<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$role = $_SESSION['role_id'];

switch ($role) {
    case 2:
        $condition = "manager_validation IS NULL";
        break;
    case 3:
        $condition = "manager_validation = 1 AND dir_service_validation IS NULL";
        break;
    case 4:
        $condition = "manager_validation = 1 AND dir_service_validation = 1 AND dg_validation IS NULL";
        break;
    case 5:
        $condition = "lancement = 1 AND rh_preparer = 0";
        break;
    case 6:
        $condition = "lancement = 1 AND rh_preparer = 1 AND df_valide = 0";
        break;
    default:
        $condition = "0=1";
        break;
}

$stmt = $pdo->query("SELECT id, titre FROM missions WHERE $condition ORDER BY date_debut ASC LIMIT 5");
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($missions);
