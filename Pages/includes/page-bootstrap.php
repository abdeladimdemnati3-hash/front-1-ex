<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$currentUser = $_SESSION['user'] ?? null;
$userName = $currentUser['NOM'] ?? $currentUser['nom'] ?? null;
$userEmail = $currentUser['EMAIL'] ?? $currentUser['email'] ?? null;
$userImage = $currentUser['image'] ?? $currentUser['IMAGE'] ?? 'default.png';
$typeAdmin = $currentUser['type_admin'] ?? $currentUser['TYPE_ADMIN'] ?? 'N';
$isAdmin = $typeAdmin === 'A';
$cartCount = 0;
$cartStatus = '(Vide)';
$userImage = basename($userImage);

if (empty($userImage) || $userImage === '') {
    $userImage = 'default.png';
}

$imagePath = __DIR__ . '/../../img/' . $userImage;
$hasAvatar = $currentUser && file_exists($imagePath) && is_file($imagePath);

if ($userEmail) {
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS cart_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            user_email VARCHAR(255) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            product_price DECIMAL(10,2) NOT NULL,
            product_image VARCHAR(255) DEFAULT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )"
    );

    $cartStmt = $pdo->prepare("SELECT quantity FROM cart_items WHERE user_email = ?");
    $cartStmt->execute([$userEmail]);
    $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItems as $item) {
        $cartCount += (int)$item['quantity'];
    }

    if ($cartCount > 0) {
        $cartStatus = '(' . $cartCount . ' article(s))';
    }
}
