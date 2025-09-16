<?php
session_start();
require 'config.php';
if (!isset($_SESSION['role_id'])) exit;

$personnel_id = intval($_GET['personnel_id'] ?? 0);
if ($personnel_id <= 0) {
    echo "<p>Personnel non valide.</p>";
    exit;
}

// Récupérer les missions où le personnel est impliqué
$stmt = $pdo->prepare("
    SELECT id, titre, statut, manager_validation, dir_service_validation, df_valide, dg_validation, lancement
    FROM missions
    WHERE FIND_IN_SET(:id, personnels)  -- si la colonne 'personnels' contient la liste des IDs
       OR propose_par = :id
    ORDER BY id DESC
");
$stmt->execute([':id' => $personnel_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($missions)) {
    echo "<p>Ce personnel n'est actuellement affecté à aucune mission et n'a pas d'historique.</p>";
} else {
    echo "<p>Ce personnel a participé aux missions suivantes :</p>";
    echo "<table style='width:100%; border-collapse:collapse;'>";
    echo "<tr><th>#</th><th>Titre</th><th>Statut actuel</th></tr>";
    foreach ($missions as $index => $m) {
        echo "<tr>";
        echo "<td>".($index+1)."</td>";
        echo "<td>".htmlspecialchars($m['titre'])."</td>";
        echo "<td>".htmlspecialchars($m['statut'])."</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
