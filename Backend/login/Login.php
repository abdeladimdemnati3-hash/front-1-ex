<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>BuyEase</title>
  <link href="login.css" rel="stylesheet">    
  
</head>
<body>
  <div class="im">
    <a href="../../index.php">
    <img src="../../img/logo_pp.png" alt="login image" width="200px" />
    </a></div>

  <form class="form" action="login-ver.php" method="post">
    <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
      <p class="success-message">Mot de passe change avec succes. Vous pouvez vous connecter.</p>
    <?php endif; ?>
    <?php if (isset($_GET['signup']) && $_GET['signup'] === 'success'): ?>
      <p class="success-message">Compte cree avec succes. Vous pouvez vous connecter.</p>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
      <p class="error-message">
        <?php
          $messages = [
            'missing' => 'Email et mot de passe obligatoires.',
            'invalid' => 'Adresse email invalide.',
            'incorrect' => 'Email ou mot de passe incorrect.'
          ];
          echo htmlspecialchars($messages[$_GET['error']] ?? 'Erreur de connexion.');
        ?>
      </p>
    <?php endif; ?>

    <input type="email" name="email" placeholder="Email" required><br>

    <input type="password" name="password" placeholder="Password" required><br>
    <button type="submit">Login</button>

    
    <a class="link-Sign-up" href="../Sign_up/Sign_up.php">Vous n'avez pas de compte ?</a>
  </form>

  <div class="footer-lang">
    <a href="#">English (UK)</a> • <a href="#">Español (España)</a> •
    <a href="#">Italiano</a> • <a href="#">Deutsch</a>
  </div>
</body>
</html>
