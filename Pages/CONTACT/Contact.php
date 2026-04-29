<?php
require_once __DIR__ . '/../includes/page-bootstrap.php';
$rootPrefix = '../../';
$activePage = 'contact';
$showSearch = false;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact - BuyEase</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../index-style.css">
  <link rel="stylesheet" href="Contact.css">
</head>
<body class="contact-page">
  <div class="main-container">
    <?php require __DIR__ . '/../includes/site-header.php'; ?>

    <main>
      <section class="page-panel contact-shell">
        <div class="content-block contact-intro">
          <h2>Contactez-nous</h2>
          <p>Envoyez-nous votre message, nous repondons rapidement.</p>
        </div>

        <div class="contact-form-container">
          <form action="verContact.php" method="POST" class="contact-form">
            <div class="form-group">
              <label>Nom</label>
              <input type="text" name="nom" placeholder="Votre nom" required>
            </div>

            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" placeholder="Votre email" required>
            </div>

            <div class="form-group">
              <label>Message</label>
              <textarea name="message" rows="6" placeholder="Votre message..." required></textarea>
            </div>

            <button type="submit" class="submit-button">Envoyer le Message</button>
          </form>
        </div>
      </section>
    </main>
  </div>

  <footer class="site-footer">
    <p>&copy; 2026 BuyEase Pc</p>
  </footer>
</body>
</html>
