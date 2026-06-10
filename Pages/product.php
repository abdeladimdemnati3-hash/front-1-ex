<?php
session_start();
include "../config/db.php";

$productId = (int)($_GET['id'] ?? 0);
$currentUser = $_SESSION['user'] ?? null;
$userName = $currentUser['NOM'] ?? $currentUser['nom'] ?? null;
$userEmail = $currentUser['EMAIL'] ?? $currentUser['email'] ?? null;
$userImage = $currentUser['image'] ?? $currentUser['IMAGE'] ?? 'default.png';
$typeAdmin = $currentUser['type_admin'] ?? $currentUser['TYPE_ADMIN'] ?? 'N';
$isAdmin = $typeAdmin === 'A';
$cartCount = 0;

$userImage = basename($userImage);
if (empty($userImage) || $userImage === '') {
    $userImage = 'default.png';
}

$imagePath = __DIR__ . '/../img/' . $userImage;
$hasAvatar = $currentUser && file_exists($imagePath) && is_file($imagePath);
$avatarPath = $hasAvatar ? '../img/' . $userImage : '';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS produits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);

try {
    $pdo->exec("ALTER TABLE produits ADD COLUMN IF NOT EXISTS type_product VARCHAR(100) DEFAULT 'General'");
} catch (Exception $e) {
}

try {
    $pdo->exec("ALTER TABLE produits ADD COLUMN product_type VARCHAR(100) DEFAULT 'General'");
} catch (Exception $e) {
}

if ($userEmail) {
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
    foreach ($cartStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $cartCount += (int)$item['quantity'];
    }
}

$cartStatus = $cartCount > 0 ? '(' . $cartCount . ' article(s))' : '(Vide)';

$productStmt = $pdo->prepare("SELECT id, nom, prix, image, COALESCE(product_type, type_product) AS product_type FROM produits WHERE id = ?");
$productStmt->execute([$productId]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: ../index.php");
    exit();
}

$added = isset($_GET['added']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($product['nom']) ?> - BuyEase</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../index-style.css">
  <style>
    .product-page {
      margin-top: 28px;
    }

    .product-detail {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
      gap: 26px;
      align-items: center;
      background: #fff;
      border: 1px solid #d8e0ea;
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 10px 25px rgba(15, 43, 76, 0.08);
    }

    .product-detail-image {
      width: 100%;
      aspect-ratio: 4 / 3;
      max-height: 460px;
      object-fit: cover;
      border-radius: 14px;
      background: #f8fbff;
    }

    .product-detail h1 {
      margin: 0 0 12px;
      color: #1e293b;
      overflow-wrap: anywhere;
    }

    .product-type {
      display: inline-block;
      margin-bottom: 12px;
      padding: 6px 10px;
      border-radius: 999px;
      background: #eef6ff;
      color: #0f6ddf;
      font-weight: 700;
    }

    .product-price {
      margin: 0 0 18px;
      font-size: 28px;
      font-weight: 800;
      color: #0f6ddf;
    }

    .product-actions {
      display: grid;
      grid-template-columns: 120px 1fr;
      gap: 12px;
      align-items: stretch;
      max-width: 520px;
    }

    .qty-input {
      width: 100%;
      min-height: 54px;
      border: 1px solid #d8e0ea;
      border-radius: 10px;
      padding: 10px 14px;
      font: inherit;
      font-weight: 700;
    }

    .product-actions .btn-sh {
      min-height: 54px;
      width: 100%;
      border-radius: 10px;
      font-size: 16px;
    }

    .review-section {
      margin: 24px auto 0;
      background: #fff;
      border: 1px solid #d8e0ea;
      border-radius: 18px;
      padding: 20px;
      box-shadow: 0 10px 25px rgba(15, 43, 76, 0.08);
    }

    .review-heading,
    .review-item-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
    }

    .review-heading h2 {
      margin: 0 0 4px;
      color: #0f6ddf;
    }

    .review-heading p,
    .review-message,
    .review-login,
    .review-empty {
      margin: 0;
      color: #5b6b7d;
      font-weight: 600;
    }

    .review-stars,
    .rating-picker label,
    .review-item-header span {
      color: #ff9f1c;
      letter-spacing: 0;
    }

    .rating-picker {
      display: inline-flex;
      flex-direction: row-reverse;
      gap: 4px;
      margin: 16px 0 10px;
      isolation: isolate;
    }

    .rating-picker input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .rating-picker label {
      color: #c7d1dd;
      cursor: pointer;
      font-size: 30px;
      line-height: 1;
      transform-origin: center;
      transition: color 0.18s ease, transform 0.18s ease, filter 0.18s ease, text-shadow 0.18s ease;
    }

    .rating-picker input:checked ~ label,
    .rating-picker label:hover,
    .rating-picker label:hover ~ label {
      color: #ff9f1c;
      filter: drop-shadow(0 6px 10px rgba(255, 159, 28, 0.22));
      text-shadow: 0 0 16px rgba(255, 159, 28, 0.22);
    }

    .rating-picker label:hover {
      transform: translateY(-3px) scale(1.18) rotate(-5deg);
    }

    .rating-picker label:hover ~ label {
      transform: translateY(-1px) scale(1.06);
    }

    .rating-picker input:checked + label {
      animation: star-pop 0.32s cubic-bezier(0.2, 0.9, 0.25, 1.35);
    }

    .rating-picker input:focus-visible + label {
      outline: 3px solid rgba(15, 109, 223, 0.25);
      outline-offset: 4px;
      border-radius: 8px;
    }

    @keyframes star-pop {
      0% {
        transform: scale(0.85);
      }
      55% {
        transform: scale(1.32) rotate(-8deg);
      }
      100% {
        transform: scale(1);
      }
    }

    .review-form textarea {
      width: 100%;
      min-height: 120px;
      border: 1px solid #d8e0ea;
      border-radius: 12px;
      padding: 12px;
      font: inherit;
      resize: vertical;
    }

    .review-submit {
      margin-top: 10px;
      min-height: 40px;
      border: 0;
      border-radius: 10px;
      padding: 0 18px;
      background: #0f6ddf;
      color: #fff;
      font-weight: 800;
      cursor: pointer;
    }

    .reviews-list {
      display: grid;
      gap: 12px;
      margin-top: 18px;
    }

    .review-item {
      border: 1px solid #d8e0ea;
      border-radius: 12px;
      padding: 12px;
      background: #f9fbfd;
    }

    .review-item p {
      margin: 8px 0 0;
      line-height: 1.5;
    }

    @media (max-width: 767px) {
      .product-page {
        margin-top: 18px;
      }

      .product-detail {
        grid-template-columns: 1fr;
        gap: 18px;
        padding: 14px;
        border-radius: 12px;
      }

      .product-actions {
        grid-template-columns: 1fr;
      }

      .product-price {
        font-size: 24px;
      }

      .review-section {
        padding: 14px;
        border-radius: 12px;
      }
    }

    @media (max-width: 420px) {
      .rating-picker label {
        font-size: 26px;
      }
    }
  </style>
