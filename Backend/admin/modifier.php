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

$stmt = $pdo->prepare("SELECT *, COALESCE(product_type, type_product) AS product_type FROM produits WHERE id = ?");
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
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 28px 16px;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: #1d2939;
            background:
                linear-gradient(135deg, rgba(15, 109, 223, 0.08), transparent 28%),
                linear-gradient(315deg, rgba(22, 131, 77, 0.07), transparent 24%),
                #f5f7fb;
        }

        .panel {
            max-width: 520px;
            margin: 0 auto;
            padding: 18px;
            border: 1px solid #d9e2ef;
            border-radius: 14px;
            background: #fff;
            box-shadow: 0 10px 24px rgba(16, 24, 40, 0.07);
        }

        h1 {
            margin: 0 0 14px;
            color: #101828;
            font-size: 24px;
            letter-spacing: 0;
        }

        form {
            display: grid;
            gap: 9px;
        }

        input,
        select {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            border: 1px solid #d9e2ef;
            border-radius: 10px;
            color: #1d2939;
            background: #fff;
            outline: none;
        }

        input[type="file"] {
            background: #f8fbff;
        }

        input:focus,
        select:focus {
            border-color: #0f6ddf;
            box-shadow: 0 0 0 4px rgba(15, 109, 223, 0.12);
        }

        button,
        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 0 12px;
            border: 1px solid transparent;
            border-radius: 8px;
            text-decoration: none;
            background: #0f6ddf;
            color: #fff;
            cursor: pointer;
            font-weight: 800;
        }

        .back-link {
            width: fit-content;
            background: #0f6ddf;
            margin-top: 10px;
        }

        button:hover,
        .back-link:hover {
            background: #0a56b2;
        }

        .preview {
            width: 100%;
            aspect-ratio: 16 / 9;
            object-fit: cover;
            border: 1px solid #edf1f7;
            border-radius: 10px;
            margin-bottom: 10px;
            background: #f8fbff;
        }

        .hint {
            margin: 0;
            color: #667085;
            font-size: 13px;
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
            <select name="type" required>
                <option value="PC" <?= ($product['product_type'] ?? 'General') === 'PC' ? 'selected' : '' ?>>PC</option>
                <option value="Laptop" <?= ($product['product_type'] ?? 'General') === 'Laptop' ? 'selected' : '' ?>>Laptop</option>
                <option value="PC-Gamer" <?= ($product['product_type'] ?? 'General') === 'PC-Gamer' ? 'selected' : '' ?>>PC-Gamer</option>
                <option value="CPU" <?= ($product['product_type'] ?? 'General') === 'CPU' ? 'selected' : '' ?>>CPU</option>
                <option value="GPU" <?= ($product['product_type'] ?? 'General') === 'GPU' ? 'selected' : '' ?>>GPU</option>
                <option value="Ram" <?= ($product['product_type'] ?? 'General') === 'Ram' ? 'selected' : '' ?>>Ram</option>
                <option value="Disque" <?= ($product['product_type'] ?? 'General') === 'Disque' ? 'selected' : '' ?>>Disque</option>
                <option value="Alimentation" <?= ($product['product_type'] ?? 'General') === 'Alimentation' ? 'selected' : '' ?>>Alimentation</option>
                <option value="Clavier" <?= ($product['product_type'] ?? 'General') === 'Clavier' ? 'selected' : '' ?>>Clavier</option>
                <option value="Souris" <?= ($product['product_type'] ?? 'General') === 'Souris' ? 'selected' : '' ?>>Souris</option>
                <option value="Ecran" <?= ($product['product_type'] ?? 'General') === 'Ecran' ? 'selected' : '' ?>>Ecran</option>
                
            </select>
            <input type="file" name="image" accept="image/*">
            <p class="hint">Laissez l'image vide pour garder l'image actuelle.</p>
            <button type="submit">Enregistrer</button>
        </form>

        <a class="back-link" href="admin.php">Retour admin</a>
    </div>
</body>
</html>
