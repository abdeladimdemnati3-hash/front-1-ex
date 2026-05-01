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

// Create orders tables if not exist
$pdo->exec(
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        user_name VARCHAR(255) NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        order_status VARCHAR(50) DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        product_price DECIMAL(10,2) NOT NULL,
        quantity INT NOT NULL,
        subtotal DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )"
);

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS produits (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        prix DECIMAL(10,2) NOT NULL,
        image VARCHAR(255) DEFAULT NULL,
        product_type VARCHAR(100) DEFAULT 'General',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['order_status'];
    $updateSql = "UPDATE orders SET order_status = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$newStatus, $orderId]);
}

// Determine view mode
$view = $_GET['view'] ?? 'products';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

if ($view === 'orders') {
    // Get all orders
    $ordersSql = "SELECT * FROM orders ORDER BY created_at DESC";
    $ordersStmt = $pdo->prepare($ordersSql);
    $ordersStmt->execute();
    $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Get products
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
}
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

        h2 {
            margin-top: 30px;
            color: #333;
        }

        form {
            margin: 20px;
        }

        input, button, select {
            padding: 8px;
            margin: 5px;
        }

        button {
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background: #0056b3;
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

        .view-tabs {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .view-tabs a {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 5px;
            background: #e0e0e0;
            color: #333;
            text-decoration: none;
            cursor: pointer;
        }

        .view-tabs a.active {
            background: #007bff;
            color: white;
        }

        .view-tabs a:hover {
            background: #0056b3;
            color: white;
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

        /* Orders Table Styles */
        .orders-section {
            margin: 20px auto;
            max-width: 1200px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .orders-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .orders-table thead {
            background: #007bff;
            color: white;
        }

        .orders-table th,
        .orders-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .orders-table tbody tr:hover {
            background: #f5f5f5;
        }

        .orders-table .status-pending {
            color: #ff9800;
            font-weight: bold;
        }

        .orders-table .status-confirmed {
            color: #4caf50;
            font-weight: bold;
        }

        .orders-table .status-shipped {
            color: #2196f3;
            font-weight: bold;
        }

        .orders-table .status-delivered {
            color: #4caf50;
            font-weight: bold;
        }

        .orders-table .status-cancelled {
            color: #f44336;
            font-weight: bold;
        }

        .status-select {
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .order-actions {
            display: flex;
            gap: 5px;
        }

        .order-actions button {
            padding: 5px 10px;
            font-size: 12px;
        }

        .order-details-link {
            color: #007bff;
            cursor: pointer;
            text-decoration: underline;
        }

        .order-details-link:hover {
            color: #0056b3;
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

<div class="view-tabs">
    <a href="?view=products" class="<?= ($view === 'products') ? 'active' : '' ?>">Produits</a>
    <a href="?view=orders" class="<?= ($view === 'orders') ? 'active' : '' ?>">Commandes</a>
</div>

<?php if ($status === 'added'): ?>
    <p class="flash">Produit ajoute avec succes.</p>
<?php elseif ($status === 'updated'): ?>
    <p class="flash">Produit mis a jour avec succes.</p>
<?php elseif ($status === 'deleted'): ?>
    <p class="flash">Produit supprime avec succes.</p>
<?php endif; ?>

<?php if ($view === 'products'): ?>
    <!-- PRODUCTS SECTION -->
    <h2>Ajouter Produit</h2>

    <form action="add-product.php" method="POST" enctype="multipart/form-data">
        <input type="text" name="nom" placeholder="Nom" required><br>
        <input type="text" name="prix" placeholder="Prix" required><br>
        <select name="type" required>
            <option value="PC">PC</option>
            <option value="Laptop">Laptop</option>
            <option value="PC-Gamer">PC-Gamer</option>
            <option value="CPU">CPU</option>
            <option value="GPU">GPU</option>
        </select><br>
        <input type="file" name="image" required><br>
        <button type="submit">Ajouter</button>
    </form>

    <hr>

    <form method="GET">
        <input type="hidden" name="view" value="products">
        <input type="text" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit">Rechercher</button>
    </form>

    <h2>Produits</h2>

    <div class="container">

    <?php foreach ($products as $p): ?>
        <div class="card">

            <img src="../../img/<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['nom']) ?>">

            <h3><?= htmlspecialchars($p['nom']) ?></h3>
            <p><strong>Type:</strong> <?= htmlspecialchars($p['product_type'] ?? 'General') ?></p>
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

<?php else: ?>
    <!-- ORDERS SECTION -->
    <div class="orders-section">
        <h2>Commandes des Utilisateurs</h2>
        
        <?php if (empty($orders)): ?>
            <p style="text-align: center; color: #999;">Aucune commande pour le moment.</p>
        <?php else: ?>
            <table class="orders-table">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Nom Client</th>
                        <th>Email</th>
                        <th>Total (MAD)</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?= (int)$order['id'] ?></strong></td>
                            <td><?= htmlspecialchars($order['user_name']) ?></td>
                            <td><?= htmlspecialchars($order['user_email']) ?></td>
                            <td><?= number_format((float)$order['total_amount'], 2) ?></td>
                            <td>
                                <span class="status-<?= strtolower($order['order_status']) ?>">
                                    <?= htmlspecialchars($order['order_status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <form action="" method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                                    <input type="hidden" name="update_order_status" value="1">
                                    <select name="order_status" class="status-select" onchange="this.form.submit()">
                                        <option value="Pending" <?= ($order['order_status'] === 'Pending') ? 'selected' : '' ?>>En attente</option>
                                        <option value="Confirmed" <?= ($order['order_status'] === 'Confirmed') ? 'selected' : '' ?>>Confirmée</option>
                                        <option value="Shipped" <?= ($order['order_status'] === 'Shipped') ? 'selected' : '' ?>>Expediée</option>
                                        <option value="Delivered" <?= ($order['order_status'] === 'Delivered') ? 'selected' : '' ?>>Livree</option>
                                        <option value="Cancelled" <?= ($order['order_status'] === 'Cancelled') ? 'selected' : '' ?>>Annulee</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php endif; ?>

</body>
</html>