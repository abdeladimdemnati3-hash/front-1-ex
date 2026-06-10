<?php
include "../../config/db.php";

$nom = $_POST['nom'] ?? '';
$email = trim($_POST['email'] ?? '');
$mdp = $_POST['mcd'] ?? '';

if(empty(trim($nom)) || empty($email) || empty($mdp)){
    header("Location: Sign_up.php?error=missing");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: Sign_up.php?error=invalid");
    exit();
}

if (strlen($mdp) < 6) {
    header("Location: Sign_up.php?error=short");
    exit();
}

if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    $original = basename($image);
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (in_array($ext, $allowed, true) && is_uploaded_file($tmp)) {
        $imageName = time() . '_profile_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $original);
        move_uploaded_file($tmp, "../../img/" . $imageName);
    } else {
        $imageName = "default.png";
    }
}else{
    $imageName = "default.png";
}

try {
    $checkSql = "SELECT id FROM app_users WHERE EMAIL = ? OR LOWER(EMAIL) = ? LIMIT 1";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$email, strtolower($email)]);

    if ($checkStmt->fetch(PDO::FETCH_ASSOC)) {
        header("Location: Sign_up.php?error=exists");
        exit();
    }

    $sql = "INSERT INTO app_users (NOM, EMAIL, mdp, type_admin, image) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([trim($nom), $email, $mdp, 'N', $imageName]);
} catch (PDOException $e) {
    header("Location: Sign_up.php?error=database");
    exit();
}


header("Location: ../login/Login.php?signup=success");
exit();
