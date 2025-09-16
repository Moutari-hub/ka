<?php
session_start();
require 'config.php';
require_once 'functions.php';

// Vérification session
if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['user_nom'])) {
    header('Location: login.php');
    exit;
}

// Infos utilisateur
$user = [
    'id'         => $_SESSION['user_id'],
    'role'       => (int)$_SESSION['role_id'], // 2=Manager,3=Directeur,4=DG
    'nom'        => htmlspecialchars($_SESSION['user_nom']),
    'service_id' => $_SESSION['service_id'] ?? null,
];

// Récupération des personnels
function getPersonnelsInfo(PDO $pdo, string $ids_str): string {
    if (empty($ids_str)) return '-';
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

// Workflow message
function getWorkflowMessage(array $m): string {
    $manager    = (int)($m['manager_validation'] ?? 0);
    $dirService = (int)($m['dir_service_validation'] ?? 0);
    $lancement  = (int)($m['lancement'] ?? 0);
    $dfValide   = (int)($m['df_valide'] ?? 0);
    $dgValFinal = (int)($m['dg_valide_final'] ?? 0);

    if($manager===0) return "En attente validation Manager";
    if($dirService===0) return "Validée Manager → attente Directeur de Service";
    if($lancement===0) return "Validée Dir. Service → attente DG (lancement)";
    if($dfValide===1 && $dgValFinal===0) return "Préparée DF → attente validation finale DG";
    if($dgValFinal===1) return "Mission en cours / Complète";
    return "En attente validations précédentes";
}

// Champ commentaire selon rôle
function getCommentField(array $m, int $role): string {
    switch($role){
        case 2: return $m['commentaire_manager'] ?? '';
        case 3: return $m['commentaire_dir'] ?? '';
        case 4: return $m['commentaire_dg'] ?? '';
        default: return '';
    }
}

// Vérifie si l'utilisateur peut agir
function canUserAct(array $m, array $user): bool {
    $manager    = (int)($m['manager_validation'] ?? 0);
    $dirService = (int)($m['dir_service_validation'] ?? 0);
    $lancement  = (int)($m['lancement'] ?? 0);
    $dfValide   = (int)($m['df_valide'] ?? 0);
    $dgValFinal = (int)($m['dg_valide_final'] ?? 0);

    switch($user['role']){
        case 2: // Manager
            return ($m['service_id']==$user['service_id']) && $manager===0;
        case 3: // Directeur
            return ($m['service_id']==$user['service_id']) && $manager===1 && $dirService===0;
        case 4: // DG
            return ($dirService===1 && $lancement===0) || ($dfValide===1 && $dgValFinal===0);
        default: return false;
    }
}

/* ------------------ Traitement formulaire ------------------ */
$success = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mission_id'], $_POST['action'])) {
    $mission_id  = (int)$_POST['mission_id'];
    $action      = $_POST['action'];
    $commentaire = trim($_POST['commentaire'] ?? '');

    $stmtCheck = $pdo->prepare("SELECT * FROM missions WHERE id=:id");
    $stmtCheck->execute([':id'=>$mission_id]);
    $m = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    if(!$m) {
        $success = "Mission introuvable.";
    } else {
        switch($user['role']){
            case 2: // Manager
                $val = $action==='valider'?1:0;
                $statut = $action==='valider'?'En attente validation Directeur':'Rejetée';
                $stmt = $pdo->prepare("UPDATE missions SET manager_validation=:val, commentaire_manager=:c, statut=:s WHERE id=:id");
                $stmt->execute([':val'=>$val, ':c'=>$commentaire, ':s'=>$statut, ':id'=>$mission_id]);
                $success = $action==='valider'?"Mission validée par Manager":"Mission rejetée par Manager";
                break;

            case 3: // Directeur
                $val = $action==='valider'?1:0;
                $statut = $action==='valider'?'En attente DG':'Rejetée';
                $stmt = $pdo->prepare("UPDATE missions SET dir_service_validation=:val, commentaire_dir=:c, statut=:s WHERE id=:id");
                $stmt->execute([':val'=>$val, ':c'=>$commentaire, ':s'=>$statut, ':id'=>$mission_id]);
                $success = $action==='valider'?"Mission validée par Directeur":"Mission rejetée par Directeur";
                break;

            case 4: // DG
                $lancement  = (int)($m['lancement'] ?? 0);
                $dfValide   = (int)($m['df_valide'] ?? 0);
                $dgValFinal = (int)($m['dg_valide_final'] ?? 0);
                $dirService = (int)($m['dir_service_validation'] ?? 0);

                if($action==='valider'){
                    if($dirService===1 && $lancement===0){
                        $stmt = $pdo->prepare("UPDATE missions SET lancement=1, statut='Lancée', commentaire_dg=:c WHERE id=:id");
                        $stmt->execute([':c'=>$commentaire, ':id'=>$mission_id]);
                        $success = "Mission lancée par DG.";
                    } elseif($dfValide===1 && $dgValFinal===0){
                        $stmt = $pdo->prepare("UPDATE missions SET dg_valide_final=1, statut='En cours', commentaire_dg=:c WHERE id=:id");
                        $stmt->execute([':c'=>$commentaire, ':id'=>$mission_id]);
                        $success = "Ordre de mission validé par DG.";
                    } else {
                        $success = "Impossible de valider cette mission : conditions non remplies (Dir. Service ou DF non validé).";
                    }
                } else {
                    // Rejet DG
                    $stmt = $pdo->prepare("UPDATE missions SET dg_valide_final=0, lancement=0, statut='Rejetée', commentaire_dg=:c WHERE id=:id");
                    $stmt->execute([':c'=>$commentaire, ':id'=>$mission_id]);
                    $success = "Mission rejetée par DG.";
                }
                break;
        }
    }
}

