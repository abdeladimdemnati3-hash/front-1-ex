<?php
session_start();
include "../config/db.php";

$currentUser = $_SESSION['user'] ?? null;
if (!$currentUser) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

$userName = $currentUser['NOM'] ?? $currentUser['nom'] ?? 'User';
$userEmail = $currentUser['EMAIL'] ?? $currentUser['email'] ?? null;
$typeAdmin = $currentUser['type_admin'] ?? $currentUser['TYPE_ADMIN'] ?? 'N';
$isAdmin = $typeAdmin === 'A';
$addedToCart = isset($_GET['added']);
$removedFromCart = isset($_GET['removed']);
$cartCount = 0;
$total = 0;

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

$cartSql = "SELECT id, product_name, product_price, product_image, quantity FROM cart_items WHERE user_email = ? ORDER BY id DESC";
$cartStmt = $pdo->prepare($cartSql);
$cartStmt->execute([$userEmail]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($cartItems as $item) {
    $qty = (int)$item['quantity'];
    $price = (float)$item['product_price'];
    $cartCount += $qty;
    $total += $qty * $price;
}

$cartStatus = $cartCount > 0 ? '(' . $cartCount . ' article(s))' : '(Vide)';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mon Panier</title>
  <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  <link rel="stylesheet" href="../index-style.css" />
  <link rel="stylesheet" href="panier.css" />
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
        <a href="profile.php" class="nav-button">Profile</a>
      </div>

      <div class="auth-buttons-group">
        <?php if ($isAdmin): ?>
          <a href="../Backend/admin/admin.php" class="auth-button admin-button">Admin</a>
        <?php endif; ?>
        <a href="../Backend/login/logout.php" class="auth-button logout-button">Logout</a>
      </div>
    </nav>

    <main class="cart-page">
      <h1>Mon Panier</h1>

      <?php if ($addedToCart): ?>
        <p class="cart-flash success">Produit ajoute au panier.</p>
      <?php endif; ?>

      <?php if ($removedFromCart): ?>
        <p class="cart-flash warning">Produit retire du panier.</p>
      <?php endif; ?>

      <?php if (empty($cartItems)): ?>
        <p class="empty-state">Votre panier est vide.</p>
      <?php else: ?>
        <div class="cart-items-grid">
          <?php foreach ($cartItems as $item): ?>
            <div class="cart-item-card">
              <?php if (!empty($item['product_image'])): ?>
                <img class="cart-item-image" src="../img/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
              <?php endif; ?>
              <h4 class="cart-item-title"><?= htmlspecialchars($item['product_name']) ?></h4>
              <p class="cart-item-text">Prix: <?= number_format((float)$item['product_price'], 2) ?> MAD</p>
              <p class="cart-item-text">Qte: <?= (int)$item['quantity'] ?></p>
              <p class="cart-item-text total-line">Sous-total: <?= number_format((float)$item['product_price'] * (int)$item['quantity'], 2) ?> MAD</p>

              <form action="../Backend/remove-cart-item.php" method="POST">
                <input type="hidden" name="item_id" value="<?= (int)$item['id'] ?>">
                <input type="hidden" name="redirect" value="../Pages/panier.php">
                <button type="submit" class="remove-cart-btn">Retirer</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="cart-summary">
          <p>Articles: <strong><?= (int)$cartCount ?></strong></p>
          <p>Total: <strong><?= number_format($total, 2) ?> MAD</strong></p>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
