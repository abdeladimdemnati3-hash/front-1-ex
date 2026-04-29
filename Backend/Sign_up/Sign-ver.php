<?php
include "../../config/db.php";

$nom = $_POST['nom'] ?? '';
$email = $_POST['email'] ?? '';
$mdp = $_POST['mcd'] ?? '';

if(empty($nom) || empty($email) || empty($mdp)){
    echo "les champs sont obligatoires";
    exit();
}

// image
if(isset($_FILES['image']) && $_FILES['image']['error'] == 0){
    $image = $_FILES['image']['name'];
    $tmp = $_FILES['image']['tmp_name'];

    $imageName = time() . "_" . $image;
    move_uploaded_file($tmp, "../../img/" . $imageName);
}else{
    $imageName = "default.png";
}

$sql = "INSERT INTO users (NOM, EMAIL, mdp, type_admin, image) VALUES (?, ?, ?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$nom, $email, $mdp, 'N', $imageName]);


header("Location: ../login/login.php");
exit();