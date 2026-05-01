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
		product_type VARCHAR(100) DEFAULT 'General',
		created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
	)"
);

$nom = trim($_POST['nom'] ?? '');
$prix = (float)($_POST['prix'] ?? 0);
$type = trim($_POST['type'] ?? 'General');
$errors = [];

// Validation
if ($nom === '') {
	$errors[] = 'Le nom du produit est obligatoire';
}

if ($prix <= 0) {
	$errors[] = 'Le prix doit être un nombre positif';
}

if ($type === '') {
	$errors[] = 'Le type de produit est obligatoire';
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
	$errors[] = 'L\'image est obligatoire';
}

// Check image on POST
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
	$_SESSION['product_errors'] = $errors;
	header("Location: admin.php");
	exit();
}

$image = basename($_FILES['image']['name']);
$tmp = $_FILES['image']['tmp_name'];

if ($tmp === '' || !is_uploaded_file($tmp)) {
	$errors[] = 'Erreur lors du chargement de l\'image';
	$_SESSION['product_errors'] = $errors;
	header("Location: admin.php");
	exit();
}

// Check file extension
$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));
if (!in_array($ext, $allowed, true)) {
	$errors[] = 'Type d\'image non autorisé. Utilisez JPG, PNG, WEBP ou GIF';
	$_SESSION['product_errors'] = $errors;
	header("Location: admin.php");
	exit();
}

if (count($errors) > 0) {
	$_SESSION['product_errors'] = $errors;
	header("Location: admin.php");
	exit();
}

$imageName = time() . "_" . preg_replace('/[^a-zA-Z0-9._-]/', '_', $image);
$imgPath = "../../img/" . $imageName;

if (!move_uploaded_file($tmp, $imgPath)) {
	$errors[] = 'Impossible de déplacer le fichier image';
	$_SESSION['product_errors'] = $errors;
	header("Location: admin.php");
	exit();
}

try {
	$sql = "INSERT INTO produits (nom, prix, image, product_type) VALUES (?, ?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$nom, $prix, $imageName, $type]);
	header("Location: admin.php?status=added");
	exit();
} catch (Exception $e) {
	// Clean up uploaded file on database error
	if (file_exists($imgPath)) {
		@unlink($imgPath);
	}
	$_SESSION['product_errors'] = ['Erreur lors de l\'ajout du produit. Veuillez réessayer.'];
	header("Location: admin.php");
	exit();
}