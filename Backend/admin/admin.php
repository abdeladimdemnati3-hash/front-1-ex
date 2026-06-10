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

try {
    $pdo->exec("ALTER TABLE orders ADD COLUMN order_number VARCHAR(40) DEFAULT NULL");
} catch (Exception $e) {
}

try {
    $pdo->exec("UPDATE orders SET order_number = CONCAT('CMD-', DATE_FORMAT(created_at, '%Y%m%d'), '-', LPAD(id, 6, '0')) WHERE order_number IS NULL OR order_number = ''");
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

try {
    $pdo->exec("ALTER TABLE produits ADD COLUMN product_type VARCHAR(100) DEFAULT 'General'");
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
$orders = [];
$ordersPerPage = 5;
$ordersCurrentPage = max(1, (int)($_GET['page'] ?? 1));
$ordersTotal = 0;
$ordersTotalPages = 1;

if ($view === 'orders') {
    try {
        $ordersCountStmt = $pdo->query("SELECT COUNT(*) FROM orders");
        $ordersTotal = (int)$ordersCountStmt->fetchColumn();
        $ordersTotalPages = max(1, (int)ceil($ordersTotal / $ordersPerPage));

        if ($ordersCurrentPage > $ordersTotalPages) {
            $ordersCurrentPage = $ordersTotalPages;
        }

        $ordersOffset = ($ordersCurrentPage - 1) * $ordersPerPage;
        $ordersSql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT " . (int)$ordersPerPage . " OFFSET " . (int)$ordersOffset;
        $ordersStmt = $pdo->prepare($ordersSql);
        $ordersStmt->execute();
        $orders = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $orders = [];
        $ordersTotal = 0;
        $ordersTotalPages = 1;
        $ordersCurrentPage = 1;
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
        $sql = "SELECT *, COALESCE(product_type, type_product) AS product_type FROM produits";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }

    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - BuyEase</title>

    <style>
        :root {
            --bg: #f5f7fb;
            --panel: #ffffff;
            --panel-soft: #f8fbff;
            --text: #1d2939;
            --muted: #667085;
            --line: #d9e2ef;
            --primary: #0f6ddf;
            --primary-dark: #0a56b2;
            --success: #16834d;
            --warning: #b76a00;
            --danger: #d92d20;
            --shadow: 0 10px 24px rgba(16, 24, 40, 0.07);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 100%;
            overflow-x: hidden;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text);
            background:
                linear-gradient(135deg, rgba(15, 109, 223, 0.08), transparent 28%),
                linear-gradient(315deg, rgba(22, 131, 77, 0.07), transparent 24%),
                var(--bg);
        }

        a {
            color: inherit;
        }

        button,
        input,
        select {
            font: inherit;
        }

        h1,
        h2,
        h3,
        p {
            margin-top: 0;
        }

        .admin-shell {
            width: min(1180px, calc(100% - 28px));
            margin: 0 auto;
            padding: 18px 0 30px;
        }

        .admin-hero {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 14px;
            align-items: center;
            padding: 16px 18px;
            border: 1px solid rgba(15, 109, 223, 0.14);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.92);
            box-shadow: var(--shadow);
        }

        .admin-eyebrow {
            margin: 0 0 4px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        h1 {
            margin-bottom: 5px;
            font-size: clamp(22px, 3vw, 32px);
            line-height: 1.1;
            letter-spacing: 0;
        }

        .admin-subtitle {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.45;
        }

        .top-links,
        .view-tabs {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .top-links {
            justify-content: flex-end;
        }

        .top-links a,
        .view-tabs a,
        button,
        .edit {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0 20px;
            border: 1px solid transparent;
            border-radius: 10px;
            text-decoration: none;
            font-size: 16px;
            font-weight: 900;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }

        button {
            color: #fff;
            background: var(--primary);
        }

        .admin-top-btn {
            color: #fff;
            min-width: 78px;
            height: 38px;
            padding: 0 13px;
            border-radius: 15px;
            font-size: 16px;
            line-height: 1;
            box-shadow: 0 8px 18px rgba(16, 24, 40, 0.10);
        }

        .admin-home-btn {
            background: #2c3e50;
            border-color: #2c3e50;
        }

        .admin-profile-btn {
            min-width: 82px;
            background: #355c9b;
            border-color: #355c9b;
        }

        .admin-logout-btn {
            min-width: 82px;
            background: #b73a3a;
            border-color: #b73a3a;
        }

        .top-links a:hover,
        .view-tabs a:hover,
        button:hover,
        .edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 22px rgba(16, 24, 40, 0.14);
        }

        .admin-home-btn:hover,
        button:hover {
            background: #1f3245;
            border-color: #1f3245;
        }

        .admin-profile-btn:hover {
            background: #2b4f8c;
            border-color: #2b4f8c;
        }

        .admin-logout-btn:hover {
            background: #9f3030;
            border-color: #9f3030;
        }

        .view-tabs {
            margin: 12px 0;
            padding: 6px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.75);
        }

        .view-tabs a {
            flex: 1 1 160px;
            color: var(--muted);
            background: transparent;
        }

        .view-tabs a.active {
            color: #fff;
            background: var(--primary);
            box-shadow: 0 10px 22px rgba(15, 109, 223, 0.18);
        }

        .flash {
            display: block;
            width: fit-content;
            max-width: 100%;
            margin: 10px 0;
            padding: 10px 12px;
            border-radius: 10px;
            background: #ecfdf3;
            border: 1px solid #bee8cf;
            color: var(--success);
            font-weight: 800;
        }

        .error-flash {
            background: #fff1f0;
            border-color: #ffd0cc;
            color: var(--danger);
        }

        .error-flash p {
            margin: 4px 0;
        }

        .products-layout {
            display: grid;
            grid-template-columns: minmax(250px, 320px) 1fr;
            gap: 14px;
            align-items: start;
        }

        .panel,
        .orders-section,
        .contacts-section {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: var(--panel);
            box-shadow: var(--shadow);
        }

        .panel-header {
            padding: 14px 14px 0;
        }

        .panel-header h2 {
            margin-bottom: 4px;
            font-size: 18px;
        }

        .panel-header p {
            margin-bottom: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.4;
        }

        .product-form,
        .search-form {
            display: grid;
            gap: 9px;
            padding: 14px;
        }

        .field {
            display: grid;
            gap: 5px;
        }

        .field label {
            color: #344054;
            font-size: 12px;
            font-weight: 800;
        }

        input,
        select {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            border: 1px solid var(--line);
            border-radius: 10px;
            background: #fff;
            color: var(--text);
            outline: none;
            transition: border-color 0.18s ease, box-shadow 0.18s ease;
        }

        input[type="file"] {
            padding: 7px;
            background: var(--panel-soft);
        }

        input:focus,
        select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(15, 109, 223, 0.12);
        }

        .search-row {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 8px;
        }

        .search-tools {
            margin-bottom: 12px;
        }

        .products-head {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: end;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }

        .products-head h2 {
            margin-bottom: 3px;
            font-size: 20px;
        }

        .products-count {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            font-weight: 700;
        }

        .container {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }

        .card {
            min-width: 0;
            padding: 10px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 8px 18px rgba(16, 24, 40, 0.06);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px rgba(16, 24, 40, 0.10);
        }

        .product-image {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 10;
            height: auto;
            border-radius: 9px;
            object-fit: contain;
            background: #ffffff;
            border: 1px solid #eef2f7;
        }

        .card-body {
            display: grid;
            gap: 7px;
            padding-top: 10px;
        }

        .product-title {
            margin: 0;
            min-height: 38px;
            color: #101828;
            font-size: 14px;
            line-height: 1.35;
            overflow-wrap: anywhere;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            align-items: center;
        }

        .type-pill {
            max-width: 55%;
            padding: 4px 8px;
            border-radius: 999px;
            color: var(--primary-dark);
            background: #eaf3ff;
            font-size: 11px;
            font-weight: 900;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .price {
            color: #101828;
            font-size: 14px;
            font-weight: 900;
            white-space: nowrap;
        }

        .actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 4px;
        }

        .actions form {
            margin: 0;
        }

        .actions a,
        .actions button {
            width: 100%;
            min-height: 34px;
            padding: 0 10px;
            border-radius: 8px;
            font-size: 13px;
        }

        .edit {
            color: var(--primary-dark);
            background: #edf5ff;
            border-color: #cfe5ff;
        }

        .edit:hover {
            color: #fff;
            background: var(--primary);
            border-color: var(--primary);
        }

        .delete,
        .delete-btn {
            color: #fff;
            background: var(--danger);
        }

        .empty-state {
            grid-column: 1 / -1;
            padding: 20px;
            border: 1px dashed var(--line);
            border-radius: 12px;
            color: var(--muted);
            background: var(--panel-soft);
            text-align: center;
            font-weight: 700;
        }

        .orders-section,
        .contacts-section {
            padding: 14px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .section-heading-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .pagination-summary {
            margin: 4px 0 10px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 700;
        }

        .admin-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 18px;
            padding-top: 16px;
            border-top: 1px solid #e7edf5;
        }

        .pagination-pages {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .pagination-link {
            min-width: 38px;
            min-height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 12px;
            border-radius: 8px;
            border: 1px solid var(--line);
            background: #ffffff;
            color: var(--text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
        }

        .pagination-link.active,
        .pagination-link:hover {
            border-color: var(--primary);
            background: var(--primary);
            color: #ffffff;
        }

        .pagination-link.disabled {
            pointer-events: none;
            opacity: 0.45;
            background: var(--panel-soft);
            color: var(--muted);
        }

        .orders-table {
            width: 100%;
            min-width: 820px;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 10px;
        }

        .orders-table th,
        .orders-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #edf1f7;
        }

        .orders-table th {
            color: #475467;
            background: var(--panel-soft);
            font-size: 13px;
            text-transform: uppercase;
        }

        .orders-table tbody tr:hover {
            background: #fbfdff;
        }

        .status-pending,
        .status-confirmed,
        .status-shipped,
        .status-delivered,
        .status-cancelled,
        .status-badge {
            display: inline-flex;
            align-items: center;
            min-height: 24px;
            padding: 0 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 900;
        }

        .status-pending,
        .status-unread {
            color: var(--warning);
            background: #fff7e8;
        }

        .status-confirmed,
        .status-delivered,
        .status-read {
            color: var(--success);
            background: #ecfdf3;
        }

        .status-shipped,
        .status-replied {
            color: var(--primary-dark);
            background: #eaf3ff;
        }

        .status-cancelled {
            color: var(--danger);
            background: #fff1f0;
        }

        .status-select {
            min-height: 34px;
            padding: 6px 8px;
            font-size: 13px;
        }

        .contacts-list {
            display: grid;
            gap: 10px;
            margin-top: 10px;
        }

        .contact-card {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: var(--panel-soft);
            overflow: hidden;
        }

        .contact-header,
        .contact-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            padding: 10px;
            background: #fff;
        }

        .contact-info h3 {
            margin-bottom: 6px;
            font-size: 16px;
        }

        .contact-email,
        .contact-date {
            margin: 4px 0;
            color: var(--muted);
            font-size: 13px;
        }

        .contact-body {
            padding: 14px;
        }

        .contact-message {
            margin: 0;
            color: #344054;
            line-height: 1.6;
            white-space: pre-wrap;
        }

        .contact-actions {
            justify-content: flex-start;
            border-top: 1px solid var(--line);
        }

        .contact-actions form {
            margin: 0;
        }

        @media (max-width: 1180px) {
            .products-layout {
                grid-template-columns: 1fr;
            }

            .container {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .admin-shell {
                width: min(100% - 18px, 1180px);
                padding-top: 10px;
            }

            .admin-hero,
            .search-row {
                grid-template-columns: 1fr;
            }

            .top-links {
                justify-content: stretch;
            }

            .top-links a,
            .view-tabs a,
            button,
            .edit {
                min-height: 40px;
                padding: 0 12px;
                font-size: 14px;
            }

            .top-links a {
                flex: 1 1 120px;
            }

            .container {
                grid-template-columns: 1fr;
            }

            .actions {
                grid-template-columns: 1fr;
            }

            .orders-section,
            .contacts-section {
                padding: 10px;
                border-radius: 12px;
            }
        }

        @media (max-width: 420px) {
            .admin-shell {
                width: min(100% - 12px, 1180px);
            }

            .admin-hero {
                padding: 12px;
            }

            .view-tabs a,
            .top-links a {
                flex-basis: 100%;
            }

            .product-meta {
                align-items: flex-start;
                flex-direction: column;
            }

            .type-pill {
                max-width: 100%;
            }
        }
    </style>
</head>

<body>
    <main class="admin-shell">

        <section class="admin-hero">
            <div>
                <p class="admin-eyebrow">BuyEase admin</p>
                <h1>Salut <?= htmlspecialchars($nomAdmin) ?></h1>
                <p class="admin-subtitle">Gerez les produits, les commandes et les messages depuis un tableau de bord propre et rapide.</p>
            </div>

            <div class="top-links">
                <a class="admin-top-btn admin-home-btn" href="../../index.php">Home</a>
                <a class="admin-top-btn admin-profile-btn" href="../../Pages/profile.php">Profile</a>
                <a class="admin-top-btn admin-logout-btn" href="../login/logout.php">Logout</a>
            </div>
        </section>

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
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
            <?php unset($_SESSION['product_errors']); ?>
        <?php endif; ?>

        <?php if ($view === 'products'): ?>
            <!-- PRODUCTS SECTION -->
            <section class="products-layout">
                <aside class="panel">
                    <div class="panel-header">
                        <h2>Ajouter produit</h2>
                        <p>Ajoutez un produit avec une image propre, un prix clair et une categorie.</p>
                    </div>

                    <form class="product-form" action="add-product.php" method="POST" enctype="multipart/form-data">
                        <div class="field">
                            <label for="nom">Nom du produit</label>
                            <input id="nom" type="text" name="nom" placeholder="Ex: PC Gamer Ryzen 5" required>
                        </div>

                        <div class="field">
                            <label for="prix">Prix en MAD</label>
                            <input id="prix" type="number" step="0.01" min="0.01" name="prix" placeholder="Ex: 5149.00" required>
                        </div>

                        <div class="field">
                            <label for="type">Categorie</label>
                            <select id="type" name="type" required>
                                <option value="PC">PC</option>
                                <option value="Laptop">Laptop</option>
                                <option value="PC-Gamer">PC-Gamer</option>
                                <option value="CPU">CPU</option>
                                <option value="GPU">GPU</option>
                                <option value="Ram">Ram</option>
                                <option value="Disque">Disque</option>
                                <option value="Alimentation">Alimentation</option>
                                <option value="Clavier">Clavier</option>
                                <option value="Souris">Souris</option>
                                <option value="Ecran">Ecran</option>
                            </select>
                        </div>

                        <div class="field">
                            <label for="image">Image du produit</label>
                            <input id="image" type="file" name="image" accept="image/*" required>
                        </div>

                        <button type="submit">Ajouter le produit</button>
                    </form>
                </aside>

                <section>
                    <div class="panel search-tools">
                        <form class="search-form" method="GET">
                            <input type="hidden" name="view" value="products">
                            <div class="search-row">
                                <input type="text" name="search" placeholder="Rechercher par nom, prix ou image..." value="<?= htmlspecialchars($search) ?>">
                                <button type="submit">Rechercher</button>
                            </div>
                        </form>
                    </div>

                    <div class="products-head">
                        <div>
                            <h2>Produits</h2>
                            <p class="products-count"><?= count($products) ?> produit<?= count($products) > 1 ? 's' : '' ?> trouve<?= count($products) > 1 ? 's' : '' ?></p>
                        </div>
                    </div>

                    <div class="container">
                        <?php if (empty($products)): ?>
                            <p class="empty-state">Aucun produit trouve. Essayez une autre recherche ou ajoutez votre premier produit.</p>
                        <?php endif; ?>

                        <?php foreach ($products as $p): ?>
                            <article class="card">
                                <img class="product-image" src="../../img/<?= htmlspecialchars($p['image'] ?: 'logo.eco.png') ?>" alt="<?= htmlspecialchars($p['nom']) ?>">

                                <div class="card-body">
                                    <h3 class="product-title"><?= htmlspecialchars($p['nom']) ?></h3>
                                    <div class="product-meta">
                                        <span class="type-pill"><?= htmlspecialchars($p['product_type'] ?? 'General') ?></span>
                                        <span class="price"><?= number_format((float)$p['prix'], 2) ?> MAD</span>
                                    </div>

                                    <div class="actions">
                                        <a class="edit" href="modifier.php?id=<?= (int)$p['id'] ?>">Modifier</a>
                                        <form action="delete.php" method="POST" onsubmit="return confirm('Supprimer ce produit ?')">
                                            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                            <button class="delete" type="submit">Supprimer</button>
                                        </form>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            </section>

        <?php elseif ($view === 'orders'): ?>
            <!-- ORDERS SECTION -->
            <div class="orders-section">
                <div class="section-heading-row">
                    <div>
                        <h2>Commandes des Utilisateurs</h2>
                        <p class="pagination-summary">
                            Page <?= (int)$ordersCurrentPage ?> / <?= (int)$ordersTotalPages ?> - <?= (int)$ordersTotal ?> commande<?= $ordersTotal > 1 ? 's' : '' ?>
                        </p>
                    </div>
                </div>

                <?php if (empty($orders)): ?>
                    <p style="text-align: center; color: blue;">Aucune commande pour le moment.</p>
                <?php else: ?>
                    <style>
                        .orders-table thead tr:first-child {
                            background-color: blue;
                        }
                    </style>

                    <table class="orders-table">
                        <thead>
                            <tr style="background-color: red;">
                                <th>N Commande</th>
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
                                $orderId = (int)$order['id'];
                                $orderNumber = $order['order_number'] ?: ('CMD-' . date('Ymd', strtotime($order['created_at'] ?? 'now')) . '-' . str_pad((string)$orderId, 6, '0', STR_PAD_LEFT));
                                $orderDate = $order['created_at'] ?? null;
                                $orderDateStr = $orderDate ? date('d/m/Y H:i', strtotime($orderDate)) : 'Date non disponible';
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($orderNumber) ?></strong></td>
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
                                                <option value="Confirmed" <?= ($order['order_status'] === 'Confirmed') ? 'selected' : '' ?>>Confirmee</option>
                                                <option value="Shipped" <?= ($order['order_status'] === 'Shipped') ? 'selected' : '' ?>>Expediee</option>
                                                <option value="Delivered" <?= ($order['order_status'] === 'Delivered') ? 'selected' : '' ?>>Livree</option>
                                                <option value="Cancelled" <?= ($order['order_status'] === 'Cancelled') ? 'selected' : '' ?>>Annulee</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if ($ordersTotalPages > 1): ?>
                        <nav class="admin-pagination" aria-label="Pagination commandes">
                            <a
                                class="pagination-link <?= $ordersCurrentPage <= 1 ? 'disabled' : '' ?>"
                                href="<?= $ordersCurrentPage <= 1 ? '#' : '?view=orders&page=' . (int)($ordersCurrentPage - 1) ?>"
                            >
                                Precedent
                            </a>

                            <div class="pagination-pages">
                                <?php for ($pageNumber = 1; $pageNumber <= $ordersTotalPages; $pageNumber++): ?>
                                    <a
                                        class="pagination-link <?= $pageNumber === $ordersCurrentPage ? 'active' : '' ?>"
                                        href="?view=orders&page=<?= (int)$pageNumber ?>"
                                    >
                                        <?= (int)$pageNumber ?>
                                    </a>
                                <?php endfor; ?>
                            </div>

                            <a
                                class="pagination-link <?= $ordersCurrentPage >= $ordersTotalPages ? 'disabled' : '' ?>"
                                href="<?= $ordersCurrentPage >= $ordersTotalPages ? '#' : '?view=orders&page=' . (int)($ordersCurrentPage + 1) ?>"
                            >
                                Suivant
                            </a>
                        </nav>
                    <?php endif; ?>
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
                                                'replied' => 'Repondu'
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
                                            <option value="replied" <?= ($contactStatus === 'replied') ? 'selected' : '' ?>>Repondu</option>
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

    </main>
</body>

</html>
