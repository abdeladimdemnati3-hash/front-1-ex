<?php
session_start();
include "../../config/db.php";

$email = trim($_POST['email'] ?? '');
$mdp   = trim($_POST['password'] ?? '');

if (empty($email) || empty($mdp)) {
    echo "Email ou mot de passe requis";
    exit();
}

// Query to get user - try EMAIL first, then email
$sql = "SELECT * FROM users WHERE EMAIL = ? OR email = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$email, $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Try case-insensitive search as last resort
    $sql = "SELECT * FROM users WHERE LOWER(EMAIL) = ? OR LOWER(email) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([strtolower($email), strtolower($email)]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($user && $mdp == $user['mdp']) {
    // Get the image - handle both column name variations
    $image = $user['image'] ?? $user['IMAGE'] ?? null;
    
    // Ensure image is set and valid
    if (empty($image)) {
        $image = 'default.png';
    } else {
        // Sanitize: remove path traversal attempts
        $image = basename($image);
        if (empty($image)) {
            $image = 'default.png';
        }
    }
    
    // Get email - use whichever is available
    $userEmail = $user['EMAIL'] ?? $user['email'] ?? $email;
    
    // Store user in session with comprehensive key mapping
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

    // Send all users to the homepage
    header("Location: ../../index.php");
    exit();

} else {
    echo "Email ou mot de passe incorrect";
}
?>