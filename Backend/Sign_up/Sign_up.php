<!doctype html>
<html lang="fr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Créer un compte BuyEase - Démo</title>
  <link rel="stylesheet" href="Sign.css">
  
</head>

<body style="background-color:  #D3D3D3;">
  <div class="container">
    <nav class="logo">
      <a href="../../index.php">
        <img src="../../img/logo_pp.png" alt="BuyEase Logo" width="300" />
      </a>
    </nav>

    <div class="card">
      <h1>Créer un compte</h1>
      <p class="subtitle">C'est simple et rapide.</p>

      <form action="Sign-ver.php" method="post" enctype="multipart/form-data">

      

        <div class="grid-row">
          <div class="col">
            <input type="text" name="nom" placeholder="Prénom" required>
          </div>
          
        </div>

        
        

        <input type="email" name="email" placeholder="enter your e-mail" required>

        <div style="margin-top:12px;">
          <input type="password" name="mcd" placeholder="Entrer votre mot de passe" required>
        </div>
        <div style="margin-top:12px;">
          <input type="file" name="image" placeholder="Ajouter une image de profil" required>
        </div>
        

        <button class="btn" type="submit">S'inscrire</button>

        <a class="link-login" href="../login/Login.php">Vous avez déjà un compte ?</a>

      </form>
    </div>

    <div class="footer-lang">
      <a href="#">English (UK)</a> •
      <a href="#">Español (España)</a> •
      <a href="#">Italiano</a> •
      <a href="#">Deutsch</a>
    </div>
  </div>
</body>

</html>