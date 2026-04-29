<?php $added = isset($_GET['added']); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produit CPU - BuyEase</title>
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
      
     
     body {
      font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text);
      background: radial-gradient(circle at top right, #deedff 0%, var(--bg) 40%, #eef2f7 100%);
      padding: 20px 0;
      margin: 0;
    }

    .main-container {
      max-width: 1140px;
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
      flex-direction: column;
      align-items: center;
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
        align-items: center;
        gap: 10px;
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
            <input type="hidden" name="redirect" value="../Pages/PRO.php">
            <input type="number" name="quantity" class="num" placeholder="Qte" min="1" value="1" required>
            <button type="submit" class="buy">Ajouter au panier</button>
          </form>
        </div>
      </div>
    </div>
  </main>
      <main class="review-section">
        <form action="#" method="post" class="review-form">
          <h1 style=" text-align:center; color: #007bff">Product Reviews</h1>
          <div style="align-items: center; text-align:center">
            <h2>Votre note :</h2>
          <label>
            <input type="radio" name="rating" value="1" /> 1⭐
          </label>
          <label>
            <input type="radio" name="rating" value="2" /> 2⭐
          </label>
          <label>
            <input type="radio" name="rating" value="3" /> 3⭐
          </label>
          <label>
            <input type="radio" name="rating" value="4" /> 4⭐
          </label>
          <label>
            <input type="radio" name="rating" value="5" /> 5⭐
          </label>

          </div>
          <h4 style="text-align: center; color:black ">Votre avis :</h4>
          <textarea placeholder="Ecrire un commentaire..."></textarea><br /><br />
          <div style="text-align: center;"><input type="submit" value="Envoyer" class="review-submit" /></div><br /><br />
      </main>
</body>
</html>