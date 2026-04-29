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

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier produit</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 30px 16px;
            text-align: center;
        }

        .panel {
            max-width: 520px;
            margin: 0 auto;
            background: #fff;
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
        }

        h1 {
            color: #007bff;
            margin-top: 0;
        }

        form {
            display: grid;
            gap: 12px;
        }

        input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #d8e0ea;
            border-radius: 10px;
            box-sizing: border-box;
        }

        button,
        .back-link {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 999px;
            border: none;
            text-decoration: none;
            background: #007bff;
            color: #fff;
            cursor: pointer;
        }

        .back-link {
            background: #374151;
            margin-top: 12px;
        }

        .preview {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 12px;
            margin-bottom: 8px;
        }

        .hint {
            margin: 0;
            color: #5b6b7d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Modifier le produit</h1>

        <?php if (!empty($product['image'])): ?>
            <img class="preview" src="../../img/<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['nom']) ?>">
        <?php endif; ?>

        <form action="update-product.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= (int)$product['id'] ?>">
            <input type="hidden" name="current_image" value="<?= htmlspecialchars($product['image'] ?? '') ?>">
            <input type="text" name="nom" value="<?= htmlspecialchars($product['nom']) ?>" required>
            <input type="number" step="0.01" min="0.01" name="prix" value="<?= htmlspecialchars($product['prix']) ?>" required>
            <input type="file" name="image" accept="image/*">
            <p class="hint">Laissez l'image vide pour garder l'image actuelle.</p>
            <button type="submit">Enregistrer</button>
        </form>

        <a class="back-link" href="admin.php">Retour admin</a>
    </div>
</body>
</html>
