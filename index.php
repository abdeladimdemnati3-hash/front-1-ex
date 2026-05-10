<?php
session_start();
include "./config/db.php";

$search = trim($_GET['q'] ?? '');
$currentUser = $_SESSION['user'] ?? null;
$userName = $currentUser['NOM'] ?? $currentUser['nom'] ?? null;
$userEmail = $currentUser['EMAIL'] ?? $currentUser['email'] ?? null;
$typeAdmin = $currentUser['type_admin'] ?? $currentUser['TYPE_ADMIN'] ?? 'N';
$isAdmin = $typeAdmin === 'A';
$cartCount = 0;
$addedToCart = isset($_GET['added']);
$userImage = $currentUser['image'] ?? $currentUser['IMAGE'] ?? 'default.png';
$userImage = basename($userImage);

if (empty($userImage) || $userImage === '') {
  $userImage = 'default.png';
}

$imagePath = __DIR__ . '/img/' . $userImage;
$hasAvatar = $currentUser && file_exists($imagePath) && is_file($imagePath);
$avatarPath = $hasAvatar ? 'img/' . $userImage : '';

$pdo->exec(
  "CREATE TABLE IF NOT EXISTS produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  )"
);

$validTypes = ['all', 'PC', 'Laptop', 'PC-Gamer', 'CPU', 'GPU'];
$typeFilter = $_GET['type'] ?? 'all';
if (!in_array($typeFilter, $validTypes, true)) {
  $typeFilter = 'all';
}

$productsSql = "SELECT id, nom, prix, image, COALESCE(product_type, type_product) AS product_type FROM produits";
$productsParams = [];
$where = [];

if ($search !== '') {
  $where[] = "nom LIKE ?";
  $productsParams[] = "%$search%";
}

if ($typeFilter !== 'all') {
  $where[] = "COALESCE(product_type, type_product) = ?";
  $productsParams[] = $typeFilter;
}

if (!empty($where)) {
  $productsSql .= " WHERE " . implode(' AND ', $where);
}

$productsSql .= " ORDER BY id DESC";
$productsStmt = $pdo->prepare($productsSql);
$productsStmt->execute($productsParams);
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
$searchResultCount = count($products);
$feedbackStats = [];

$pdo->exec(
  "CREATE TABLE IF NOT EXISTS product_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_key VARCHAR(32) NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    user_email VARCHAR(255) NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    rating TINYINT NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product_user (product_key, user_email)
  )"
);

$feedbackStmt = $pdo->query("SELECT product_key, AVG(rating) AS avg_rating, COUNT(*) AS rating_count FROM product_feedback GROUP BY product_key");
foreach ($feedbackStmt->fetchAll(PDO::FETCH_ASSOC) as $feedbackRow) {
  $feedbackStats[$feedbackRow['product_key']] = [
    'average' => (float)$feedbackRow['avg_rating'],
    'count' => (int)$feedbackRow['rating_count'],
  ];
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

  $cartSql = "SELECT quantity FROM cart_items WHERE user_email = ? ORDER BY id DESC";
  $cartStmt = $pdo->prepare($cartSql);
  $cartStmt->execute([$userEmail]);
  $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($cartItems as $item) {
    $cartCount += (int)$item['quantity'];
  }
}

$cartStatus = $cartCount > 0 ? '(' . $cartCount . ' article(s))' : '(Vide)';

function buildQuery(array $params): string {
    $query = array_merge($_GET, $params);
    return http_build_query(array_filter($query, function ($value) {
        return $value !== null && $value !== '';
    }));
}

