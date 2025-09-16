<?php
session_start();
require 'config.php';

// Vérification de session
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'] ?? 0;

// Vérification de l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p>Mission invalide.</p><a href='dashboard.php'>← Retour</a>";
    exit;
}

$id = (int) $_GET['id'];

// --- Fonction pour afficher les personnels d'une mission
function getPersonnelsInfo($pdo, $ids_str) {
    if(empty($ids_str)) return '-';
    $ids = array_map('intval', explode(',', $ids_str));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT nom, prenom, poste FROM personnels WHERE id IN ($placeholders) ORDER BY nom");
    $stmt->execute($ids);
    $res = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $p){
        $res[] = htmlspecialchars($p['nom'].' '.$p['prenom'].' ('.$p['poste'].')');
    }
    return implode(', ', $res);
}

// Récupération de la mission
$stmt = $pdo->prepare("
    SELECT m.*, s.nom AS service_nom
    FROM missions m
    LEFT JOIN services s ON m.service_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    echo "<p>Mission introuvable.</p><a href='dashboard.php'>← Retour</a>";
    exit;
}

// Déterminer la couleur du statut
$statusClass = "status-ready";
$statusText = $mission['statut'] ?? 'Non défini';
switch($statusText) {
    case 'En préparation RH': $statusClass='status-locked'; break;
    case 'Lancée': $statusClass='status-ready'; break;
    case 'En cours': $statusClass='status-ready'; break;
    case 'Rejetée': $statusClass='status-red'; break;
    case 'Validée DG': $statusClass='status-ready'; break;
    default: $statusClass='status-ready';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Détails de la mission</title>
<style>
body { font-family: Arial; padding:20px; background:#f4f6f8; color:#333; }
.mission-details { background:#fff; padding:25px; border-radius:8px; max-width:700px; margin:auto; box-shadow:0 2px 15px rgba(0,0,0,0.1); }
h2 { color:#006837; margin-bottom:15px; }
.field { margin-bottom:10px; font-size:1rem; }
.status { padding:4px 10px; border-radius:5px; color:#fff; font-weight:bold; font-size:0.9rem; }
.status-ready { background:#27ae60; }
.status-locked { background:#f39c12; }
.status-red { background:#c0392b; }
a.back { display:inline-block; margin-top:20px; color:#006837; text-decoration:none; font-weight:bold; }
a.back:hover { text-decoration:underline; }
</style>
</head>
<body>

<div class="mission-details">
    <h2><?= htmlspecialchars($mission['titre']) ?></h2>
    
    <div class="field"><strong>Statut :</strong> <span class="status <?= $statusClass ?>"><?= htmlspecialchars($statusText) ?></span></div>
    <div class="field"><strong>Service :</strong> <?= htmlspecialchars($mission['service_nom'] ?? '-') ?></div>
    <div class="field"><strong>Dates :</strong> <?= htmlspecialchars($mission['date_debut']) ?> → <?= htmlspecialchars($mission['date_fin']) ?></div>
    <div class="field"><strong>Logistique :</strong> <?= htmlspecialchars($mission['logistique'] ?? '-') ?></div>
    <div class="field"><strong>Personnels :</strong> <?= getPersonnelsInfo($pdo, $mission['personnels']) ?></div>

    <a class="back" href="dashboard.php">← Retour au tableau de bord</a>
</div>

</body>
</html>
