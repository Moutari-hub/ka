<?php
session_start();
require 'config.php';

// Vérifier que l'utilisateur est RH (role_id = 5)
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
    header('Location: login.php');
    exit;
}


// --- Fonction pour obtenir les personnels par service et dates
function getPersonnelsByService($pdo, $service_id, $date_debut='', $date_fin='') {
    $personnels = [];
    if($service_id>0){
        $stmt = $pdo->prepare("SELECT id, nom, prenom, poste FROM personnels WHERE service_id=? ORDER BY nom");
        $stmt->execute([$service_id]);
        $personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if($date_debut && $date_fin){
            foreach($personnels as $key=>$p){
                $stmt2 = $pdo->prepare("
                    SELECT COUNT(*) FROM missions 
                    WHERE FIND_IN_SET(?, personnels) 
                    AND (
                        (date_debut<=? AND date_fin>=?) OR
                        (date_debut<=? AND date_fin>=?) OR
                        (date_debut>=? AND date_fin<=?)
                    )
                ");
                $stmt2->execute([$p['id'],$date_debut,$date_debut,$date_fin,$date_fin,$date_debut,$date_fin]);
                if($stmt2->fetchColumn()>0){
                    unset($personnels[$key]);
                }
            }
            $personnels = array_values($personnels);
        }
    }
    return $personnels;
}

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

// --- Gestion AJAX pour personnels
if(isset($_GET['ajax']) && $_GET['ajax']=='get_personnels'){
    $service_id = intval($_GET['service_id'] ?? 0);
    $date_debut = $_GET['date_debut'] ?? '';
    $date_fin = $_GET['date_fin'] ?? '';
    $personnels = getPersonnelsByService($pdo, $service_id, $date_debut, $date_fin);

    header('Content-Type: application/json');
    echo json_encode($personnels);
    exit;
}

// Récupérer les services
$stmtServices = $pdo->query("SELECT id, nom FROM services ORDER BY nom ASC");
$services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

// Variables formulaire
$errors = [];
$success = '';
$editMode = false;
$mission = [
    'id' => '',
    'titre' => '',
    'date_debut' => '',
    'date_fin' => '',
    'service_id' => '',
    'personnels' => [],
    'logistique' => ''
];

// --- Si modification
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM missions WHERE id=?");
    $stmt->execute([$id]);
    $missionData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($missionData) {
        $editMode = true;
        $mission = [
            'id' => $missionData['id'],
            'titre' => $missionData['titre'],
            'date_debut' => $missionData['date_debut'],
            'date_fin' => $missionData['date_fin'],
            'service_id' => $missionData['service_id'],
            'personnels' => explode(',', $missionData['personnels']),
            'logistique' => $missionData['logistique'] ?? ''
        ];
    }
}

