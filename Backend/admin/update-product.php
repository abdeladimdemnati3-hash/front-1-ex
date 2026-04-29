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
$nom = trim($_POST['nom'] ?? '');
$prix = (float)($_POST['prix'] ?? 0);
$currentImage = basename(trim($_POST['current_image'] ?? ''));

if ($id <= 0 || $nom === '' || $prix <= 0) {
    header("Location: admin.php");
    exit();
}

$imageName = $currentImage;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $originalName = basename($_FILES['image']['name']);
    $tmpName = $_FILES['image']['tmp_name'];

    if ($originalName !== '' && is_uploaded_file($tmpName)) {
        $imageName = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
        $destination = __DIR__ . '/../../img/' . $imageName;

        if (move_uploaded_file($tmpName, $destination)) {
            if ($currentImage !== '') {
                $oldPath = __DIR__ . '/../../img/' . $currentImage;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        } else {
            $imageName = $currentImage;
        }
    }
}

$updateStmt = $pdo->prepare("UPDATE produits SET nom = ?, prix = ?, image = ? WHERE id = ?");
$updateStmt->execute([$nom, $prix, $imageName, $id]);

header("Location: admin.php?status=updated");
exit();
