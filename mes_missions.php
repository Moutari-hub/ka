<?php
session_start();
require 'config.php'; // contient la connexion $pdo

// Vérification session
if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['service_id'])) {
    header('Location: login.php');
    exit;
}

$user_service_id = (int) $_SESSION['service_id'];

// Récupération des missions du service
$sql = "SELECT id, titre, type_mission, zone_mission, date_debut, date_fin, statut 
        FROM missions 
        WHERE service_id = :service_id
        ORDER BY date_debut DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['service_id' => $user_service_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Mes missions - Niger Telecoms</title>
<style>
    body { font-family: Arial, sans-serif; margin: 2rem; background:#f9f9f9; color:#333; }
    h1 { color: #007a33; }
    table { border-collapse: collapse; width: 100%; background: white; box-shadow: 0 0 5px rgba(0,0,0,0.1); margin-top: 1rem; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #007a33; color: white; }
    tr:nth-child(even) { background: #f2f2f2; }
    a.btn {
        display: inline-block; padding: 6px 12px; background: #f7941d; color: white; text-decoration: none; border-radius: 4px; font-weight: bold;
    }
    a.btn:hover { background: #e07b14; }
    .btn-back { margin-bottom: 1rem; }
</style>
</head>
<body>

<!-- Bouton retour -->
<a href="dashboard.php" class="btn btn-back">← Retour au tableau de bord</a>

<h1>Mes missions - Service <?= htmlspecialchars($user_service_id) ?></h1>

<?php if (empty($missions)): ?>
    <p>Aucune mission trouvée pour votre service.</p>
<?php else: ?>
    <table aria-label="Liste des missions de votre service">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Type</th>
                <th>Zone</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($missions as $m): ?>
            <tr>
                <td><?= htmlspecialchars($m['titre']) ?></td>
                <td><?= htmlspecialchars($m['type_mission']) ?></td>
                <td><?= htmlspecialchars($m['zone_mission']) ?></td>
                <td><?= htmlspecialchars($m['date_debut']) ?></td>
                <td><?= htmlspecialchars($m['date_fin']) ?></td>
                <td><?= htmlspecialchars($m['statut']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

</body>
</html>
