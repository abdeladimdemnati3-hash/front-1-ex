<?php
include "../../config/db.php";

if(isset($_POST['nom'], $_POST['email'], $_POST['message'])){
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if(empty($nom) || empty($email) || empty($message)){
        echo "Tous les champs sont obligatoires";
        exit();
    }

    // Validate email
    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        echo "Adresse email invalide";
        exit();
    }

    // Create contact table if not exists
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS contact (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nom VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('unread', 'read', 'replied') DEFAULT 'unread'
        )"
    );

    try {
        $pdo->exec("ALTER TABLE contact ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    } catch (Exception $e) {
        // ignore if column already exists or unsupported syntax
    }

    $sql = "INSERT INTO contact (nom, email, message) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([$nom, $email, $message]);

    if($result){
        header("Location: Contact.php?success=1");
        exit();
    } else {
        echo "Erreur lors de l'envoi du message";
    }

}else{
    echo "Tous les champs sont obligatoires";
}
?>