/* ------------------ Récupération missions selon rôle ------------------ */
$where=[]; $params=[];
switch($user['role']){
    case 2: $where[]='service_id=:sid AND (manager_validation IS NULL OR manager_validation=0)'; $params[':sid']=$user['service_id']; break;
    case 3: $where[]='service_id=:sid AND manager_validation=1 AND (dir_service_validation IS NULL OR dir_service_validation=0)'; $params[':sid']=$user['service_id']; break;
    case 4: $where[]='(dir_service_validation=1 AND lancement=0) OR (df_valide=1 AND (dg_valide_final IS NULL OR dg_valide_final=0))'; break;
    default: $where[]='1=0'; break;
}

$whereSql = $where? 'WHERE '.implode(' AND ',$where) : '';
$sql = "SELECT * FROM missions $whereSql ORDER BY date_debut DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Validation des missions</title>
<style>
body{font-family:Arial;margin:20px;background:#f4f6f8;color:#333;}
h1{text-align:center;color:#2c3e50;margin-bottom:20px;}
.return-btn{display:inline-block;margin-bottom:20px;padding:8px 15px;background:#3498db;color:#fff;text-decoration:none;border-radius:5px;}
.return-btn:hover{background:#2980b9;}
.mission{background:#fff;padding:20px;margin-bottom:20px;border-radius:10px;box-shadow:0 4px 10px rgba(0,0,0,0.08);}
.mission h2{color:#1abc9c;margin-top:0;}
form textarea{width:100%;padding:10px;margin-bottom:15px;border:1px solid #ccc;border-radius:6px;resize:vertical;}
button.valider{background:#1abc9c;}
button.rejeter{background:#e74c3c;}
button{color:#fff;padding:10px 20px;border:none;border-radius:6px;cursor:pointer;margin-right:10px;}
.ordre_mission{background:#fff;border:2px solid #1abc9c;padding:20px;border-radius:10px;margin-top:15px;box-shadow:0 4px 10px rgba(0,0,0,0.1);}
.ordre_mission h2{text-align:center;color:#27ae60;margin-bottom:15px;text-decoration:underline;}
.ordre_mission img{width:120px;display:block;margin:0 auto 15px;}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:6px;margin-bottom:20px;text-align:center;}
</style>
</head>
<body>
<a href="dashboard.php" class="return-btn">&larr; Retour au dashboard</a>
<h1>Validation des missions</h1>

<?php if(!empty($success)) echo "<div class='success'>".htmlspecialchars($success)."</div>"; ?>

<?php if(empty($missions)): ?>
<p>Aucune mission à valider pour le moment.</p>
<?php else: ?>
<?php foreach($missions as $m): ?>
<div class="mission">
    <h2><?= htmlspecialchars($m['titre'] ?? '') ?></h2>
    <p><strong>Date :</strong> <?= isset($m['date_debut'])?date("d/m/Y",strtotime($m['date_debut'])):'-' ?> → <?= isset($m['date_fin'])?date("d/m/Y",strtotime($m['date_fin'])):'-' ?></p>
    <p><strong>Type :</strong> <?= htmlspecialchars($m['type_mission'] ?? '-') ?></p>
    <p><strong>Zone :</strong> <?= htmlspecialchars($m['zone_mission'] ?? '-') ?></p>
    <p><strong>Statut workflow :</strong> <?= getWorkflowMessage($m) ?></p>

    <?php if(canUserAct($m,$user)): ?>
        <?php if($user['role']!==4 || (int)($m['df_valide']??0)===0): ?>
            <form method="POST">
                <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                <label>Commentaire :</label><br>
                <textarea name="commentaire"><?= htmlspecialchars(getCommentField($m,$user['role'])) ?></textarea><br>
                <button type="submit" name="action" value="valider" class="valider">Valider</button>
                <button type="submit" name="action" value="rejeter" class="rejeter">Rejeter</button>
            </form>
        <?php else: ?>
            <div class="ordre_mission">
                <img src="images/logo.jpg" alt="Logo">
                <h2>ORDRE DE MISSION</h2>
                <p><strong>Titre :</strong> <?= htmlspecialchars($m['titre'] ?? '') ?></p>
                <p><strong>Description :</strong> <?= htmlspecialchars($m['description'] ?? '') ?></p>
                <p><strong>Type :</strong> <?= htmlspecialchars($m['type_mission'] ?? '') ?></p>
                <p><strong>Zone :</strong> <?= htmlspecialchars($m['zone_mission'] ?? '') ?></p>
                <p><strong>Date :</strong> <?= isset($m['date_debut'])?date("d/m/Y",strtotime($m['date_debut'])):'-' ?> → <?= isset($m['date_fin'])?date("d/m/Y",strtotime($m['date_fin'])):'-' ?></p>
                <p><strong>Montant prévu :</strong> <?= isset($m['montant_prevu'])?number_format($m['montant_prevu'],0,',',' ').' F CFA':'N/A' ?></p>
                <p><strong>Logistique :</strong> <?= htmlspecialchars($m['logistique'] ?? 'Non défini') ?></p>
                <p><strong>Personnel :</strong> <?= getPersonnelsInfo($pdo, $m['personnels'] ?? '') ?></p>
                <form method="POST">
                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                    <label>Commentaire :</label><br>
                    <textarea name="commentaire"><?= htmlspecialchars(getCommentField($m,$user['role'])) ?></textarea><br>
                    <button type="submit" name="action" value="valider" class="valider">Valider ordre de mission</button>
                    <button type="submit" name="action" value="rejeter" class="rejeter">Rejeter ordre de mission</button>
                </form>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php endforeach; ?>
<?php endif; ?>
</body>
</html>
