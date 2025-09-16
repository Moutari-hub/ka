<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['service_id'])) {
    header('Location: login.php');
    exit;
}

$user_service_id = $_SESSION['service_id'];

// Récupérer la liste des personnels du service de l'utilisateur
$stmt = $pdo->prepare("SELECT * FROM personnels WHERE service_id = ? ORDER BY nom, prenom");
$stmt->execute([$user_service_id]);
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Gestion des personnels</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 900px; margin: auto; padding: 20px;}
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px;}
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left;}
    th { background: #f0f0f0; }
    a.button { background: #007b5e; color: white; padding: 8px 12px; border-radius: 4px; text-decoration: none;}
    a.button:hover { background: #005f46; }
</style>
</head>
<body>
<h2>Gestion des personnels - Service ID: <?= htmlspecialchars($user_service_id) ?></h2>
<a href="personnel_ajouter.php" class="button">Ajouter un personnel</a>
<table>
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Poste</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($personnels) === 0): ?>
            <tr><td colspan="6">Aucun personnel dans votre service.</td></tr>
        <?php else: ?>
            <?php foreach ($personnels as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['nom']) ?></td>
                    <td><?= htmlspecialchars($p['prenom']) ?></td>
                    <td><?= htmlspecialchars($p['poste']) ?></td>
                    <td><?= htmlspecialchars($p['email']) ?></td>
                    <td><?= htmlspecialchars($p['telephone']) ?></td>
                    <td>
                        <a href="personnel_modifier.php?id=<?= $p['id'] ?>">Modifier</a> | 
                        <a href="personnel_supprimer.php?id=<?= $p['id'] ?>" onclick="return confirm('Confirmer la suppression ?')">Supprimer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>
</body>
</html>