</head>
<body>
  <div class="main-container">
    <header class="header-top-row">
      <nav class="logo">
        <a href="../index.php">
          <img src="../img/logo_pp.png" alt="BuyEase Logo" width="300" />
        </a>
      </nav>

      <div class="header-spacer" aria-hidden="true"></div>

      <div class="user-actions">
        <a href="<?= $currentUser ? 'profile.php' : '../Backend/login/Login.php' ?>" class="account-avatar-link" aria-label="<?= $currentUser ? 'Voir le profile' : 'Se connecter' ?>">
          <?php if ($hasAvatar): ?>
            <img src="<?= htmlspecialchars($avatarPath) ?>" alt="Photo de profile" class="header-avatar" width="42" height="42">
          <?php else: ?>
            <i class="fas fa-user header-avatar-placeholder" aria-hidden="true"></i>
          <?php endif; ?>
        </a>

        <div class="account-info">
          <span>Bienvenue</span>
          <?php if ($currentUser): ?>
            <a href="profile.php"><?= htmlspecialchars($userName ?: $userEmail) ?></a>
          <?php else: ?>
            <a href="../Backend/login/Login.php">Identifiez-vous</a>
          <?php endif; ?>
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
        <a href="commandes.php" class="nav-button">Commandes</a>
      </div>

      <div class="auth-buttons-group">
        <?php if ($currentUser && $isAdmin): ?>
          <a href="../Backend/admin/admin.php" class="auth-button admin-button">Admin</a>
        <?php endif; ?>
        <?php if ($currentUser): ?>
          <a href="profile.php" class="auth-button profile-button">Profile</a>
          <a href="../Backend/login/logout.php" class="auth-button logout-button">Logout</a>
        <?php else: ?>
          <a href="../Backend/login/Login.php" class="auth-button">Login</a>
        <?php endif; ?>
      </div>
    </nav>

    <main class="product-page">
      <section class="product-detail">
        <img
          src="../img/<?= htmlspecialchars($product['image'] ?: 'logo.eco.png') ?>"
          alt="<?= htmlspecialchars($product['nom']) ?>"
          class="product-detail-image"
        >

        <div>
          <span class="product-type"><?= htmlspecialchars($product['product_type'] ?? 'Produit') ?></span>
          <h1><?= htmlspecialchars($product['nom']) ?></h1>
          <p class="product-price"><?= number_format((float)$product['prix'], 2) ?> MAD</p>

          <?php if ($added): ?>
            <p class="cart-flash success">Produit ajoute au panier.</p>
          <?php endif; ?>

          <?php if ($currentUser): ?>
            <form action="../Backend/cart.php" method="post" class="product-actions">
              <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['nom']) ?>">
              <input type="hidden" name="product_price" value="<?= (float)$product['prix'] ?>">
              <input type="hidden" name="product_image" value="<?= htmlspecialchars($product['image'] ?? '') ?>">
              <input type="hidden" name="redirect" value="../Pages/panier.php">
              <input type="number" name="quantity" class="qty-input" min="1" value="1" required>
              <button type="submit" class="btn-sh">Ajouter au panier</button>
            </form>
          <?php else: ?>
            <a class="btn-sh" href="../Backend/login/Login.php">Connectez-vous</a>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <?php
      $feedbackProductName = $product['nom'];
      $feedbackRedirect = '../Pages/product.php?id=' . (int)$product['id'];
      require __DIR__ . '/includes/product-feedback-widget.php';
    ?>
  </div>
</body>
</html>
