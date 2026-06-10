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
		description TEXT NULL,
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

try {
	$pdo->exec("ALTER TABLE produits ADD COLUMN description TEXT NULL");
} catch (Exception $e) {
}

$nom = trim($_POST['nom'] ?? '');
$prixRaw = trim($_POST['prix'] ?? '');
$prixClean = str_replace([' ', ','], ['', '.'], $prixRaw);
$type = trim($_POST['type'] ?? 'General');
$description = trim($_POST['description'] ?? '');
$errors = [];

$allowedTypes = [
	'PC',
	'Laptop',
	'PC-Gamer',
	'CPU',
	'GPU',
	'Ram',
	'Disque',
	'Alimentation',
	'Clavier',
	'Souris',
	'Ecran',
	'General',
];

if ($nom === '') {
	$errors[] = 'Le nom du produit est obligatoire';
}

if ($prixClean === '' || !is_numeric($prixClean)) {
	$errors[] = 'Le prix doit etre un nombre valide';
} else {
	$prix = (float)$prixClean;
	if ($prix <= 0) {
		$errors[] = 'Le prix doit etre un nombre positif';
	}
}

if ($type === '' || !in_array($type, $allowedTypes, true)) {
	$errors[] = 'Le type de produit est invalide';
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
	$errors[] = 'L\'image est obligatoire';
}

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

$allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$ext = strtolower(pathinfo($image, PATHINFO_EXTENSION));

if (!in_array($ext, $allowed, true)) {
	$errors[] = 'Type d\'image non autorise. Utilisez JPG, PNG, WEBP ou GIF';
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
$imgDir = __DIR__ . "/../../img";
$imgPath = $imgDir . "/" . $imageName;

if (!is_dir($imgDir) || !is_writable($imgDir)) {
	$errors[] = 'Le dossier img est introuvable ou non accessible en ecriture';
	$_SESSION['product_errors'] = $errors;
	header("Location: admin.php");
	exit();
}

if (!move_uploaded_file($tmp, $imgPath)) {
	$errors[] = 'Impossible de deplacer le fichier image';
	$_SESSION['product_errors'] = $errors;
	header("Location: admin.php");
	exit();
}

try {
	$sql = "INSERT INTO produits (nom, prix, image, description, type_product, product_type) VALUES (?, ?, ?, ?, ?, ?)";
	$stmt = $pdo->prepare($sql);
	$stmt->execute([$nom, $prix, $imageName, $description, $type, $type]);

	header("Location: admin.php?status=added");
	exit();

} catch (Exception $e) {

	if (file_exists($imgPath)) {
		@unlink($imgPath);
	}

	$errorMessage = $e->getMessage();

	error_log("add-product.php error: " . $errorMessage);

	$_SESSION['product_errors'] = [
		'Erreur lors de l\'ajout du produit. Veuillez reessayer.',
		'Detail: ' . $errorMessage
	];

	header("Location: admin.php");
	exit();
}
