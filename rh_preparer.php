<?php
session_start();
require 'config.php';

// Vérification rôle RH
if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['user_nom']) || $_SESSION['role_id'] != 5) {
    header('Location: login.php');
    exit;
}

$user = [
    'id'  => $_SESSION['user_id'],
    'nom' => htmlspecialchars($_SESSION['user_nom']),
];

// Message de succès
$success = '';

// Récupérer les personnels du service
function getPersonnelsService($pdo, $service_id) {
    $stmt = $pdo->prepare("SELECT * FROM personnels WHERE service_id = :service_id ORDER BY nom, prenom");
    $stmt->execute([':service_id' => $service_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Vérifie si un personnel est déjà affecté à une mission RH
function isPersonnelEnMission($pdo, $personnel_id) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM missions
        WHERE lancement = 1 AND rh_preparer = 1 AND FIND_IN_SET(:pid, personnels) > 0
        LIMIT 1
    ");
    $stmt->execute([':pid' => $personnel_id]);
    return $stmt->fetchColumn() ? true : false;
}

// Récupérer missions à préparer
$stmt = $pdo->query("
    SELECT * 
    FROM missions
    WHERE lancement = 1
      AND dir_service_validation = 1
      AND (rh_preparer IS NULL OR rh_preparer = 0)
    ORDER BY date_debut DESC
");
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mission_id'])) {
    $mission_id = (int)$_POST['mission_id'];
    $logistique = trim($_POST['logistique'] ?? '');
    $personnels_ids = $_POST['personnels'] ?? [];
    $commentaire_rh = trim($_POST['commentaire_rh'] ?? '');
    $date_debut = $_POST['date_debut'] ?? '';
    $date_fin = $_POST['date_fin'] ?? '';
    $personnels_str = implode(',', $personnels_ids);

    $stmt = $pdo->prepare("
        UPDATE missions
        SET 
            logistique = :logistique,
            personnels = :personnels,
            commentaire_rh = :commentaire_rh,
            date_debut = :date_debut,
            date_fin = :date_fin,
            rh_preparer = 1,
            df_preparer = 0
        WHERE id = :id AND lancement = 1 AND (rh_preparer IS NULL OR rh_preparer = 0)
    ");
    $stmt->execute([
        ':logistique' => $logistique,
        ':personnels' => $personnels_str,
        ':commentaire_rh' => $commentaire_rh,
        ':date_debut' => $date_debut,
        ':date_fin' => $date_fin,
        ':id' => $mission_id,
    ]);

    $success = "Mission préparée avec succès et transmise au DF.";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Préparation des missions - RH</title>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f6f8; margin:0; padding:20px; }
.header { text-align:center; margin-bottom:25px; }
.header img { max-width:120px; margin-bottom:10px; }
.header h1 { font-size:1.8rem; color:#2c3e50; margin:0; }
.success { background:#d4edda; color:#155724; padding:12px; border-radius:6px; margin-bottom:25px; text-align:center; font-weight:600; }
.mission { background:#fff; padding:25px; margin-bottom:20px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.05); }
.mission h2 { margin-top:0; color:#34495e; font-size:1.4rem; border-bottom:1px solid #ddd; padding-bottom:5px; }
label { font-weight:600; display:block; margin-top:10px; margin-bottom:5px; color:#2c3e50; }
textarea, select, input[type=date] { width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:1px solid #ccc; font-size:0.95rem; box-sizing:border-box; }
select[multiple] { height:auto; }
button { padding:12px 25px; background-color:#2980b9; color:#fff; border:none; border-radius:6px; font-size:0.95rem; cursor:pointer; transition:background 0.2s; }
button:hover { background-color:#1f5c8b; }
.note { font-size:0.85rem; color:#7f8c8d; margin-top:5px; }
.retour { display:inline-block; margin-bottom:20px; padding:10px 20px; background:#7f8c8d; color:#fff; border-radius:6px; text-decoration:none; }
.retour:hover { background:#606c76; }
</style>
</head>
<body>

<a href="dashboard.php" class="retour">← Retour au tableau de bord</a>

<div class="header">
    <img src="images/logo.jpg" alt="Niger Telecoms">
    <h1>Préparation des missions - RH</h1>
</div>

<?php if(!empty($success)): ?>
<div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if(empty($missions)): ?>
<p>Aucune mission à préparer pour le moment.</p>
<?php else: ?>
<?php foreach($missions as $mission): ?>
<div class="mission">
    <h2><?= htmlspecialchars($mission['titre']) ?></h2>

    <form method="POST">
        <input type="hidden" name="mission_id" value="<?= $mission['id'] ?>">

        <label>Date de début :</label>
        <input type="date" name="date_debut" value="<?= htmlspecialchars($mission['date_debut']) ?>" required>

        <label>Date de fin :</label>
        <input type="date" name="date_fin" value="<?= htmlspecialchars($mission['date_fin']) ?>" required>

        <label>Logistique :</label>
        <textarea name="logistique" required><?= htmlspecialchars($mission['logistique']) ?></textarea>

        <label>Personnels affectés :</label>
        <?php 
            $personnels = getPersonnelsService($pdo, $mission['service_id']);
            if(empty($personnels)): 
                echo "<p class='note'>Aucun personnel trouvé pour ce service.</p>";
            else: ?>
                <select name="personnels[]" multiple required size="5">
                <?php foreach($personnels as $p): 
                    $enMission = isPersonnelEnMission($pdo, $p['id']);
                ?>
                    <option value="<?= $p['id'] ?>" <?= $enMission ? 'disabled' : '' ?> >
                        <?= htmlspecialchars($p['nom'].' '.$p['prenom'].' ('.$p['poste'].')') ?> <?= $enMission ? '(déjà en mission)' : '' ?>
                    </option>
                <?php endforeach; ?>
                </select>
        <?php endif; ?>

        <label>Commentaire RH :</label>
        <textarea name="commentaire_rh"><?= htmlspecialchars($mission['commentaire_rh']) ?></textarea>

        <button type="submit">Préparer la mission</button>
    </form>
</div>
<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
