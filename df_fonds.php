<?php
session_start();
require 'config.php';

// Vérification que l'utilisateur est DF (role_id = 6)
if (!isset($_SESSION['user_id'], $_SESSION['role_id']) || $_SESSION['role_id'] != 6) {
    header('Location: login.php');
    exit;
}

$user = [
    'id' => $_SESSION['user_id'],
    'nom' => htmlspecialchars($_SESSION['user_nom']),
];

$success = '';
$error = '';

// Récupération des missions à traiter par DF
$stmt = $pdo->query("
    SELECT * 
    FROM missions
    WHERE lancement = 1 
      AND rh_preparer = 1
      AND (df_valide = 0 OR df_valide IS NULL)
    ORDER BY date_debut ASC
");
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour récupérer les noms et postes des personnels à partir d'une chaîne d'IDs
function getPersonnelsInfo($pdo, $ids_str) {
    if(empty($ids_str)) return '-';
    $ids = array_map('intval', explode(',', $ids_str));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT nom, prenom, poste FROM personnels WHERE id IN ($placeholders) ORDER BY nom");
    $stmt->execute($ids);
    $pers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $res = [];
    foreach($pers as $p){
        $res[] = htmlspecialchars($p['nom'].' '.$p['prenom'].' ('.$p['poste'].')');
    }
    return implode(', ', $res);
}

// Traitement formulaire DF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mission_id'])) {
    $mission_id = (int)$_POST['mission_id'];
    $montant_prevu = floatval($_POST['montant_prevu'] ?? 0);
    $commentaire_df = trim($_POST['commentaire_df'] ?? '');

    if ($montant_prevu > 0) {
        $stmt = $pdo->prepare("
            UPDATE missions
            SET montant_prevu = :montant_prevu,
                commentaire_df = :commentaire_df,
                df_valide = 1
            WHERE id = :id
              AND lancement = 1
              AND rh_preparer = 1
              AND (df_valide = 0 OR df_valide IS NULL)
        ");
        $stmt->execute([
            ':montant_prevu' => $montant_prevu,
            ':commentaire_df' => $commentaire_df,
            ':id' => $mission_id,
        ]);

        $success = "Mission traitée avec succès. Elle est maintenant visible par le DG.";
        header("Refresh:0");
        exit;
    } else {
        $error = "Veuillez saisir un montant valide pour la mission.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Ordre de mission - DF</title>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background:#f4f6f8; margin:0; padding:20px; }
.header { text-align:center; margin-bottom:25px; }
.header img { max-width:120px; margin-bottom:10px; }
.header h1 { margin:0; font-size:1.8rem; color:#2c3e50; }
.button-back { display:inline-block; margin:15px 0; padding:8px 15px; background:#2980b9; color:#fff; border:none; border-radius:5px; text-decoration:none; }
.button-back:hover { background:#1f5c8b; }
.success, .error { padding:12px; border-radius:6px; margin-bottom:20px; font-weight:bold; width:90%; margin:auto; text-align:center; }
.success { background:#d4edda; color:#155724; }
.error { background:#f8d7da; color:#721c24; }
.mission-card { background:#fff; padding:25px; margin-bottom:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.05); }
.mission-card h2 { margin-top:0; color:#34495e; border-bottom:1px solid #ddd; padding-bottom:6px; }
.mission-info { margin:10px 0; }
.mission-info strong { display:inline-block; width:140px; color:#2c3e50; }
.mission-form input, .mission-form textarea { width:100%; padding:10px; margin-top:5px; margin-bottom:15px; border-radius:6px; border:1px solid #ccc; font-size:0.95rem; }
.mission-form button { padding:12px 20px; background:#27ae60; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:0.95rem; }
.mission-form button:hover { background:#219150; }
</style>
</head>
<body>

<div class="header">
    <img src="images/logo.jpg" alt="Niger Telecoms">
    <h1>Validation financière - DF</h1>
</div>

<a href="dashboard.php" class="button-back">← Retour au dashboard</a>

<?php if(!empty($success)) echo "<div class='success'>$success</div>"; ?>
<?php if(!empty($error)) echo "<div class='error'>$error</div>"; ?>

<?php if(empty($missions)): ?>
    <p style="text-align:center;">Aucune mission à traiter pour le moment.</p>
<?php else: ?>
    <?php foreach($missions as $m): ?>
    <div class="mission-card">
        <h2><?= htmlspecialchars($m['titre']) ?></h2>
        <div class="mission-info"><strong>Durée :</strong> <?= htmlspecialchars($m['date_debut'].' → '.$m['date_fin']) ?></div>
        <div class="mission-info"><strong>Type :</strong> <?= htmlspecialchars($m['type_mission'] ?? '-') ?></div>
        <div class="mission-info"><strong>Logistique :</strong> <?= htmlspecialchars($m['logistique']) ?></div>
        <div class="mission-info"><strong>Personnels :</strong> <?= getPersonnelsInfo($pdo, $m['personnels']) ?></div>
        <div class="mission-info"><strong>Commentaire RH :</strong> <?= htmlspecialchars($m['commentaire_rh']) ?></div>

        <form method="POST" class="mission-form">
            <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">

            <label>Montant prévu (FCFA) :</label>
            <input type="number" name="montant_prevu" value="<?= htmlspecialchars($m['montant_prevu'] ?? '') ?>" required min="1" step="0.01">

            <label>Commentaire DF :</label>
            <textarea name="commentaire_df"><?= htmlspecialchars($m['commentaire_df'] ?? '') ?></textarea>

            <button type="submit">Valider la mission</button>
        </form>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
