<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    header('Location: login.php');
    exit;
}

$role = $_SESSION['role_id'];
$nom = $_SESSION['user_nom'];

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE role_cible = ? ORDER BY date_envoi DESC");
$stmt->execute([$role]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Marquer comme vues après récupération
$update = $pdo->prepare("UPDATE notifications SET vue = 1 WHERE role_cible = ? AND vue = 0");
$update->execute([$role]);


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>🔔 Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <h3 class="mb-4">🔔 Notifications pour <?= htmlspecialchars($nom) ?></h3>

        <?php if (empty($notifications)): ?>
            <div class="alert alert-secondary">Aucune notification.</div>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($notifications as $notif): ?>
                    <li class="list-group-item<?= $notif['vue'] ? '' : ' list-group-item-warning' ?>">
                        <strong>[Mission M-<?= str_pad($notif['id_mission'], 4, '0', STR_PAD_LEFT) ?>]</strong>
                        <?= htmlspecialchars($notif['message']) ?>
                        <br><small class="text-muted"><?= $notif['date_envoi'] ?></small>
                        <a href="traiter_mission.php?id=<?= $notif['id_mission'] ?>" class="btn btn-sm btn-outline-primary float-end">Voir</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</body>
</html>