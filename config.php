<?php
// config.php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=nigertelecom_missions;charset=utf8', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Erreur de connexion DB : ' . $e->getMessage());
}
