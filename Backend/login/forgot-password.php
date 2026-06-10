<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Changer le mot de passe - BuyEase</title>
  <link href="login.css" rel="stylesheet">
</head>
<body>
  <div class="im">
    <a href="../../index.php">
      <img src="../../img/logo_pp.png" alt="BuyEase" width="200px" />
    </a>
  </div>

  <form class="form" action="forgot-password-ver.php" method="post">
    <h1 class="form-title">Changer le mot de passe</h1>

    <?php if (isset($_GET['error'])): ?>
      <p class="error-message">
        <?php
          $errors = [
            'missing' => 'Tous les champs sont obligatoires.',
            'current' => 'Email ou mot de passe actuel incorrect.',
            'match' => 'Les mots de passe ne sont pas identiques.',
            'short' => 'Le mot de passe doit contenir au moins 6 caracteres.',
          ];

          echo htmlspecialchars($errors[$_GET['error']] ?? 'Erreur, veuillez reessayer.');
        ?>
      </p>
    <?php endif; ?>

    <input type="email" name="email" placeholder="Email de votre compte" required><br>
    <input type="password" name="current_password" placeholder="Mot de passe actuel" required><br>
    <input type="password" name="password" placeholder="Nouveau mot de passe" minlength="6" required><br>
    <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" minlength="6" required><br>

    <button type="submit">Changer le mot de passe</button>

    <a class="link-Sign-up" href="Login.php">Retour a la connexion</a>
  </form>

  <div class="footer-lang">
    <a href="#">English (UK)</a> * <a href="#">Espanol (Espana)</a> *
    <a href="#">Italiano</a> * <a href="#">Deutsch</a>
  </div>
</body>
</html>
