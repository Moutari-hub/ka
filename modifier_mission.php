<?php
session_start();
require 'config.php';

// Vérifier que l'utilisateur est RH
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
    header('Location: login.php');
    exit;
}

// Récupérer les missions préparées par RH
$stmt = $pdo->query("
    SELECT m.*, s.nom AS service_nom
    FROM missions m
    LEFT JOIN services s ON m.service_id = s.id
    ORDER BY date_debut DESC
");
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fonction pour obtenir les personnels
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Historique des missions - RH</title>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; margin:20px; background:#f4f6f8;}
.header { text-align:center; margin-bottom:30px;}
.header img { max-width:120px; }
.header h1 { margin-top:10px; color:#2c3e50;}
table { width:100%; border-collapse:collapse; background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 4px 10px rgba(0,0,0,0.05);}
th, td { padding:12px; text-align:left; border-bottom:1px solid #ddd;}
th { background:#007b5e; color:#fff;}
tr:hover { background:#f1f1f1; }
.btn { padding:6px 12px; border:none; border-radius:5px; cursor:pointer; color:#fff; text-decoration:none; font-size:0.9rem; margin-right:5px;}
.btn-edit { background:#27ae60; }
.btn-edit:hover { background:#1f8b4d; }
.btn-view { background:#f39c12; }
.btn-view:hover { background:#d77f0f; }
.btn-delete { background:#c0392b; }
.btn-delete:hover { background:#922b1f; }
.status { padding:3px 8px; border-radius:5px; color:#fff; font-weight:bold; font-size:0.85rem;}
.status-ready { background:#27ae60; }
.status-pending { background:#f39c12; }
.status-locked { background:#7f8c8d; }
</style>
</head>
<body>

<div class="header">
    <img src="images/logo.jpg" alt="Niger Telecoms">
    <h1>Historique des missions - RH</h1>
</div>

<table>
    <thead>
        <tr>
            <th>Titre</th>
            <th>Service</th>
            <th>Dates</th>
            <th>Personnels</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if(empty($missions)): ?>
            <tr><td colspan="6" style="text-align:center;">Aucune mission pour le moment</td></tr>
        <?php else: ?>
            <?php foreach($missions as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['titre']) ?></td>
                    <td><?= htmlspecialchars($m['service_nom'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($m['date_debut'].' → '.$m['date_fin']) ?></td>
                    <td><?= getPersonnelsInfo($pdo, $m['personnels']) ?></td>
                    <td>
                        <?php
                        if($m['rh_preparer'] == 1 && ($m['dg_valide_final'] ?? 0) != 1) {
                            echo "<span class='status status-ready'>Modifiable</span>";
                        } elseif(($m['dg_valide_final'] ?? 0) == 1) {
                            echo "<span class='status status-locked'>Validée DG</span>";
                        } else {
                            echo "<span class='status status-pending'>En attente RH</span>";
                        }
                        ?>
                    </td>
                    <td>
                        <?php if($m['rh_preparer'] == 1 && ($m['dg_valide_final'] ?? 0) != 1): ?>
                            <a class="btn btn-edit" href="modifier_mission.php?id=<?= $m['id'] ?>">Modifier</a>
                        <?php endif; ?>
                        <a class="btn btn-view" href="voir_mission.php?id=<?= $m['id'] ?>">Voir</a>
                        <a class="btn btn-delete" href="supprimer_mission.php?id=<?= $m['id'] ?>" onclick="return confirm('Confirmer la suppression ?');">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<p><a href="dashboard.php">← Retour au dashboard</a></p>

</body>
</html>
