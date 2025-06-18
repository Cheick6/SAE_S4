<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = new PDO("mysql:host=localhost;port=3306;dbname=appli", "root", "");
    echo "✅ Connexion réussie à la base de données";
} catch (PDOException $e) {
    echo "❌ Erreur de connexion : " . $e->getMessage();
}
