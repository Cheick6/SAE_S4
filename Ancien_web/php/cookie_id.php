<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Récupérer les valeurs du formulaire
    $mail = htmlspecialchars($_POST["addMail"]); // Nom du cookie
    $password = htmlspecialchars($_POST["password"]); // Valeur du cookie
    $duree = 86400 ; // Durée en secondes

    // Vérification des données
    if (!empty($mail) && !empty($password) && $duree > 0) {

        // Connexion à la base de données
        $pdo = new PDO('mysql:host=localhost;dbname=tondb;charset=utf8', 'root', '');

        // Vérification des identifiants
        $stmt = $pdo->prepare("SELECT user_id FROM User WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        // Créer le cookie
        setcookie("user_id", $user['user_id'], time() + $duree, "/", "", false, true); // httpOnly pour sécurité

       // echo "Le cookie '$user_id' a été créé avec la valeur '$user_id' pour une durée de $duree secondes.";
    } 
    
    else {
        //echo "Veuillez fournir des données valides.";
    }
} else {
    //echo "Aucune donnée reçue.";
}
?>
