<?php
// functions.php

// Récupérer le nom d’un rôle
function getRoleName(int $role_id): string {
    $roles = [
        1 => "Chef de Service",
        2 => "Manager",
        3 => "Directeur de Service",
        4 => "Directeur Général",
        5 => "Ressources Humaines",
        6 => "Directeur Financier"
    ];
    return $roles[$role_id] ?? "Inconnu";
}

// Récupérer le nom d’un service
function getServiceName(int $service_id, PDO $pdo): string {
    $stmt = $pdo->prepare("SELECT nom FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    return $stmt->fetchColumn() ?: "Service inconnu";
}

// Insérer une notification
function insererNotification(PDO $pdo, int $mission_id, int $role_id, string $message): bool {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (mission_id, role_id, message, date_envoi, lu)
        VALUES (?, ?, ?, NOW(), 0)
    ");
    return $stmt->execute([$mission_id, $role_id, $message]);
}

// Marquer une notification comme lue
function marquerNotificationLue(PDO $pdo, int $notification_id): bool {
    $stmt = $pdo->prepare("UPDATE notifications SET lu = 1 WHERE id = ?");
    return $stmt->execute([$notification_id]);
}
