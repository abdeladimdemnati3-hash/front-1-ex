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

$cartCount = 0;
$cartStmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_email = ?");
$cartStmt->execute([$userEmail]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cartItems as $item) {
    $cartCount += (int)$item['quantity'];
}

$cartStatus = $cartCount > 0 ? '(' . $cartCount . ' article(s))' : '(Vide)';
$updated = isset($_GET['updated']);

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
  <title>Mon Profile</title>
  <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
  <link rel="stylesheet" href="../index-style.css" />
  <link rel="stylesheet" href="profile.css" />
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
      </div>

      <div class="auth-buttons-group">
        <?php if ($isAdmin): ?>
          <a href="../Backend/admin/admin.php" class="auth-button admin-button">Admin</a>
        <?php endif; ?>
        <a href="../Backend/login/logout.php" class="auth-button logout-button">Logout</a>
      </div>
    </nav>

    <main class="profile-page">
      <h1>Mon Profile</h1>

      <?php if ($updated): ?>
        <p class="cart-flash success">Profile mis a jour avec succes.</p>
      <?php endif; ?>

      <div class="profile-card">
        <div class="avatar-wrap">
          <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Photo de profile" class="avatar">
        </div>

        <form action="../Backend/profile-update.php" method="POST" enctype="multipart/form-data" class="profile-form">
          <label>Nom</label>
          <input type="text" name="nom" value="<?= htmlspecialchars($userName) ?>" required>

          <label>Email</label>
          <input type="email" value="<?= htmlspecialchars($userEmail) ?>" disabled>

          <label>Photo de profile</label>
          <input type="file" name="image" accept="image/*">

          <button type="submit" class="btn-sh">Enregistrer les modifications</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
