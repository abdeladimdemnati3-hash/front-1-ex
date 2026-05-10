<?php
session_start();
include "../config/db.php";

$currentUser = $_SESSION['user'] ?? null;

if (!$currentUser) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

$productName = trim($_POST['product_name'] ?? '');
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');
$redirect = $_POST['redirect'] ?? '../index.php';

if (preg_match('/^https?:\/\//i', $redirect) || strpos($redirect, "\n") !== false || strpos($redirect, "\r") !== false) {
    $redirect = '../index.php';
}

if ($productName === '' || $rating < 1 || $rating > 5) {
    header("Location: " . $redirect . (strpos($redirect, '?') !== false ? '&' : '?') . "review=invalid");
    exit();
}

$comment = substr($comment, 0, 1000);
$userName = $currentUser['NOM'] ?? $currentUser['nom'] ?? 'User';
$userEmail = $currentUser['EMAIL'] ?? $currentUser['email'] ?? '';
$productKey = md5(strtolower($productName));

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS product_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_key VARCHAR(32) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        user_email VARCHAR(255) NOT NULL,
        user_name VARCHAR(255) NOT NULL,
        rating TINYINT NOT NULL,
        comment TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_product_user (product_key, user_email)
    )"
);

$stmt = $pdo->prepare(
    "INSERT INTO product_feedback (product_key, product_name, user_email, user_name, rating, comment)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE
        product_name = VALUES(product_name),
        user_name = VALUES(user_name),
        rating = VALUES(rating),
        comment = VALUES(comment)"
);

$stmt->execute([$productKey, $productName, $userEmail, $userName, $rating, $comment]);

header("Location: " . $redirect . (strpos($redirect, '?') !== false ? '&' : '?') . "review=saved");
exit();
