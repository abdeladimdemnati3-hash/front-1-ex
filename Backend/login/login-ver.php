<?php
session_start();
include "../../config/db.php";

$email = $_POST['email'] ?? '';
$mdp   = $_POST['password'] ?? '';

$sql = "SELECT * FROM users WHERE email=?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && $mdp == $user['mdp']) {
    $_SESSION['user'] = $user;

    // Send all users to the homepage; admins will see the Admin button there.
    header("Location: ../../index.php");
    exit();

} else {
    echo "Email ou mot de passe incorrect";
}
?>