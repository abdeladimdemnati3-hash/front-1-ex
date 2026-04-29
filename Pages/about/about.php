<?php
require_once __DIR__ . '/../includes/page-bootstrap.php';
$rootPrefix = '../../';
$activePage = 'about';
$showSearch = false;
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>A propos de BuyEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="../../index-style.css" rel="stylesheet" />
    <link href="about.css" rel="stylesheet" />
  </head>
  <body>
    <div class="main-container">
      <?php require __DIR__ . '/../includes/site-header.php'; ?>

      <main>
        <section class="page-panel about-content">
          <h1>Notre Mission : La Puissance accessible a tous</h1>
          <p>
            <strong>BuyEase Pc</strong> a ete fonde sur une conviction simple :
            l'acces a l'excellence du gaming ne devrait pas etre complique.
            Notre equipe, composee de passionnes de hardware et de professionnels
            de l'assemblage, travaille chaque jour pour selectionner les meilleurs
            composants et construire des machines qui repoussent les limites de la
            performance.
          </p>

          <h2>Pourquoi nous choisir ?</h2>
          <p>
            Nous ne vendons pas seulement des PC, nous vendons la tranquillite
            d'esprit et l'assurance d'une experience de jeu ultra-fluide. Chaque
            unite est soumise a des tests de stress rigoureux pour garantir une
            stabilite maximale, meme sous les charges les plus lourdes. Nous croyons
            en la transparence, l'innovation constante et un service client qui
            comprend vraiment le monde du gaming.
          </p>

          <h2>Notre Engagement</h2>
          <p>
            Notre engagement est double : offrir la meilleure qualite au meilleur
            prix, et s'assurer que vous etes pret a dominer chaque partie des le
            premier allumage.
          </p>

          <p class="signature">- L'equipe BuyEase Pc.</p>
        </section>
      </main>
    </div>
    <footer class="site-footer">
      <div class="footer-container">
        <p class="copyright">&copy; 2026 Setup Game</p>
      </div>
    </footer>
  </body>
</html>
