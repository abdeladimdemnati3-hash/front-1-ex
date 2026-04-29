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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
	header("Location: admin.php");
	exit();
}

$pdo->exec(
	"CREATE TABLE IF NOT EXISTS produits (
		id INT AUTO_INCREMENT PRIMARY KEY,
		nom VARCHAR(255) NOT NULL,
		prix DECIMAL(10,2) NOT NULL,
		image VARCHAR(255) DEFAULT NULL,
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	)"
);

$nom = $_POST['nom'] ?? '';
$prix = $_POST['prix'] ?? '';

$nom = trim($nom);
$prix = (float)$prix;

if ($nom === '' || $prix <= 0 || !isset($_FILES['image'])) {
	header("Location: admin.php");
	exit();
}

$image = basename($_FILES['image']['name']);
$tmp = $_FILES['image']['tmp_name'];

if ($tmp === '' || !is_uploaded_file($tmp)) {
	header("Location: admin.php");
	exit();
}

$imageName = time() . "_" . $image;

if (!move_uploaded_file($tmp, "../../img/" . $imageName)) {
	header("Location: admin.php");
	exit();
}

$sql = "INSERT INTO produits (nom, prix, image) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nom, $prix, $imageName]);

header("Location: admin.php?status=added");
exit();