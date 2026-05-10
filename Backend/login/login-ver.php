<?php
session_start();
include "../../config/db.php";

$email = trim($_POST['email'] ?? '');
$mdp   = trim($_POST['password'] ?? '');

if (empty($email) || empty($mdp)) {
    echo "Email ou mot de passe requis";
    exit();
}


$sql = "SELECT * FROM users WHERE EMAIL = ? OR email = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email, $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    
    $sql = "SELECT * FROM users WHERE LOWER(EMAIL) = ? OR LOWER(email) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([strtolower($email), strtolower($email)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($user && $mdp == $user['mdp']) {
    
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
    
    
    $userEmail = $user['EMAIL'] ?? $user['email'] ?? $email;
    
    
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
    echo "Email ou mot de passe incorrect";
}
?>