function renderProductStars(float $rating): string {
  $rounded = (int)round($rating);
  $stars = '';

  for ($i = 1; $i <= 5; $i++) {
    $stars .= $i <= $rounded ? '★' : '☆';
  }

  return $stars;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>BuyEase Pc</title>

    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <link href="index-style.css" rel="stylesheet">
  </head>
  <body>
    <div class="main-container">
      <header class="header-top-row">
        <nav class="logo">
          <a href="index.php">
            <img src="img/logo_pp.png" alt="BuyEase Logo" width="300" />
          </a>
        </nav>

        <form action="index.php" method="GET" class="simple-search-form">
          <input
            type="text"
            name="q"
            placeholder="Rechercher un produit..."
            class="search-input-simple"
            value="<?= htmlspecialchars($search) ?>"
          />
          <button type="submit" class="search-button-simple">OK</button>
        </form>

        <div class="user-actions">
          <a
            href="<?= $currentUser ? 'Pages/profile.php' : 'Backend/login/Login.php' ?>"
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
              <a href="Pages/profile.php"><?= htmlspecialchars($userName ?: $userEmail) ?></a>
            <?php else: ?>
              <a href="Backend/login/Login.php">Identifiez-vous</a>
            <?php endif; ?>
          </div>

          <a href="Pages/panier.php" class="cart-link">
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
          <a href="index.php" class="nav-button">Home</a>
          <a href="Pages/about/about.php" class="nav-button">À propos</a>
          <a href="Pages/Services/Services.php" class="nav-button">Services</a>
          <a href="Pages/CONTACT/Contact.php" class="nav-button">Contact</a>
          <a href="Pages/Legal/Legal.php" class="nav-button">Legal</a>
          <?php if ($currentUser): ?>
            <a href="Pages/panier.php" class="nav-button">Panier</a>
            <a href="Pages/commandes.php" class="nav-button">Commandes</a>
          <?php endif; ?>
        </div>

        <div class="auth-buttons-group">
          <?php if ($currentUser && $isAdmin): ?>
            <a href="Backend/admin/admin.php" class="auth-button admin-button">Admin</a>
          <?php endif; ?>
          <?php if ($currentUser): ?>
            <a href="Pages/profile.php" class="auth-button profile-button">Profile</a>
            <a href="Backend/login/logout.php" class="auth-button logout-button">Logout</a>
          <?php else: ?>
            <a href="Backend/login/Login.php" class="auth-button">Login</a>
            <a href="Backend/Sign_up/Sign_up.php" class="auth-button">Sign up</a>
          <?php endif; ?>
        </div>
      </nav>

      <main>
        <div class="content-block">
          <h2>Bienvenue sur BuyEase Pc</h2>
          <p>
            Plonge dans l'univers du gaming haute performance ! Ici, nous
            assemblons des PC Gamer puissants, conçus pour offrir vitesse,
            stabilité et graphismes ultra-fluides.
          </p>
        </div>

        <div class="catalog-layout">
          <aside class="type-sidebar">
            <h3>Catégories</h3>
            <p class="type-sidebar-note">Filtre vertical par type de produit.</p>
            <form action="index.php" method="GET" class="category-filter-form">
              <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
              <select name="type" class="category-select" onchange="this.form.submit()">
                <?php
                  $typeNames = [
                    'all' => 'Tous les produits',
                    'PC' => 'PC',
                    'Laptop' => 'Laptop',
                    'PC-Gamer' => 'PC Gamer',
                    'CPU' => 'CPU',
                    'GPU' => 'GPU',
                  ];
                ?>
                <?php foreach ($typeNames as $typeKey => $typeLabel): ?>
                  <option value="<?= htmlspecialchars($typeKey) ?>" <?= $typeFilter === $typeKey ? 'selected' : '' ?>>
                    <?= htmlspecialchars($typeLabel) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </aside>

          <div class="products-grid">
          <?php if ($addedToCart): ?>
            <p class="cart-flash success">Produit ajoute au panier.</p>
          <?php endif; ?>

          <?php if ($search !== ''): ?>
            <div class="search-summary">
              <span><?= $searchResultCount ?> resultat(s) pour "<?= htmlspecialchars($search) ?>"</span>
              <a href="index.php" class="clear-search-link">Effacer</a>
            </div>
          <?php endif; ?>

          <?php if (empty($products)): ?>
            <p class="empty-products">Aucun produit disponible pour le moment.</p>
          <?php else: ?>
            <?php foreach ($products as $product): ?>
              <?php
                $productKey = md5(strtolower($product['nom'] ?? ''));
                $productFeedback = $feedbackStats[$productKey] ?? ['average' => 0, 'count' => 0];
              ?>
              <div class="product-slot">
                <div class="custom-card">
                  <?php if (!empty($product['image'])): ?>
                    <img
                      class="card-img"
                      src="img/<?= htmlspecialchars($product['image']) ?>"
                      alt="<?= htmlspecialchars($product['nom']) ?>"
                    />
                  <?php else: ?>
                    <img
                      class="card-img"
                      src="img/logo.eco.png"
                      alt="Produit sans image"
                    />
                  <?php endif; ?>

                  <h5 class="card-title"><?= htmlspecialchars($product['nom']) ?></h5>
                  <div class="product-rating-summary">
                    <span class="product-rating-stars"><?= renderProductStars((float)$productFeedback['average']) ?></span>
                    <span>
                      <?php if ((int)$productFeedback['count'] > 0): ?>
                        <?= number_format((float)$productFeedback['average'], 1) ?> (<?= (int)$productFeedback['count'] ?> avis)
                      <?php else: ?>
                        Aucun avis
                      <?php endif; ?>
                    </span>
                  </div>
                  <div class="price-tag">
                    <?= number_format((float)$product['prix'], 2) ?> MAD
                  </div>

                  <a class="btn-sh btn-secondary" href="Pages/product.php?id=<?= (int)$product['id'] ?>">Voir page</a>

                  <?php if ($currentUser): ?>
                    <form action="Backend/cart.php" method="POST" class="details-button add-cart-form">
                      <input type="hidden" name="product_name" value="<?= htmlspecialchars($product['nom']) ?>">
                      <input type="hidden" name="product_price" value="<?= (float)$product['prix'] ?>">
                      <input type="hidden" name="product_image" value="<?= htmlspecialchars($product['image'] ?? '') ?>">
                      <input type="hidden" name="quantity" value="1">
                      <input type="hidden" name="redirect" value="index.php<?= $search !== '' ? '?q=' . urlencode($search) : '' ?>">
                      <button type="submit" class="btn-sh">Ajouter au panier</button>
                    </form>
                  <?php else: ?>
                    <nav class="details-button"><a class="btn-sh" href="Backend/login/Login.php">Connectez-vous</a></nav>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
      </main>
    </div>
    <footer class="site-footer">
      <div class="footer-container">
        <p class="copyright">&copy; 2026 BuyEase Pc</p>
      </div>
      <div></div>
     
    </footer>
  </body>
</html>
