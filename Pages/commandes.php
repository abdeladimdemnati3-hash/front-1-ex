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

$userImage = basename($userImage);

if (empty($userImage) || $userImage === '') {
    $userImage = 'default.png';
}

$cartCount = 0;

try {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cart_items (
          id INT AUTO_INCREMENT PRIMARY KEY,
          user_id INT NULL,
          user_email VARCHAR(255) NOT NULL,
          product_name VARCHAR(255) NOT NULL,
          product_price DECIMAL(10,2) NOT NULL,
          product_image VARCHAR(255) DEFAULT NULL,
          quantity INT NOT NULL DEFAULT 1,
          created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $cartStmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_email = ?");
    $cartStmt->execute([$userEmail]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItems as $item) {
        $cartCount += (int)$item['quantity'];
    }
} catch (Exception $e) {
    $cartCount = 0;
}

$cartStatus = $cartCount > 0 ? '(' . $cartCount . ' article(s))' : '(Vide)';

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (Exception $e) {
}

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN order_status VARCHAR(50) DEFAULT 'Pending'");
} catch (Exception $e) {
}

try {
    $pdo->exec("ALTER TABLE order_items ADD COLUMN product_price DECIMAL(10,2) DEFAULT 0");
} catch (Exception $e) {
}

try {
    $pdo->exec("ALTER TABLE order_items ADD COLUMN subtotal DECIMAL(10,2) DEFAULT 0");
} catch (Exception $e) {
}

$statusFilter = $_GET['status'] ?? 'all';

$validStatuses = ['all', 'Pending', 'Confirmed', 'Shipped', 'Delivered', 'Cancelled'];

if (!in_array($statusFilter, $validStatuses)) {
    $statusFilter = 'all';
}

try {
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

} catch (Exception $e) {
    $orders = [];
}

$orderItems = [];

foreach ($orders as $order) {

    try {
        $itemsSql = "SELECT * FROM order_items WHERE order_id = ?";

        $itemsStmt = $pdo->prepare($itemsSql);
        $itemsStmt->execute([$order['id']]);

        $orderItems[$order['id']] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        $orderItems[$order['id']] = [];
    }
}

$userImage = basename($userImage);

if (empty($userImage) || $userImage === '' || $userImage === 'default.png') {
    $userImage = 'default.png';
}

$imagePath = __DIR__ . '/../img/' . $userImage;

$hasAvatar = file_exists($imagePath) && is_file($imagePath);
$avatarPath = $hasAvatar ? '../img/' . $userImage : '';
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

        <a href="profile.php" class="account-avatar-link" aria-label="Voir le profile">
          <?php if ($hasAvatar): ?>
            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Photo de profile" class="header-avatar" width="42" height="42">
          <?php else: ?>
            <i class="fas fa-user header-avatar-placeholder" aria-hidden="true"></i>
          <?php endif; ?>
        </a>

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

      <?php if (empty($orders)): ?>

        <div class="empty-state">
          <i class="fas fa-inbox"></i>
          <p>Vous n'avez pas encore de commandes.</p>
          <a href="../index.php" class="btn-continue-shopping">Continuer les achats</a>
        </div>

      <?php else: ?>

        <div class="orders-list">

          <?php foreach ($orders as $order): ?>

            <?php
                $orderId = $order['id'] ?? 0;
                $orderStatus = $order['order_status'] ?? 'Pending';
                $orderDate = $order['created_at'] ?? null;
                $orderDateStr = $orderDate ? date('d/m/Y à H:i', strtotime($orderDate)) : 'Date non disponible';
                $totalAmount = $order['total_amount'] ?? 0;
            ?>

            <div class="order-card">

              <div class="order-header">

                <div class="order-info">
                  <h3>Commande #<?= (int)$orderId ?></h3>

                  <p class="order-date">
                    <i class="fas fa-calendar"></i>
                    <?= htmlspecialchars($orderDateStr) ?>
                  </p>
                </div>

                <div class="order-status">

                  <span class="status-badge status-<?= strtolower($orderStatus) ?>">

                    <?php 
                      $statusText = [
                        'Pending' => 'En attente',
                        'Confirmed' => 'Confirmée',
                        'Shipped' => 'Expédiée',
                        'Delivered' => 'Livrée',
                        'Cancelled' => 'Annulée'
                      ];

                      echo $statusText[$orderStatus] ?? htmlspecialchars($orderStatus);
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

                    <?php if (isset($orderItems[$orderId])): ?>

                      <?php foreach ($orderItems[$orderId] as $item): ?>

                        <tr>
                          <td><?= htmlspecialchars($item['product_name'] ?? 'Produit inconnu') ?></td>
                          <td class="text-right"><?= number_format((float)($item['product_price'] ?? 0), 2, ',', ' ') ?> MAD</td>
                          <td class="text-right"><?= (int)($item['quantity'] ?? 0) ?></td>
                          <td class="text-right font-bold"><?= number_format((float)($item['subtotal'] ?? 0), 2, ',', ' ') ?> MAD</td>
                        </tr>

                      <?php endforeach; ?>

                    <?php endif; ?>

                  </tbody>

                </table>

              </div>

              <div class="order-footer">

                <div class="total-section">
                  <span class="total-label">Total:</span>
                  <span class="total-amount"><?= number_format((float)$totalAmount, 2, ',', ' ') ?> MAD</span>
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
