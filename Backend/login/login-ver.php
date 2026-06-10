<?php
session_start();
include "../../config/db.php";

$email = trim($_POST['email'] ?? '');
$mdp   = $_POST['password'] ?? '';

if (empty($email) || empty($mdp)) {
    header("Location: Login.php?error=missing");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: Login.php?error=invalid");
    exit();
}

$sql = "SELECT * FROM app_users WHERE EMAIL = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    
    $sql = "SELECT * FROM app_users WHERE LOWER(EMAIL) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([strtolower($email)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($user && hash_equals((string)($user['mdp'] ?? ''), $mdp)) {
    
    $image = $user['image'] ?? $user['IMAGE'] ?? null;
    
    
    if (empty($image)) {
        $image = 'default.png';
    } else {
       
        $image = basename($image);
        if (empty($image)) {
            $image = 'default.png';
        }
       
        $imagePath = __DIR__ . '/../../img/' . $image;
        if (!file_exists($imagePath) || !is_file($imagePath)) {
            $image = 'default.png';
        }
    }
    
    
    $userEmail = $user['EMAIL'] ?? $email;
    
    
    $_SESSION['user'] = [
        'NOM' => $user['NOM'] ?? $user['nom'] ?? '',
        'nom' => $user['NOM'] ?? $user['nom'] ?? '',
        'EMAIL' => $userEmail,
        'email' => $userEmail,
        'mdp' => $user['mdp'] ?? '',
        'type_admin' => $user['type_admin'] ?? $user['TYPE_ADMIN'] ?? 'N',
        'TYPE_ADMIN' => $user['type_admin'] ?? $user['TYPE_ADMIN'] ?? 'N',
        'image' => $image,
        'IMAGE' => $image
    ];

    
    header("Location: ../../index.php");
    exit();

} else {
    header("Location: Login.php?error=incorrect");
    exit();
}
?>
