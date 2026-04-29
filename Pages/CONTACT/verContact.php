<?php
include "../../config/db.php";

if(isset($_POST['nom'], $_POST['email'], $_POST['message'])){

    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    $sql = "INSERT INTO contact (nom, email, message) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nom, $email, $message]);

    echo "envoye succes";
    header("Location: Contact.php");
    exit();

}else{
    echo "les champs sont obliger";
}
?>