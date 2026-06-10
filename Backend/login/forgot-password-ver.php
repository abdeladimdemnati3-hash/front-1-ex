<?php
include "../../config/db.php";

$email = trim($_POST['email'] ?? '');
$currentPassword = $_POST['current_password'] ?? '';
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (empty($email) || empty($currentPassword) || empty($password) || empty($confirmPassword)) {
    header("Location: forgot-password.php?error=missing");
    exit();
}

if ($password !== $confirmPassword) {
    header("Location: forgot-password.php?error=match");
    exit();
}

if (strlen($password) < 6) {
    header("Location: forgot-password.php?error=short");
    exit();
}

$sql = "SELECT EMAIL, mdp FROM app_users WHERE EMAIL = ? OR LOWER(EMAIL) = ? LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email, strtolower($email)]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: forgot-password.php?error=current");
    exit();
}

$storedPassword = $user['mdp'] ?? '';
$isCurrentPasswordValid = hash_equals($storedPassword, $currentPassword);

if (!$isCurrentPasswordValid) {
    header("Location: forgot-password.php?error=current");
    exit();
}

$userEmail = $user['EMAIL'] ?? $email;
$updateSql = "UPDATE app_users SET mdp = ? WHERE EMAIL = ?";
$updateStmt = $pdo->prepare($updateSql);
$updateStmt->execute([$password, $userEmail]);

header("Location: Login.php?reset=success");
exit();
