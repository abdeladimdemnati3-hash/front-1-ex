<?php
session_start();
include "../config/db.php";

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

$userName = $currentUser['NOM'] ?? $currentUser['nom'] ?? 'User';
$userEmail = $currentUser['EMAIL'] ?? $currentUser['email'] ?? '';
$userImage = $currentUser['image'] ?? $currentUser['IMAGE'] ?? 'default.png';
$typeAdmin = $currentUser['type_admin'] ?? $currentUser['TYPE_ADMIN'] ?? 'N';
$isAdmin = $typeAdmin === 'A';

// Sanitize image name
$userImage = basename($userImage);
if (empty($userImage) || $userImage === '') {
    $userImage = 'default.png';
}

// Get cart count
$cartCount = 0;
$cartStmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_email = ?");
$cartStmt->execute([$userEmail]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cartItems as $item) {
    $cartCount += (int)$item['quantity'];
}

$cartStatus = $cartCount > 0 ? '(' . $cartCount . ' article(s))' : '(Vide)';

// Get status filter from query
$statusFilter = $_GET['status'] ?? 'all';
$validStatuses = ['all', 'Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];
if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = 'all';
}

// Get user's orders
$ordersSql = "SELECT * FROM orders WHERE user_email = ?";
$orderParams = [$userEmail];

if ($statusFilter !== 'all') {
    $ordersSql .= " AND order_status = ?";
    $orderParams[] = $statusFilter;
}

$ordersSql .= " ORDER BY created_at DESC";
$ordersStmt = $pdo->prepare($ordersSql);
$ordersStmt->execute($orderParams);
$orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);

// Get order items for display
$orderItems = [];
foreach ($orders as $order) {
    $itemsSql = "SELECT * FROM order_items WHERE order_id = ?";
    $itemsStmt = $pdo->prepare($itemsSql);
    $itemsStmt->execute([$order['id']]);
    $orderItems[$order['id']] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
}

$avatarPath = '../img/' . basename($userImage);
if (!is_file(__DIR__ . '/../img/' . basename($userImage))) {
    $avatarPath = '../img/default.png';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mes Commandes</title>
  <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  <link rel="stylesheet" href="../index-style.css" />
  <link rel="stylesheet" href="commandes.css" />
</head>
<body>
  <div class="main-container">
    <header class="header-top-row">
      <nav class="logo">
        <a href="../index.php">
          <img src="../img/logo.eco.png" alt="BuyEase Logo" width="300" />
        </a>
      </nav>

      <div class="user-actions">
        <div class="account-info">
          <span>Bienvenue</span>
          <a href="profile.php"><?= htmlspecialchars($userName) ?></a>
        </div>

        <a href="panier.php" class="cart-link">
          <div class="cart-icon-container">
            <i class="fas fa-shopping-cart" style="color: #007bff"></i>
            <span class="cart-count"><?= (int)$cartCount ?></span>
          </div>
          <div class="cart-details">
            <span class="cart-label">Panier</span>
            <span class="cart-status"><?= htmlspecialchars($cartStatus) ?></span>
          </div>
        </a>
      </div>
    </header>

    <nav>
      <div class="main-nav-buttons">
        <a href="../index.php" class="nav-button">Home</a>
        <a href="panier.php" class="nav-button">Panier</a>
        <a href="commandes.php" class="nav-button active-nav">Commandes</a>
      </div>

      <div class="auth-buttons-group">
        <?php if ($isAdmin): ?>
          <a href="../Backend/admin/admin.php" class="auth-button admin-button">Admin</a>
        <?php endif; ?>
        <a href="profile.php" class="auth-button">Profile</a>
        <a href="../Backend/login/logout.php" class="auth-button logout-button">Logout</a>
      </div>
    </nav>

    <main class="commandes-page">
      <h1>Mes Commandes</h1>

      <!-- Status Filter -->
      <div class="filter-section">
        <span class="filter-label">Filtrer par statut:</span>
        <div class="filter-buttons">
          <a href="commandes.php" class="filter-btn <?= ($statusFilter === 'all') ? 'active' : '' ?>">
            Tous
          </a>
          <a href="commandes.php?status=Pending" class="filter-btn status-pending <?= ($statusFilter === 'Pending') ? 'active' : '' ?>">
            <i class="fas fa-hourglass-half"></i> En attente
          </a>
          <a href="commandes.php?status=Confirmed" class="filter-btn status-confirmed <?= ($statusFilter === 'Confirmed') ? 'active' : '' ?>">
            <i class="fas fa-check-circle"></i> Confirmée
          </a>
          <a href="commandes.php?status=Shipped" class="filter-btn status-shipped <?= ($statusFilter === 'Shipped') ? 'active' : '' ?>">
            <i class="fas fa-truck"></i> Expédiée
          </a>
          <a href="commandes.php?status=Delivered" class="filter-btn status-delivered <?= ($statusFilter === 'Delivered') ? 'active' : '' ?>">
            <i class="fas fa-box"></i> Livrée
          </a>
          <a href="commandes.php?status=Cancelled" class="filter-btn status-cancelled <?= ($statusFilter === 'Cancelled') ? 'active' : '' ?>">
            <i class="fas fa-times-circle"></i> Annulée
          </a>
        </div>
      </div>

      <!-- Orders List -->
      <?php if (empty($orders)): ?>
        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>Vous n'avez pas encore de commandes.</p>
          <a href="../index.php" class="btn-continue-shopping">Continuer les achats</a>
        </div>
      <?php else: ?>
        <div class="orders-list">
          <?php foreach ($orders as $order): ?>
            <div class="order-card">
              <div class="order-header">
                <div class="order-info">
                  <h3>Commande #<?= (int)$order['id'] ?></h3>
                  <p class="order-date">
                    <i class="fas fa-calendar"></i>
                    <?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?>
                  </p>
                </div>
                <div class="order-status">
                  <span class="status-badge status-<?= strtolower($order['order_status']) ?>">
                    <?php 
                      $statusText = [
                        'Pending' => 'En attente',
                        'Confirmed' => 'Confirmée',
                        'Shipped' => 'Expédiée',
                        'Delivered' => 'Livrée',
                        'Cancelled' => 'Annulée'
                      ];
                      echo $statusText[$order['order_status']] ?? $order['order_status'];
                    ?>
                  </span>
                </div>
              </div>

              <div class="order-body">
                <table class="order-items-table">
                  <thead>
                    <tr>
                      <th>Produit</th>
                      <th class="text-right">Prix</th>
                      <th class="text-right">Quantité</th>
                      <th class="text-right">Sous-total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (isset($orderItems[$order['id']])): ?>
                      <?php foreach ($orderItems[$order['id']] as $item): ?>
                        <tr>
                          <td><?= htmlspecialchars($item['product_name']) ?></td>
                          <td class="text-right"><?= number_format((float)$item['product_price'], 2, ',', ' ') ?> MAD</td>
                          <td class="text-right"><?= (int)$item['quantity'] ?></td>
                          <td class="text-right font-bold"><?= number_format((float)$item['subtotal'], 2, ',', ' ') ?> MAD</td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>

              <div class="order-footer">
                <div class="total-section">
                  <span class="total-label">Total:</span>
                  <span class="total-amount"><?= number_format((float)$order['total_amount'], 2, ',', ' ') ?> MAD</span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
