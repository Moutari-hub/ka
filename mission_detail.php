<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role_id'])) {
    header('Location: login.php');
    exit;
}

$nom = $_SESSION['user_nom'] ?? 'Utilisateur';

// Vérifie si l'ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID mission non spécifié.";
    exit;
}

$mission_id = (int) $_GET['id'];

// Récupère les détails de la mission
$stmt = $pdo->prepare("SELECT m.*, u.nom AS proposeur_nom FROM missions m
                       LEFT JOIN utilisateurs u ON m.propose_par = u.id
                       WHERE m.id = :id");
$stmt->execute([':id' => $mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    echo "Mission introuvable.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Détail de la mission - Niger Telecoms</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        header {
            background: #2e7d32;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container {
            padding: 30px;
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            margin-top: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2e7d32;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #e8f5e9;
        }
        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background: #43a047;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
        }
        .back-btn:hover {
            background: #2e7d32;
        }
    </style>
</head>
<body>

<header>
    <div><strong>Niger Telecoms</strong> - Détail de la mission</div>
    <div>Connecté : <?= htmlspecialchars($nom) ?></div>
</header>

<div class="container">
    <h1>Mission #<?= 'M-' . str_pad($mission['id'], 4, '0', STR_PAD_LEFT) ?></h1>
    <table>
        <tr><th>Titre</th><td><?= htmlspecialchars($mission['titre']) ?></td></tr>
        <tr><th>Objet</th><td><?= htmlspecialchars($mission['objet']) ?></td></tr>
        <tr><th>Lieu</th><td><?= htmlspecialchars($mission['lieu']) ?></td></tr>
        <tr><th>Date début</th><td><?= htmlspecialchars($mission['date_debut']) ?></td></tr>
        <tr><th>Date fin</th><td><?= htmlspecialchars($mission['date_fin']) ?></td></tr>
        <tr><th>Type de mission</th><td><?= htmlspecialchars($mission['type']) ?></td></tr>
        <tr><th>Logistique prévue</th><td><?= htmlspecialchars($mission['logistique']) ?></td></tr>
        <tr><th>Montant prévu</th><td><?= number_format($mission['montant_prevu'], 0, ',', ' ') ?> FCFA</td></tr>
        <tr><th>Statut</th><td><strong><?= htmlspecialchars($mission['statut']) ?></strong></td></tr>
        <tr><th>Proposée par</th><td><?= htmlspecialchars($mission['proposeur_nom']) ?></td></tr>
        <tr><th>Date de création</th><td><?= htmlspecialchars($mission['date_created']) ?></td></tr>
    </table>

    <a href="historique.php" class="back-btn">← Retour à l'historique</a>
</div>

</body>
</html>
