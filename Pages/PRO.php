<?php
session_start();
include "../config/db.php";

$added = isset($_GET['added']);
$currentUser = $_SESSION['user'] ?? null;
$userName = $currentUser['NOM'] ?? $currentUser['nom'] ?? null;
$userEmail = $currentUser['EMAIL'] ?? $currentUser['email'] ?? null;
$userImage = $currentUser['image'] ?? $currentUser['IMAGE'] ?? 'default.png';
$cartCount = 0;

$userImage = basename($userImage);
if (empty($userImage) || $userImage === '') {
    $userImage = 'default.png';
}

$imagePath = __DIR__ . '/../img/' . $userImage;
$hasAvatar = $currentUser && file_exists($imagePath) && is_file($imagePath);
$avatarPath = $hasAvatar ? '../img/' . $userImage : '';

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
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItems as $item) {
        $cartCount += (int)$item['quantity'];
    }
}

$cartStatus = $cartCount > 0 ? '(' . $cartCount . ' article(s))' : '(Vide)';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produit CPU - BuyEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    :root {
      --surface: #ffffff;
      --bg: #f4f7fb;
      --line: #d7e2ef;
      --text: #243446;
      --primary: #0f6ddf;
      --primary-dark: #0a58b4;
    }

    * { box-sizing: border-box; }

    html,
    body {
      width: 100%;
      overflow-x: hidden;
    }
      
     
     body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text);
      background: radial-gradient(circle at top right, #deedff 0%, var(--bg) 40%, #eef2f7 100%);
      padding: 20px 0;
      margin: 0;
    }

    .main-container {
      max-width: 1140px;
      width: 100%;
      margin: 0 auto;
      padding: 0 16px;
    }

    .img-logo {
      width: 120px;
      height: auto;
    }

    /* ===== HEADER ===== */
    .header-top-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      padding: 10px 20px;
      border: 1px solid var(--line);
      border-radius: 16px;
      background: var(--surface);
      box-shadow: 0 12px 24px rgba(16, 49, 85, 0.08);
    }

    .header-logo-block {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-logo-block h1 {
      font-size: 28px;
      color: #007bff;
      font-weight: 900;
      text-transform: uppercase;
    }

    .simple-search-form {
      flex: 1;
      width: min(100%, 400px);
      max-width: 400px;
      display: flex;
      border: 1px solid var(--line);
      border-radius: 10px;
      overflow: hidden;
      margin: 10px;
      background: #f9fbfd;
    }

    .search-input-simple {
      flex: 1;
      padding: 8px;
      border: none;
      font-size: 16px;
    }

    .search-button-simple {
      background-color: var(--primary);
      color: white;
      border: none;
      padding: 8px 15px;
      cursor: pointer;
    }

    .user-actions {
      display: flex;
      align-items: center;
      gap: 15px;
      border: 1px solid #ddd;
      padding: 8px;
      border-radius: 8px;
    }

    .account-avatar-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 42px;
      height: 42px;
      min-width: 42px;
      max-width: 42px;
      flex: 0 0 42px;
      aspect-ratio: 1 / 1;
      border-radius: 50%;
      border: 2px solid #e4edf8;
      background: #ffffff;
      box-shadow: 0 6px 14px rgba(15, 43, 76, 0.1);
      overflow: hidden;
      text-decoration: none;
    }

    .header-avatar {
      width: 100%;
      height: 100%;
      min-width: 100%;
      max-width: 100%;
      display: block;
      object-fit: cover;
      object-position: center;
    }

    .header-avatar-placeholder {
      color: var(--primary);
      font-size: 20px;
    }

    .account-info {
      display: flex;
      flex-direction: column;
      font-size: 14px;
    }

    .account-info a {
      color: #007bff;
      text-decoration: none;
      font-weight: bold;
    }

    .cart-link {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: #333;
    }

    .cart-icon-container {
      position: relative;
      font-size: 22px;
    }

    .cart-count {
      position: absolute;
      top: -5px;
      right: -10px;
      background-color: #ff9900;
      color: white;
      font-size: 12px;
      font-weight: bold;
      border-radius: 50%;
      padding: 3px 6px;
    }

    .cart-label {
      font-size: 16px;
      color: #007bff;
      font-weight: bold;
    }

    .cart-details {
      display: flex;
      flex-direction: column;
      line-height: 1.1;
    }

    .cart-status {
      color: #5b6b7d;
      font-size: 12px;
    }

    /* ===== PRODUIT ===== */
    .container {
      display: flex;
      justify-content: center;
      padding: 22px 0;
    }

    .custom-card {
      display: flex;
      flex-direction: row;
      gap: 20px;
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 24px;
      max-width: 1000px;
      box-shadow: 0 12px 24px rgba(16, 49, 85, 0.08);
    }

    .card-img img {
      width: 100%;
      max-width: 500px;
      border-radius: 12px;
      max-height: 500px;
      object-fit: cover;
    }

    .info {
      flex: 1;
    }

    .price {
      margin-top: 10px;
    }

    .old-price {
      text-decoration: line-through;
      color: gray;
      font-size: 16px;
    }

    .last-price {
      color: #0c3566;
      font-size: 24px;
      font-weight: bold;
    }

    .bo {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }

    .num {
      flex: 1;
      height: 46px;
      border-radius: 8px;
      border: 1px solid var(--line);
      padding: 8px;
    }

    .buy {
      flex: 2;
      min-height: 46px;
      border-radius: 8px;
      background-color: var(--primary);
      color: white;
      font-size: 16px;
      border: none;
      cursor: pointer;
      font-weight: 700;
    }

    .buy:hover {
      background-color: var(--primary-dark);
    }

    .buy, .search-button-simple {
      transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .buy:hover, .search-button-simple:hover {
      transform: translateY(-1px);
    }

    .flash-added {
      color: #1c7b39;
      font-weight: 700;
      margin-top: 8px;
      background: #eaf8ef;
      border: 1px solid #c8ebd2;
      border-radius: 8px;
      padding: 8px 10px;
      display: inline-block;
    }

    .review-section {
      max-width: 1000px;
      margin: 0 auto 20px;
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: 16px;
      padding: 18px;
      box-shadow: 0 8px 18px rgba(16, 49, 85, 0.06);
    }

    .review-form textarea {
      width: 100%;
      height: 140px;
      padding: 12px;
      border: 1px solid var(--line);
      border-radius: 10px;
      resize: vertical;
      font: inherit;
    }

    .review-submit {
      width: 220px;
      max-width: 100%;
      padding: 12px 20px;
      border: none;
      border-radius: 10px;
      background-color: var(--primary);
      color: white;
      font-size: 16px;
      cursor: pointer;
      font-weight: 700;
    }

    .review-submit:hover { background-color: var(--primary-dark); }

    .review-heading {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 14px;
    }

    .review-heading h2 {
      margin: 0 0 4px;
      color: var(--primary);
    }

    .review-heading p {
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
      margin-bottom: 10px;
    }

    .rating-picker input {
      position: absolute;
      opacity: 0;
      pointer-events: none;
    }

    .rating-picker label {
      font-size: 28px;
      cursor: pointer;
      color: #c7d1dd;
    }

    .rating-picker input:checked ~ label,
    .rating-picker label:hover,
    .rating-picker label:hover ~ label {
      color: #ff9f1c;
    }

    .review-message,
    .review-login,
    .review-empty {
      margin: 10px 0;
      color: #5b6b7d;
      font-weight: 600;
    }

    .review-message.success {
      color: #1c7b39;
    }

    .review-message.warning {
      color: #9f5f09;
    }

    .reviews-list {
      display: grid;
      gap: 10px;
      margin-top: 16px;
    }

    .review-item {
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 12px;
      background: #f9fbfd;
    }

    .review-item-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .review-item p {
      margin: 8px 0 0;
      line-height: 1.5;
    }

    a:focus-visible,
    button:focus-visible,
    input:focus-visible,
    textarea:focus-visible {
      outline: 3px solid rgba(15, 109, 223, 0.25);
      outline-offset: 2px;
    }

    /* ===== MEDIA QUERIES ===== */
    @media (min-width: 768px) and (max-width: 1023px) {
      .custom-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .bo {
        flex-direction: column;
        width: 100%;
      }

      .num, .buy {
        width: 100%;
      }

      .header-top-row {
        flex-direction: column;
        align-items: center;
        gap: 15px;
      }

       .simple-search-form {
          width: 100%;
        
        }

        .user-actions {
          width: 100%;
          justify-content: space-between;
        }
    }

    @media (max-width: 767px) {
      .container {
        flex-direction: column;
        padding: 10px;
      }

      .custom-card {
        flex-direction: column;
        align-items: center;
        text-align: center;
      }

      .card-img img {
        max-width: 100%;
      }

      .bo {
        flex-direction: column;
        width: 100%;
      }

      .num, .buy {
        width: 100%;
      }

      .header-top-row {
        flex-direction: column;
        align-items: stretch;
        gap: 10px;
        padding: 12px;
      }

      .header-logo-block,
      .user-actions {
        justify-content: center;
      }

      .simple-search-form {
        width: 100%;
        max-width: none;
        margin: 0;
      }

      .review-heading,
      .review-item-header {
        flex-wrap: wrap;
      }

      h1, h3{
         font-size: 90%; /* réduit légèrement */ 
        } 
      .buy {
         width: 100%; margin-top: 10px; font-size: 15px;
       } 
      textarea {
         width: 100% !important; margin: auto !important;
         } 
      input[type="submit"] {
         width: 100% !important; margin: auto !important; display: block; 
        }

    }
      
    </style>
</head>
<body>
    <div class="">
      <header class="header-top-row">
        <!-- LOGO et TITRE -->
        <nav class="header-logo-block">
         
          <a href="../index.php">
            <img src="../img/logo.eco.png" alt="BuyEase" class="img-logo" />
          </a>
        </nav>

        <!-- BLOC 2: RECHERCHE -->
        <form action="#" method="GET" class="simple-search-form">
          <input
            type="text"
            name="q"
            placeholder="Rechercher un produit..."
            class="search-input-simple"
          />
          <button type="submit" class="search-button-simple">OK</button>
        </form>

        <div class="user-actions">
          <a
            href="<?= $currentUser ? 'profile.php' : '../Backend/login/Login.php' ?>"
            class="account-avatar-link"
            aria-label="<?= $currentUser ? 'Voir le profile' : 'Se connecter' ?>"
          >
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
      <main class="container">
        <div class="custom-card">
          <div class="card-img">
        <img src="../img/Product.1.1_20251121_150619.png" alt="Intel Core i9-13900K">
      </div>
      <div class="info">
        <h4>BuyEase</h4>
        <h3>Intel Core i9-13900K TRAY</h3>
        <p>
          Le processeur Intel Core i9-13900K TRAY est un processeur de pointe conçu pour les utilisateurs exigeants. Il offre une puissance de calcul exceptionnelle, idéale pour les jeux vidéo, les logiciels de traitement intensif et les applications professionnelles.
        </p>
        <div class="price">
          <span class="old-price">7,349.00 MAD</span><br>
          <span class="last-price">6,349.00 MAD</span>
        </div>
        <?php if ($added): ?>
          <p class="flash-added">Produit ajoute au panier.</p>
        <?php endif; ?>
        <div class="bo">
          <form action="../Backend/cart.php" method="post" style="display: flex; gap: 10px; width: 100%;">
            <input type="hidden" name="product_name" value="Intel Core i9-13900K TRAY">
            <input type="hidden" name="product_price" value="6349">
            <input type="hidden" name="product_image" value="Product.1.1_20251121_150619.png">
            <input type="hidden" name="redirect" value="../Pages/panier.php">
            <input type="number" name="quantity" class="num" placeholder="Qte" min="1" value="1" required>
            <button type="submit" class="buy">Ajouter au panier</button>
          </form>
        </div>
      </div>
    </div>
  </main>
      <?php
        $feedbackProductName = 'Intel Core i9-13900K TRAY';
        $feedbackRedirect = '../Pages/PRO.php';
        require __DIR__ . '/includes/product-feedback-widget.php';
      ?>
</body>
</html>
