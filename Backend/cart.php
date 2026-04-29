<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user'])) {
	header("Location: ../Backend/login/Login.php");
	exit();
}

$user = $_SESSION['user'];
$userId = $user['id'] ?? $user['ID'] ?? $user['id_user'] ?? $user['ID_USER'] ?? null;
$userEmail = $user['EMAIL'] ?? $user['email'] ?? null;

if (!$userEmail) {
	header("Location: ../Backend/login/Login.php");
	exit();
}

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("Location: ../index.php");
	exit();
}

function buildAppRedirect(string $redirect): string
{
	$appRoot = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
	if ($appRoot === '') {
		$appRoot = '/';
	}

	$parts = parse_url($redirect);
	$path = $parts['path'] ?? 'index.php';
	$path = preg_replace('#^(\./|\.\./)+#', '', $path);
	$path = ltrim($path, '/');
	if ($path === '') {
		$path = 'index.php';
	}

	$query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';

	return ($appRoot === '/' ? '' : $appRoot) . '/' . $path . $query;
}

$productName = trim($_POST['product_name'] ?? '');
$productPrice = (float)($_POST['product_price'] ?? 0);
$productImage = basename(trim($_POST['product_image'] ?? ''));
$quantity = (int)($_POST['quantity'] ?? 1);
$redirect = trim($_POST['redirect'] ?? '../index.php');

if ($redirect === '' || preg_match('/^https?:\/\//i', $redirect) || strpos($redirect, '//') === 0) {
	$redirect = '../index.php';
}

$redirect = buildAppRedirect($redirect);

if ($productName === '' || $productPrice <= 0) {
	header("Location: " . $redirect);
	exit();
}

if ($quantity < 1) {
	$quantity = 1;
}

$checkSql = "SELECT id, quantity FROM cart_items WHERE user_email = ? AND product_name = ?";
$checkStmt = $pdo->prepare($checkSql);
$checkStmt->execute([$userEmail, $productName]);
$existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
	$newQuantity = (int)$existing['quantity'] + $quantity;
	$updateSql = "UPDATE cart_items SET quantity = ?, product_price = ?, product_image = ? WHERE id = ?";
	$updateStmt = $pdo->prepare($updateSql);
	$updateStmt->execute([$newQuantity, $productPrice, $productImage, $existing['id']]);
} else {
	$insertSql = "INSERT INTO cart_items (user_id, user_email, product_name, product_price, product_image, quantity) VALUES (?, ?, ?, ?, ?, ?)";
	$insertStmt = $pdo->prepare($insertSql);
	$insertStmt->execute([$userId, $userEmail, $productName, $productPrice, $productImage, $quantity]);
}

$separator = strpos($redirect, '?') !== false ? '&' : '?';
header("Location: " . $redirect . $separator . "added=1");
exit();
?>
