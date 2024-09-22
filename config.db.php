<?php
// config.db.php

$host = 'localhost';
$dbname = 'stations';
$username = 'root';
$password = '';

try {
    // Créer la connexion PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Ne rien afficher si la connexion réussit, pour éviter les perturbations dans les réponses API
} catch (PDOException $e) {
    // Si la connexion échoue, envoyer une réponse JSON d'erreur
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erreur de connexion à la base de données : " . $e->getMessage()
    ]);
    exit();  // Terminer l'exécution si la connexion échoue
}
