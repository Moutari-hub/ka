<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifie l'ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: liste_ordres.php');
    exit;
}

$mission_id = (int)$_GET['id'];

// Récupère la mission avec le service
$stmt = $pdo->prepare("
    SELECT m.*, s.nom AS service_nom
    FROM missions m
    LEFT JOIN services s ON m.service_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    echo "<p>Mission introuvable.</p>";
    echo "<a href='liste_ordres.php' class='btn'>⬅️ Retour à la liste</a>";
    exit;
}

// Fonction pour afficher les personnels
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

// Fonction badge état
function renderEtatBadge($statut, $date_fin) {
    $today = date('Y-m-d');
    $etat = "En attente"; $class="etat gris";

    if ($statut === "Rejetée") { $etat="Rejetée"; $class="etat rouge"; }
    elseif ($statut === "Validée") { $etat="Validée"; $class="etat bleu"; }
    elseif ($statut === "Lancée") { $etat="Lancée"; $class="etat violet"; }
    elseif ($statut === "En cours" && $date_fin >= $today) { $etat="En cours"; $class="etat orange"; }
    elseif ($date_fin < $today) { $etat="Terminée"; $class="etat vert"; }

    return "<span class='$class'>$etat</span>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Détails de la mission - Niger Telecoms</title>
<style>
body { font-family: Arial; background:#f4f6f8; color:#333; padding:20px; }
header { display:flex; align-items:center; background:#006837; color:#fff; padding:10px 20px; border-radius:5px; margin-bottom:20px; }
header img { height:50px; margin-right:15px; }
header h1 { font-size:1.8rem; margin:0; flex-grow:1; }
.btn { display:inline-block; padding:7px 12px; background:#026c34; color:#fff; text-decoration:none; border-radius:4px; margin-bottom:15px; }
.btn:hover { background:#014f25; }
.card { background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1); padding:20px; margin-bottom:20px; }
.card h2 { margin-top:0; color:#026c34; }
.card table { width:100%; border-collapse: collapse; }
.card th, .card td { padding:8px; text-align:left; border-bottom:1px solid #ddd; vertical-align: top; }
.card th { width:200px; background:#f0f9f5; color:#026c34; }
.card a { color:#026c34; text-decoration:none; }
.card a:hover { text-decoration:underline; }

/* Styles badges état */
.etat { padding:4px 8px; border-radius:4px; color:#fff; font-weight:bold; }
.etat.vert { background:#28a745; }
.etat.orange { background:#fd7e14; }
.etat.rouge { background:#dc3545; }
.etat.bleu { background:#007bff; }
.etat.violet { background:#6f42c1; }
.etat.gris { background:#6c757d; }
</style>
</head>
<body>

<header>
    <img src="images/logo.jpg" alt="Niger Telecoms">
    <h1>Détails de la mission</h1>
</header>

<a href="liste_ordres.php" class="btn">⬅️ Retour à la liste</a>

<div class="card">
    <h2>Informations générales</h2>
    <table>
        <tr><th>Titre</th><td><?= htmlspecialchars($mission['titre']) ?></td></tr>
        <tr><th>Service</th><td><?= htmlspecialchars($mission['service_nom'] ?? '-') ?></td></tr>
        <tr><th>Zone</th><td><?= htmlspecialchars($mission['zone_mission']) ?></td></tr>
        <tr><th>Date de début</th><td><?= $mission['date_debut'] ?></td></tr>
        <tr><th>Date de fin</th><td><?= $mission['date_fin'] ?></td></tr>
        <tr><th>Montant prévu</th><td><?= number_format($mission['montant_prevu'],0,',',' ') ?> XOF</td></tr>
        <tr><th>Montant utilisé</th><td><?= number_format($mission['montant_utilise'],0,',',' ') ?> XOF</td></tr>
        <tr><th>État</th><td><?= renderEtatBadge($mission['statut'], $mission['date_fin']) ?></td></tr>
    </table>
</div>

<div class="card">
    <h2>Personnels</h2>
    <p><?= getPersonnelsInfo($pdo, $mission['personnels']) ?></p>
</div>

<div class="card">
    <h2>Logistique</h2>
    <p><?= nl2br(htmlspecialchars($mission['logistique'])) ?></p>
</div>

<div class="card">
    <h2>Commentaires</h2>
    <p><strong>RH :</strong> <?= nl2br(htmlspecialchars($mission['rh_commentaire'] ?? '-')) ?></p>
    <p><strong>DF :</strong> <?= nl2br(htmlspecialchars($mission['df_commentaire'] ?? '-')) ?></p>
</div>

<div class="card">
    <h2>Documents</h2>
    <p>
    <?php
        if(!empty($mission['documents'])) {
            $docs = explode(',', $mission['documents']);
            foreach($docs as $d){
                echo "<a href='uploads/".trim($d)."' target='_blank'>".htmlspecialchars($d)."</a><br>";
            }
        } else { echo '-'; }
    ?>
    </p>
</div>

</body>
</html>
