<?php
session_start();
include "../../config/db.php";

$email = $_POST['email'] ?? '';
$mdp   = $_POST['password'] ?? '';

$sql = "SELECT * FROM users WHERE EMAIL=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && $mdp == $user['mdp']) {
    $_SESSION['user'] = $user;
    
    // Normalize session keys for compatibility
    if (isset($user['NOM'])) {
        $_SESSION['user']['nom'] = $user['NOM'];
    }
    if (isset($user['EMAIL'])) {
        $_SESSION['user']['email'] = $user['EMAIL'];
    }
    if (isset($user['IMAGE'])) {
        $_SESSION['user']['IMAGE'] = $user['image'] ?? 'default.png';
    }

    // Send all users to the homepage; admins will see the Admin button there.
    header("Location: ../../index.php");
    exit();

} else {
    echo "Email ou mot de passe incorrect";
}
?>