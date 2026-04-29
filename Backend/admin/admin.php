<?php

session_start();
include "../../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../login/Login.php");
    exit();
}

$typeAdmin = $_SESSION['user']['type_admin'] ?? $_SESSION['user']['TYPE_ADMIN'] ?? 'N';
if ($typeAdmin !== 'A') {
    header("Location: ../../index.php");
    exit();
}

$nomAdmin = $_SESSION['user']['NOM'] ?? $_SESSION['user']['nom'] ?? 'Admin';

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS produits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);


$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

if ($search) {
    $sql = "SELECT * FROM produits 
            WHERE id LIKE ? 
            OR nom LIKE ? 
            OR prix LIKE ? 
            OR image LIKE ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        "%$search%",
        "%$search%",
        "%$search%",
        "%$search%"
    ]);
} else {
    $sql="SELECT * FROM produits";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel</title>

    <style>
        body {
            font-family: Arial;
            background: #f4f6f9;
            text-align: center;
        }

        h1 {
            color: #007bff;
        }

        form {
            margin: 20px;
        }

        input, button {
            padding: 8px;
            margin: 5px;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }

        .top-links {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top-links a {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 999px;
            background: #1f2937;
            color: white;
            text-decoration: none;
        }

        .top-links a:hover {
            background: #007bff;
        }

        .card {
            background: white;
            width: 260px;
            padding: 15px;
            margin: 15px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: 0.3s;
        }

        .card:hover {
            transform: translateY(-10px);
        }

        img {
            width: 100%;
            height: 160px;
            border-radius: 10px;
            object-fit: cover;
        }

        .actions {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }

        .actions a,
        .actions button {
            margin: 5px;
            text-decoration: none;
            cursor: pointer;
            background: transparent;
            border: none;
            font-size: 15px;
        }

        .delete { color: red; }
        .edit { color: #0a7b45; }

        .flash {
            display: inline-block;
            margin: 12px 0;
            padding: 10px 14px;
            border-radius: 10px;
            background: #eaf8ef;
            border: 1px solid #c8ebd2;
            color: #1c7b39;
        }
    </style>
</head>

<body>

<h1>Salut Admin <?= htmlspecialchars($nomAdmin) ?></h1>

<div class="top-links">
    <a href="../../index.php">Home</a>
    <a href="../../Pages/profile.php">Profile</a>
    <a href="../login/logout.php">Logout</a>
</div>

<?php if ($status === 'added'): ?>
    <p class="flash">Produit ajoute avec succes.</p>
<?php elseif ($status === 'updated'): ?>
    <p class="flash">Produit mis a jour avec succes.</p>
<?php elseif ($status === 'deleted'): ?>
    <p class="flash">Produit supprime avec succes.</p>
<?php endif; ?>

<h2>Ajouter Produit</h2>

<form action="add-product.php" method="POST" enctype="multipart/form-data">
    <input type="text" name="nom" placeholder="Nom" required><br>
    <input type="text" name="prix" placeholder="Prix" required><br>
    <input type="file" name="image" required><br>
    <button type="submit">Ajouter</button>
</form>

<hr>

<form method="GET">
    <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Rechercher</button>
</form>

<h2>Produits</h2>

<div class="container">

<?php foreach ($products as $p): ?>
    <div class="card">

        <img src="../../img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">

        <h3><?= htmlspecialchars($p['nom']) ?></h3>

        <p><?= number_format((float)$p['prix'], 2) ?> MAD</p>

        <div class="actions">
            <a class="edit" href="modifier.php?id=<?= (int)$p['id'] ?>">Modifier</a>
            <form action="delete.php" method="POST" onsubmit="return confirm('Supprimer ce produit ?')">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="delete" type="submit">Supprimer</button>
            </form>
        </div>

    </div>
<?php endforeach; ?>

</div>

</body>
</html>