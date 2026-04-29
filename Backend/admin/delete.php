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

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    header("Location: admin.php");
    exit();
}

$selectStmt = $pdo->prepare("SELECT image FROM produits WHERE id = ?");
$selectStmt->execute([$id]);
$product = $selectStmt->fetch(PDO::FETCH_ASSOC);

$deleteStmt = $pdo->prepare("DELETE FROM produits WHERE id = ?");
$deleteStmt->execute([$id]);

if ($product && !empty($product['image'])) {
    $imagePath = realpath(__DIR__ . '/../../img') . DIRECTORY_SEPARATOR . basename($product['image']);
    if ($imagePath && file_exists($imagePath)) {
        @unlink($imagePath);
    }
}

header("Location: admin.php?status=deleted");
exit();
