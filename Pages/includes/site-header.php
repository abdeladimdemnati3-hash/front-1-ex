<?php
$rootPrefix = $rootPrefix ?? '../';
$activePage = $activePage ?? '';
$currentUser = $currentUser ?? null;
$userName = $userName ?? null;
$userEmail = $userEmail ?? null;
$cartCount = $cartCount ?? 0;
$cartStatus = $cartStatus ?? '(Vide)';
$isAdmin = $isAdmin ?? false;
$showSearch = $showSearch ?? false;
$searchAction = $searchAction ?? ($rootPrefix . 'index.php');
$searchValue = $searchValue ?? '';
$accountLabel = $userName ?: ($userEmail ?: 'Mon compte');
?>
<header class="header-top-row">
  <nav class="logo">
    <a href="<?= htmlspecialchars($rootPrefix) ?>index.php">
      <img src="<?= htmlspecialchars($rootPrefix) ?>img/logo.eco.png" alt="BuyEase Logo" width="300" />
    </a>
  </nav>

  <?php if ($showSearch): ?>
    <form action="<?= htmlspecialchars($searchAction) ?>" method="GET" class="simple-search-form">
      <input
        type="text"
        name="q"
        placeholder="Rechercher un produit..."
        class="search-input-simple"
        value="<?= htmlspecialchars($searchValue) ?>"
      />
      <button type="submit" class="search-button-simple">OK</button>
    </form>
  <?php else: ?>
    <div class="header-spacer" aria-hidden="true"></div>
  <?php endif; ?>

  <div class="user-actions">
    <div class="account-info">
      <span>Bienvenue</span>
      <?php if ($currentUser): ?>
        <a href="<?= htmlspecialchars($rootPrefix) ?>Pages/profile.php"><?= htmlspecialchars($accountLabel) ?></a>
      <?php else: ?>
        <a href="<?= htmlspecialchars($rootPrefix) ?>Backend/login/Login.php">Identifiez-vous</a>
      <?php endif; ?>
    </div>

    <a href="<?= htmlspecialchars($rootPrefix) ?>Pages/panier.php" class="cart-link">
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
    <a href="<?= htmlspecialchars($rootPrefix) ?>index.php" class="nav-button<?= $activePage === 'home' ? ' active' : '' ?>">Home</a>
    <a href="<?= htmlspecialchars($rootPrefix) ?>Pages/about/about.php" class="nav-button<?= $activePage === 'about' ? ' active' : '' ?>">À propos</a>
    <a href="<?= htmlspecialchars($rootPrefix) ?>Pages/Services/Services.php" class="nav-button<?= $activePage === 'services' ? ' active' : '' ?>">Services</a>
    <a href="<?= htmlspecialchars($rootPrefix) ?>Pages/CONTACT/Contact.php" class="nav-button<?= $activePage === 'contact' ? ' active' : '' ?>">Contact</a>
    <a href="<?= htmlspecialchars($rootPrefix) ?>Pages/Legal/Legal.php" class="nav-button<?= $activePage === 'legal' ? ' active' : '' ?>">Legal</a>
  </div>

  <div class="auth-buttons-group">
    <?php if ($currentUser && $isAdmin): ?>
      <a href="<?= htmlspecialchars($rootPrefix) ?>Backend/admin/admin.php" class="auth-button admin-button">Admin</a>
    <?php endif; ?>
    <?php if ($currentUser): ?>
      <a href="<?= htmlspecialchars($rootPrefix) ?>Pages/profile.php" class="auth-button profile-button">Profile</a>
      <a href="<?= htmlspecialchars($rootPrefix) ?>Backend/login/logout.php" class="auth-button logout-button">Logout</a>
    <?php else: ?>
      <a href="<?= htmlspecialchars($rootPrefix) ?>Backend/login/Login.php" class="auth-button">Login</a>
      <a href="<?= htmlspecialchars($rootPrefix) ?>Backend/Sign_up/Sign_up.php" class="auth-button">Sign up</a>
    <?php endif; ?>
  </div>
</nav>
