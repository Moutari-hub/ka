<?php
session_start();
require 'config.php';
require 'functions.php';

if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$notification_id = (int)($_POST['notif_id'] ?? 0);
$mission_id = (int)($_POST['mission_id'] ?? 0);
$decision = $_POST['action'] ?? '';
$commentaire = trim($_POST['commentaire'] ?? '');

if (!$notification_id || !$mission_id || !in_array($decision, ['valider', 'rejeter'])) {
    $_SESSION['error'] = "Données invalides.";
    header('Location: dashboard.php');
    exit;
}

if ($decision === 'rejeter' && empty($commentaire)) {
    $_SESSION['error'] = "Veuillez saisir un commentaire pour un rejet.";
    header('Location: dashboard.php');
    exit;
}

// Récupérer mission
$stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ?");
$stmt->execute([$mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    $_SESSION['error'] = "Mission introuvable.";
    header('Location: dashboard.php');
    exit;
}

$updateFields = [];
$params = [];

switch ($role) {
    case 2: // Manager
        $updateFields[] = "manager_validation = " . ($decision === 'valider' ? 1 : 0);
        $updateFields[] = "manager_commentaire = :commentaire";
        $params[':commentaire'] = $commentaire;
        break;

    case 3: // Directeur service
        if ($mission['manager_validation'] != 1) {
            $_SESSION['error'] = "La mission doit être validée par le manager d'abord.";
            header('Location: dashboard.php');
            exit;
        }
        $updateFields[] = "dir_service_validation = " . ($decision === 'valider' ? 1 : 0);
        $updateFields[] = "dir_service_commentaire = :commentaire";
        $params[':commentaire'] = $commentaire;
        break;

    case 4: // DG
        if ($mission['dir_service_validation'] != 1) {
            $_SESSION['error'] = "La mission doit être validée par le directeur de service d'abord.";
            header('Location: dashboard.php');
            exit;
        }
        if ($decision === 'valider') {
            $updateFields[] = "dg_validation = 1";
            $updateFields[] = "lancement = 1";
            $updateFields[] = "statut = 'Lancée'";
        } else {
            $updateFields[] = "dg_validation = 0";
        }
        $updateFields[] = "dg_commentaire = :commentaire";
        $params[':commentaire'] = $commentaire;
        break;

    default:
        $_SESSION['error'] = "Non autorisé.";
        header('Location: dashboard.php');
        exit;
}

$sql = "UPDATE missions SET " . implode(", ", $updateFields) . " WHERE id = :id";
$params[':id'] = $mission_id;

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Marquer notification comme lue
    $stmt = $pdo->prepare("UPDATE notifications SET lu = 1 WHERE id = ?");
    $stmt->execute([$notification_id]);

    // Notification suivante
    if ($decision === 'valider') {
        $nextRole = null;
        $msg = "";

        if ($role == 2) {
            $nextRole = 3;
            $msg = "Mission validée par Manager, en attente validation Directeur Service.";
        } elseif ($role == 3) {
            $nextRole = 4;
            $msg = "Mission validée par Directeur Service, en attente validation DG.";
        } elseif ($role == 4) {
            $nextRole = 5;
            $msg = "Mission lancée par DG, en attente préparation RH.";
        }

        if ($nextRole) {
            $stmt = $pdo->prepare("INSERT INTO notifications (mission_id, role_id, message) VALUES (:mid, :rid, :msg)");
            $stmt->execute([
                ':mid' => $mission_id,
                ':rid' => $nextRole,
                ':msg' => $msg
            ]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE missions SET statut = 'Rejetée' WHERE id = ?");
        $stmt->execute([$mission_id]);
    }

    $pdo->commit();
    $_SESSION['success'] = "Mission traitée.";
    header('Location: dashboard.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Erreur : " . $e->getMessage();
    header('Location: dashboard.php');
    exit;
}
