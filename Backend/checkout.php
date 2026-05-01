<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

$user = $_SESSION['user'];
$userId = $user['id'] ?? $user['ID'] ?? $user['id_user'] ?? $user['ID_USER'] ?? null;
$userName = $user['NOM'] ?? $user['nom'] ?? 'Unknown';
$userEmail = $user['EMAIL'] ?? $user['email'] ?? null;

if (!$userEmail) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

// Create orders table if not exists
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

// Create order items table
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Get cart items
    $cartSql = "SELECT * FROM cart_items WHERE user_email = ?";
    $cartStmt = $pdo->prepare($cartSql);
    $cartStmt->execute([$userEmail]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cartItems)) {
        $_SESSION['order_message'] = 'Votre panier est vide!';
        header("Location: ../Pages/panier.php");
        exit();
    }

    // Calculate total
    $total = 0;
    foreach ($cartItems as $item) {
        $total += (float)$item['product_price'] * (int)$item['quantity'];
    }

    try {
        $pdo->beginTransaction();

        // Create order
        $orderSql = "INSERT INTO orders (user_id, user_name, user_email, total_amount, order_status) 
                     VALUES (?, ?, ?, ?, 'Pending')";
        $orderStmt = $pdo->prepare($orderSql);
        $orderStmt->execute([$userId, $userName, $userEmail, $total]);
        
        $orderId = $pdo->lastInsertId();

        // Add order items
        $itemSql = "INSERT INTO order_items (order_id, product_name, product_price, quantity, subtotal) 
                    VALUES (?, ?, ?, ?, ?)";
        $itemStmt = $pdo->prepare($itemSql);

        foreach ($cartItems as $item) {
            $subtotal = (float)$item['product_price'] * (int)$item['quantity'];
            $itemStmt->execute([
                $orderId,
                $item['product_name'],
                $item['product_price'],
                $item['quantity'],
                $subtotal
            ]);
        }

        // Clear cart
        $clearSql = "DELETE FROM cart_items WHERE user_email = ?";
        $clearStmt = $pdo->prepare($clearSql);
        $clearStmt->execute([$userEmail]);

        $pdo->commit();

        $_SESSION['order_message'] = 'Commande passee avec succes! Numero de commande: #' . $orderId;
        header("Location: ../Pages/panier.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['order_message'] = 'Erreur lors de la commande: ' . $e->getMessage();
        header("Location: ../Pages/panier.php");
        exit();
    }
}

header("Location: ../Pages/panier.php");
exit();
