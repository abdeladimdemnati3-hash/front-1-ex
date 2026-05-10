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
    "CREATE TABLE IF NOT EXISTS contact (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nom VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('unread', 'read', 'replied') DEFAULT 'unread'
    )"
);

try {
    $pdo->exec("ALTER TABLE contact ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (Exception $e) {
}

try {
    $pdo->exec("ALTER TABLE contact ADD COLUMN status ENUM('unread','read','replied') DEFAULT 'unread'");
} catch (Exception $e) {
}

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

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (Exception $e) {
}

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
} catch (Exception $e) {
}

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
        type_product VARCHAR(100) DEFAULT 'General',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )"
);

try {
    $pdo->exec("ALTER TABLE produits ADD COLUMN IF NOT EXISTS type_product VARCHAR(100) DEFAULT 'General'");
} catch (Exception $e) {
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact_status'])) {
    $contactId = (int)$_POST['contact_id'];
    $newStatus = $_POST['contact_status'];
    $updateSql = "UPDATE contact SET status = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$newStatus, $contactId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_contact'])) {
    $contactId = (int)$_POST['contact_id'];
    $deleteSql = "DELETE FROM contact WHERE id = ?";
    $deleteStmt = $pdo->prepare($deleteSql);
    $deleteStmt->execute([$contactId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['order_status'];
    $updateSql = "UPDATE orders SET order_status = ? WHERE id = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$newStatus, $orderId]);
}

$view = $_GET['view'] ?? 'products';

$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

if ($view === 'orders') {
    try {
        $ordersSql = "SELECT * FROM orders ORDER BY created_at DESC";
        $ordersStmt = $pdo->prepare($ordersSql);
        $ordersStmt->execute();
        $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $orders = [];
    }
} elseif ($view === 'contacts') {
    $contactsSql = "SELECT * FROM contact ORDER BY created_at DESC";
    $contactsStmt = $pdo->prepare($contactsSql);
    $contactsStmt->execute();
    $contacts = $contactsStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    if ($search) {
        $sql = "SELECT *, COALESCE(product_type, type_product) AS product_type FROM produits 
                WHERE id LIKE ? 
                OR nom LIKE ? 
                OR prix LIKE ? 
                OR image LIKE ? ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            "%$search%",
            "%$search%",
            "%$search%",
            "%$search%"
        ]);
    } else {
        $sql="SELECT *, COALESCE(product_type, type_product) AS product_type FROM produits";
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
            max-width: 1600px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(5, minmax(220px, 1fr));
            gap: 58px;
            padding: 0 12px 24px;
            align-items: stretch;
            margin-top: 20px;
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
            background: #ffffff;
            width: 100%;
            min-height: 340px;
            padding: 18px;
            border-radius: 22px;
            border: 1px solid rgba(15, 23, 42, 0.08);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 24px 45px rgba(15, 23, 42, 0.14);
        }

        img {
            width: 100%;
            height: 160px;
            border-radius: 18px;
            object-fit: cover;
            margin-bottom: 16px;
        }

        .card h3 {
            margin: 0 0 10px;
            font-size: 1.3rem;
            letter-spacing: 0.02em;
            color: #111827;
        }

        .card p {
            margin: 8px 0;
            color: #4b5563;
            line-height: 1.5;
        }

        .card p strong {
            color: #111827;
        }

        .actions {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            align-items: center;
        }

        .actions a,
        .actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 1 1 130px;
            min-width: 130px;
            max-width: 100%;
            padding: 12px 18px;
            border-radius: 999px;
            text-decoration: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 700;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background-color 0.18s ease;
            border: 1px solid transparent;
            background: #fff5f5;
            color: #b91c1c;
            box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.12);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .actions a:hover,
        .actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(15, 23, 42, 0.12);
            background: #fde8e8;
        }

        .actions a:focus,
        .actions button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.22);
        }

        .edit,
        .delete {
            background: #fff5f5;
            color: #b91c1c;
            border-color: rgba(220, 38, 38, 0.18);
        }

        @media (max-width: 1400px) {
            .container {
                grid-template-columns: repeat(4, minmax(220px, 1fr));
            }
        }

        @media (max-width: 1080px) {
            .container {
                grid-template-columns: repeat(3, minmax(220px, 1fr));
            }
        }

        @media (max-width: 820px) {
            .container {
                grid-template-columns: repeat(2, minmax(220px, 1fr));
            }
        }

        @media (max-width: 620px) {
            .container {
                grid-template-columns: 1fr;
            }
        }

        .flash {
            display: inline-block;
            margin: 12px 0;
            padding: 10px 14px;
            border-radius: 10px;
            background: #eaf8ef;
            border: 1px solid #c8ebd2;
            color: #1c7b39;
        }

        .error-flash {
            background: #fef1f0;
            border-color: #f9d4ce;
            color: #c41e3a;
        }

        .error-flash p {
            margin: 5px 0;
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

        /* Contacts Section Styles */
        .contacts-section {
            margin: 20px auto;
            max-width: 1000px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .contacts-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .contact-card {
            border: 1px solid #d8e0ea;
            border-radius: 12px;
            background: #f9fbfd;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .contact-card:hover {
            box-shadow: 0 4px 12px rgba(15, 109, 223, 0.1);
            border-color: #0f6ddf;
        }

        .contact-card.status-unread {
            border-left: 4px solid #ff9800;
        }

        .contact-card.status-read {
            border-left: 4px solid #4caf50;
        }

        .contact-card.status-replied {
            border-left: 4px solid #2196f3;
        }

        .contact-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px;
            background: white;
            border-bottom: 1px solid #e7edf5;
            flex-wrap: wrap;
            gap: 12px;
        }

        .contact-info h3 {
            margin: 0 0 6px;
            color: #1e293b;
            font-size: 16px;
        }

        .contact-email,
        .contact-date {
            margin: 4px 0;
            color: #5b6b7d;
            font-size: 13px;
        }

        .contact-email i,
        .contact-date i {
            margin-right: 6px;
            width: 14px;
        }

        .contact-status {
            flex-shrink: 0;
        }

        .contact-body {
            padding: 16px;
        }

        .contact-message {
            margin: 0;
            color: #1e293b;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .contact-actions {
            padding: 16px;
            background: white;
            border-top: 1px solid #e7edf5;
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-select {
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 13px;
        }

        .delete-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
        }

        .delete-btn:hover {
            background: #c82333;
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
    <a href="?view=contacts" class="<?= ($view === 'contacts') ? 'active' : '' ?>">Messages</a>
</div>

<?php if ($status === 'added'): ?>
    <p class="flash">Produit ajoute avec succes.</p>
<?php elseif ($status === 'updated'): ?>
    <p class="flash">Produit mis a jour avec succes.</p>
<?php elseif ($status === 'deleted'): ?>
    <p class="flash">Produit supprime avec succes.</p>
<?php endif; ?>

<?php if (!empty($_SESSION['product_errors'])): ?>
    <div class="flash error-flash">
        <?php foreach ($_SESSION['product_errors'] as $error): ?>
            <p>❌ <?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
    </div>
    <?php unset($_SESSION['product_errors']); ?>
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

<?php elseif ($view === 'orders'): ?>
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
                        <?php
                            $orderDate = $order['created_at'] ?? null;
                            $orderDateStr = $orderDate ? date('d/m/Y H:i', strtotime($orderDate)) : 'Date non disponible';
                        ?>
                        <tr>
                            <td><strong><?= (int)$order['id'] ?></strong></td>
                            <td><?= htmlspecialchars($order['user_name']) ?></td>
                            <td><?= htmlspecialchars($order['user_email']) ?></td>
                            <td><?= number_format((float)$order['total_amount'], 2) ?></td>
                            <td>
                                <span class="status-<?= strtolower($order['order_status'] ?? 'pending') ?>">
                                    <?= htmlspecialchars($order['order_status'] ?? 'Pending') ?>
                                </span>
                            </td>
                            <td><?= $orderDateStr ?></td>
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

<?php elseif ($view === 'contacts'): ?>
    <!-- CONTACTS SECTION -->
    <div class="contacts-section">
        <h2>Messages de Contact</h2>
        
        <?php if (empty($contacts)): ?>
            <p style="text-align: center; color: #999;">Aucun message de contact pour le moment.</p>
        <?php else: ?>
            <div class="contacts-list">
                <?php foreach ($contacts as $contact): ?>
                    <?php
                        $contactStatus = $contact['status'] ?? 'unread';
                        $contactCreatedAt = $contact['created_at'] ?? null;
                    ?>
                    <div class="contact-card status-<?= htmlspecialchars($contactStatus) ?>">
                        <div class="contact-header">
                            <div class="contact-info">
                                <h3>Message de <?= htmlspecialchars($contact['nom']) ?></h3>
                                <p class="contact-email"><i class="fas fa-envelope"></i> <?= htmlspecialchars($contact['email']) ?></p>
                                <p class="contact-date"><i class="fas fa-calendar"></i> <?= $contactCreatedAt ? date('d/m/Y H:i', strtotime($contactCreatedAt)) : 'Date non disponible' ?></p>
                            </div>
                            <div class="contact-status">
                                <span class="status-badge status-<?= htmlspecialchars($contactStatus) ?>">
                                    <?php 
                                        $statusText = [
                                            'unread' => 'Non lu',
                                            'read' => 'Lu',
                                            'replied' => 'Répondu'
                                        ];
                                        echo $statusText[$contactStatus] ?? htmlspecialchars($contactStatus);
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="contact-body">
                            <p class="contact-message"><?= nl2br(htmlspecialchars($contact['message'])) ?></p>
                        </div>

                        <div class="contact-actions">
                            <form action="" method="POST" style="display: inline;">
                                <input type="hidden" name="contact_id" value="<?= (int)$contact['id'] ?>">
                                <input type="hidden" name="update_contact_status" value="1">
                                <select name="contact_status" class="status-select" onchange="this.form.submit()">
                                    <option value="unread" <?= ($contactStatus === 'unread') ? 'selected' : '' ?>>Non lu</option>
                                    <option value="read" <?= ($contactStatus === 'read') ? 'selected' : '' ?>>Lu</option>
                                    <option value="replied" <?= ($contactStatus === 'replied') ? 'selected' : '' ?>>Répondu</option>
                                </select>
                            </form>
                            
                            <form action="" method="POST" style="display: inline;" onsubmit="return confirm('Supprimer ce message ?')">
                                <input type="hidden" name="contact_id" value="<?= (int)$contact['id'] ?>">
                                <input type="hidden" name="delete_contact" value="1">
                                <button type="submit" class="delete-btn">Supprimer</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

<?php endif; ?>

</body>
</html>