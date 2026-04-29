<?php
require_once __DIR__ . '/../includes/page-bootstrap.php';
$rootPrefix = '../../';
$activePage = 'services';
$showSearch = false;
?>
<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Nos Services et Produits - BuyEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link rel="stylesheet" href="../../index-style.css" />
    <link rel="stylesheet" href="Services.css" />
  </head>
  <body>
    <div class="main-container">
      <?php require __DIR__ . '/../includes/site-header.php'; ?>

      <main>
        <section class="page-panel services-content">
          <div class="content-block">
            <h2>Nos Services et Produits Phares</h2>
            <p class="services-intro">
              Chez BuyEase Pc, nous vous offrons une gamme complete pour propulser
              votre experience de jeu. Voici ce que nous proposons.
            </p>

            <div class="services-grid">
              <div class="service-card">
                <h3>PC Gaming Sur Mesure</h3>
                <p>
                  Construisez la machine de vos reves. Choisissez chaque composant,
                  du CPU a la carte graphique, et notre equipe s'occupe de
                  l'assemblage professionnel et des tests de performance.
                </p>
              </div>

              <div class="service-card">
                <h3>Vente de Composants</h3>
                <p>
                  Accedez aux dernieres cartes graphiques, processeurs et kits RAM
                  avec une selection adaptee aux standards Gaming actuels.
                </p>
              </div>

              <div class="service-card">
                <h3>Optimisation et SAV</h3>
                <p>
                  Nous proposons maintenance, depannage logiciel et optimisation de
                  votre systeme pour garantir des performances constantes.
                </p>
              </div>
            </div>

            <div class="category-block">
              <h3>Nos Categories de Produits</h3>
              <ul class="category-list">
                <li><i class="fas fa-laptop-code"></i> PC Gaming</li>
                <li><i class="fas fa-keyboard"></i> Peripheriques</li>
                <li><i class="fas fa-headphones"></i> Composants</li>
              </ul>
            </div>
          </div>
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