// --- Traitement ajout/modif
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $service_id = intval($_POST['service_id'] ?? 0);
    $personnels = $_POST['personnels'] ?? [];
    $logistique = trim($_POST['logistique'] ?? '');
    $propose_par = $_SESSION['user_id']; // ID de l'utilisateur RH connecté

    if ($titre === '') $errors[] = "Le titre est obligatoire.";
    if ($service_id <= 0) $errors[] = "Veuillez sélectionner un service.";
    if (empty($personnels)) $errors[] = "Veuillez sélectionner au moins un personnel.";
    if ($date_debut === '' || $date_fin === '') $errors[] = "Veuillez saisir les dates.";

    $personnels_str = implode(',', array_map('intval', $personnels));

    if (empty($errors)) {
        if ($id > 0) {
            // modification
            $stmt = $pdo->prepare("
                UPDATE missions 
                SET titre=?, date_debut=?, date_fin=?, service_id=?, personnels=?, logistique=?, rh_preparer=1, lancement=1, propose_par=?
                WHERE id=?
            ");
            $stmt->execute([$titre, $date_debut, $date_fin, $service_id, $personnels_str, $logistique, $propose_par, $id]);
            $success = "Mission modifiée avec succès.";
        } else {
            // ajout
            $stmt = $pdo->prepare("
                INSERT INTO missions (titre, date_debut, date_fin, service_id, personnels, logistique, rh_preparer, lancement, statut, propose_par)
                VALUES (?, ?, ?, ?, ?, ?, 1, 1, 'En préparation RH', ?)
            ");
            $stmt->execute([$titre, $date_debut, $date_fin, $service_id, $personnels_str, $logistique, $propose_par]);
            $success = "Mission ajoutée avec succès.";
        }
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// --- Récupérer toutes les missions RH
$stmt = $pdo->query("
    SELECT m.*, s.nom AS service_nom
    FROM missions m
    LEFT JOIN services s ON m.service_id = s.id
    WHERE m.rh_preparer=1
    ORDER BY date_debut DESC
");
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>RH - Gestion des missions</title>
<style>
/* Styles identiques à ton précédent fichier */
body { font-family: Arial; margin:20px; background:#f4f6f8; color:#333; }
header { display:flex; align-items:center; background:#006837; color:#fff; padding:15px; border-radius:5px; margin-bottom:20px;}
header img { height:50px; margin-right:15px; }
header h1 { font-size:1.8rem; margin:0; flex-grow:1; }
.container { max-width:1200px; margin:auto; }
form { background:#fff; padding:20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.1); margin-bottom:30px; }
form label { display:block; margin-top:10px; font-weight:bold; }
form input, form select, form textarea { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; box-sizing:border-box; }
form button { margin-top:15px; padding:10px 15px; background:#006837; color:#fff; border:none; border-radius:5px; cursor:pointer; font-weight:bold; }
form button:hover { background:#00502a; }
.success { background:#dfd; padding:10px; border-radius:5px; margin-bottom:15px; color:#060; }
.error { background:#fdd; padding:10px; border-radius:5px; margin-bottom:15px; color:#900; }
table { width:100%; border-collapse: collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
th, td { padding:12px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#006837; color:#fff; }
tr:hover { background:#f0f9f5; }
.status { padding:3px 8px; border-radius:5px; color:#fff; font-weight:bold; font-size:0.85rem; }
.status-ready { background:#27ae60; }
.status-locked { background:#7f8c8d; }
.btn { padding:5px 10px; text-decoration:none; color:#fff; border-radius:4px; margin-right:5px; font-size:0.85rem; }
.btn-edit { background:#27ae60; }
.btn-view { background:#f39c12; }
#backBtn { margin-bottom:15px; padding:8px 12px; background:#444; color:#fff; text-decoration:none; border-radius:5px; display:inline-block; }
#backBtn:hover { background:#222; }
</style>
<script>
function loadPersonnels() {
    var serviceId = document.getElementById('service_id').value;
    var debut = document.getElementById('date_debut').value;
    var fin = document.getElementById('date_fin').value;
    fetch('?ajax=get_personnels&service_id='+serviceId+'&date_debut='+debut+'&date_fin='+fin)
    .then(res=>res.json())
    .then(data=>{
        var sel = document.getElementById('personnels');
        sel.innerHTML = '';
        data.forEach(p=>{
            var opt = document.createElement('option');
            opt.value = p.id;
            opt.text = p.nom+' '+p.prenom+' ('+p.poste+')';
            sel.add(opt);
        });
    });
}
</script>
</head>
<body>
<header>
    <img src="images/logo.jpg" alt="Niger Telecoms">
    <h1>Historique des missions RH</h1>
</header>
<div class="container">

<a href="javascript:history.back()" id="backBtn">← Retour</a>

<?php if(!empty($success)) echo "<div class='success'>{$success}</div>"; ?>
<?php if(!empty($errors)) foreach($errors as $e) echo "<div class='error'>{$e}</div>"; ?>

<form method="post">
    <input type="hidden" name="id" value="<?= htmlspecialchars($mission['id']) ?>">
    <label>Titre de la mission *</label>
    <input type="text" name="titre" required value="<?= htmlspecialchars($mission['titre']) ?>">

    <label>Service *</label>
    <select name="service_id" id="service_id" required onchange="loadPersonnels()">
        <option value="">-- Sélectionner --</option>
        <?php foreach($services as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $s['id']==$mission['service_id']?'selected':'' ?>><?= htmlspecialchars($s['nom']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>Personnels *</label>
    <select name="personnels[]" id="personnels" multiple size="5" required>
        <?php 
        $available = getPersonnelsByService($pdo, $mission['service_id'], $mission['date_debut'], $mission['date_fin']);
        foreach($available as $p): ?>
            <option value="<?= $p['id'] ?>" <?= in_array($p['id'],$mission['personnels'])?'selected':'' ?>>
                <?= htmlspecialchars($p['nom'].' '.$p['prenom'].' ('.$p['poste'].')') ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Date début *</label>
    <input type="date" name="date_debut" id="date_debut" required value="<?= htmlspecialchars($mission['date_debut']) ?>" onchange="loadPersonnels()">

    <label>Date fin *</label>
    <input type="date" name="date_fin" id="date_fin" required value="<?= htmlspecialchars($mission['date_fin']) ?>" onchange="loadPersonnels()">

    <label>Logistique</label>
    <input type="text" name="logistique" value="<?= htmlspecialchars($mission['logistique']) ?>">

    <button type="submit"><?= $editMode ? 'Modifier Mission' : 'Ajouter Mission' ?></button>
</form>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Titre</th>
            <th>Service</th>
            <th>Personnels</th>
            <th>Dates</th>
            <th>Logistique</th>
            <th>Statut</th>
            <th>Détails</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($missions as $m): ?>
            <tr>
                <td><?= $m['id'] ?></td>
                <td><?= htmlspecialchars($m['titre']) ?></td>
                <td><?= htmlspecialchars($m['service_nom'] ?? '-') ?></td>
                <td><?= getPersonnelsInfo($pdo, $m['personnels']) ?></td>
                <td><?= htmlspecialchars($m['date_debut']) ?> → <?= htmlspecialchars($m['date_fin']) ?></td>
                <td><?= htmlspecialchars($m['logistique']) ?></td>
                <td>
                    <?php
                        $status = 'Modifiable';
                        $class = 'status-ready';
                        if(empty($m['rh_preparer'])) { $status='En attente RH'; $class='status-locked'; }
                        elseif(!empty($m['dg_valide_final'])) { $status='Validée DG'; $class='status-ready'; }
                        echo "<span class='status {$class}'>{$status}</span>";
                    ?>
                </td>
                <td>
                    Personnel:<br><?= getPersonnelsInfo($pdo, $m['personnels']) ?><br>
                    Logistique: <?= htmlspecialchars($m['logistique']) ?><br>
                    Dates: <?= htmlspecialchars($m['date_debut']) ?> → <?= htmlspecialchars($m['date_fin']) ?>
                </td>
                <td>
                    <?php if(empty($m['dg_valide_final'])): ?>
                        <a href="?edit=<?= $m['id'] ?>" class="btn btn-edit">Modifier</a>
                    <?php else: ?>
                        <span class="btn btn-view">Lecture seule</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</div>
</body>
</html>
