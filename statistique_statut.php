<?php
session_start();
require 'config.php';

// Vérification connexion utilisateur
if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['user_nom'])) {
    header('Location: login.php');
    exit;
}

// Récupération des stats par statut
$sql = "SELECT statut, COUNT(*) as total 
        FROM missions 
        GROUP BY statut 
        ORDER BY FIELD(statut, 'En attente', 'Validée', 'Lancée', 'En cours', 'Terminée', 'Annulée')";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Statistiques Missions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f6f8;
            margin: 0;
            padding: 0;
        }
        .container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        table {
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 6px;
            overflow: hidden;
            min-width: 400px;
        }
        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            text-align: center;
        }
        th {
            background-color: #007B5F;
            color: white;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .title {
            text-align: center;
            padding: 20px;
            font-size: 1.4rem;
            color: #333;
        }
    </style>
</head>
<body>

<div class="title">📊 Statistiques des missions par statut</div>
<div class="container">
    <table>
        <tr>
            <th>Statut</th>
            <th>Nombre</th>
        </tr>
        <?php if (!empty($stats)): ?>
            <?php foreach ($stats as $row): ?>
                <tr>
                    <td><?= htmlspecialchars($row['statut'] ?: 'Inconnu') ?></td>
                    <td><?= (int) $row['total'] ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="2">Aucune mission trouvée.</td>
            </tr>
        <?php endif; ?>
    </table>
</div>

</body>
</html>
