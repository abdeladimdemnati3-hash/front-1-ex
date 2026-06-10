<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../Pages/profile.php");
    exit();
}

$user = $_SESSION['user'];
$currentEmail = $user['EMAIL'] ?? $user['email'] ?? null;
$currentImage = $user['image'] ?? $user['IMAGE'] ?? 'default.png';

if (!$currentEmail) {
    header("Location: ../Backend/login/Login.php");
    exit();
}

$newName = trim($_POST['nom'] ?? '');
if ($newName === '') {
    $newName = $user['NOM'] ?? $user['nom'] ?? 'User';
}

$newImage = $currentImage;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['image']['tmp_name'];
    $original = basename($_FILES['image']['name']);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (in_array($ext, $allowed, true) && is_uploaded_file($tmp)) {
        $newImage = time() . '_profile_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
        $destination = __DIR__ . '/../img/' . $newImage;

        if (move_uploaded_file($tmp, $destination)) {
            $old = basename((string)$currentImage);
            if ($old !== '' && $old !== 'default.png') {
                $oldPath = __DIR__ . '/../img/' . $old;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        } else {
            $newImage = $currentImage;
        }
    }
}


$newImage = basename($newImage);
if (empty($newImage)) {
    $newImage = 'default.png';
}


$imagePath = __DIR__ . '/../img/' . $newImage;
if ($newImage !== 'default.png' && (!file_exists($imagePath) || !is_file($imagePath))) {
    $newImage = $currentImage; // Keep the old image if new one doesn't exist
}


try {
    $updateSql = "UPDATE app_users SET NOM = ?, image = ? WHERE EMAIL = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute([$newName, $newImage, $currentEmail]);
    
    if (!$result) {
        throw new Exception("Database update failed");
    }
} catch (Exception $e) {
    
    $updateSql = "UPDATE app_users SET NOM = ?, image = ? WHERE EMAIL = ?";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute([$newName, $newImage, $currentEmail]);
}


$_SESSION['user']['NOM'] = $newName;
$_SESSION['user']['nom'] = $newName;
$_SESSION['user']['image'] = $newImage;
$_SESSION['user']['IMAGE'] = $newImage;

header("Location: ../Pages/profile.php?updated=1");
exit();
