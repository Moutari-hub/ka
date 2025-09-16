<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$today = date('Y-m-d');

/* ------------------ STATISTIQUES GLOBALES ------------------ */
$stats = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(statut='En attente') AS en_attente,
        SUM(statut='Validée')   AS validees,
        SUM(statut='Lancée')    AS lancees,
        SUM(CASE WHEN statut='En cours' AND date_fin >= '$today' THEN 1 ELSE 0 END) AS en_cours,
        SUM(statut='Rejetée')   AS rejetees,
        (SELECT COUNT(*) FROM missions WHERE date_fin IS NOT NULL AND DATE(date_fin) < '$today') AS terminees
    FROM missions
")->fetch(PDO::FETCH_ASSOC);

// Cast en int pour toutes les clés
$keys = ['total','en_attente','validees','lancees','en_cours','rejetees','terminees'];
foreach ($keys as $k) {
    $stats[$k] = (int)$stats[$k];
}

/* ------------------ RÉCUPÉRATION DES MISSIONS ------------------ */
$stmt = $pdo->query("SELECT * FROM missions ORDER BY date_debut DESC");
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$missionsEnCours = [];
$missionsTerminees = [];
$missionsRejetees = [];

foreach ($missions as $m) {
    if ($m['statut'] === 'Rejetée') {
        $missionsRejetees[] = $m;
    } elseif ($m['date_fin'] !== null && $m['date_fin'] < $today) {
        $missionsTerminees[] = $m;
    } else {
        $missionsEnCours[] = $m;
    }
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

// Fonction pour afficher une table de missions
function renderTable($missions, $title, $showOrdre=false, $showRapport=false) {
    global $pdo;
    if(empty($missions)) return;
    echo "<h2>$title</h2>";
    echo "<table>";
    echo "<tr>
            <th>Titre</th>
            <th>Zone</th>
            <th>Dates</th>
            <th>Montant</th>
            <th>Personnels</th>
            <th>Logistique</th>
            <th>Actions</th>
          </tr>";
    foreach($missions as $m) {
        echo "<tr>";
        echo "<td>".htmlspecialchars($m['titre'])."</td>";
        echo "<td>".htmlspecialchars($m['zone_mission'])."</td>";
        echo "<td>{$m['date_debut']} → {$m['date_fin']}</td>";
        echo "<td>".number_format($m['montant_prevu'],0,',',' ')." XOF</td>";
        echo "<td>".getPersonnelsInfo($pdo,$m['personnels'])."</td>";
        echo "<td>".htmlspecialchars($m['logistique'])."</td>";
        echo "<td>";
        if($showOrdre) {
            echo "<a class='btn' href='ordre_mission.php?id={$m['id']}' target='_blank'>🖨️ Ordre de mission</a>";
        }
        if($showRapport) {
            echo "<a class='btn' href='rapport_missions.php?id={$m['id']}' target='_blank'>📄 Rapport</a>";
            echo "<a class='btn' href='detail_mission.php?id={$m['id']}'>🔍 Détails</a>";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Historique des missions - Niger Telecoms</title>
<style>
body { font-family: Arial; padding:20px; background:#f4f6f8; color:#333; }
header { display:flex; align-items:center; background:#006837; color:#fff; padding:10px 20px; border-radius:5px; margin-bottom:20px; }
header img { height:50px; margin-right:15px; }
header h1 { font-size:1.8rem; margin:0; flex-grow:1; }
h2 { color:#026c34; margin-top:30px; }
table { width:100%; border-collapse: collapse; background:#fff; border-radius:6px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.1); margin-bottom:30px; }
th, td { border:1px solid #ddd; padding:8px; text-align:left; }
th { background:#026c34; color:white; }
tr:hover { background:#f0f9f5; }
a.btn { padding:5px 10px; background:#026c34; color:white; text-decoration:none; border-radius:4px; margin-right:5px; font-size:0.9rem; }
a.btn:hover { background:#014f25; }
</style>


</head>

<a href="dashboard.php" class="btn">⬅️ Retour</a>

<body>

<header>
    <img src="images/logo.jpg" alt="Niger Telecoms">
    <h1>Historique des missions</h1>
</header>

<?php
// Affichage des tables
renderTable($missionsEnCours, "Missions en cours", true, false);
renderTable($missionsTerminees, "Missions terminées", false, true);
renderTable($missionsRejetees, "Missions rejetées", false, true);
?>

</body>
</html>
