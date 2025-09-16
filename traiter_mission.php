<?php
session_start();
require 'config.php';

// Fonction : insérer une notification pour tous les utilisateurs d’un rôle
function insererNotification(PDO $pdo, int $mission_id, int $role_id, string $message) {
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE role_id = ?");
    $stmt->execute([$role_id]);
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$utilisateurs) {
        return; // Aucun utilisateur trouvé
    }

    $stmt = $pdo->prepare("INSERT INTO notifications (utilisateur_id, mission_id, role_id, message, lu, date_envoi) VALUES (?, ?, ?, ?, 0, NOW())");
    foreach ($utilisateurs as $uid) {
        $stmt->execute([$uid, $mission_id, $role_id, $message]);
    }
}

// Vérification session
if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    header('Location: login.php');
    exit;
}

$role = (int)$_SESSION['role_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mission_id = (int)($_POST['mission_id'] ?? 0);
    $decision = $_POST['decision'] ?? '';
    $commentaire = trim($_POST['commentaire'] ?? '');

    if (!$mission_id || !in_array($decision, ['valider', 'rejeter'])) {
        die("Données invalides.");
    }

    if ($decision === 'rejeter' && empty($commentaire)) {
        die("Le commentaire est obligatoire en cas de rejet.");
    }

    // Définir les colonnes à modifier et le rôle suivant
    switch ($role) {
        case 2: // Manager
            $field_validation = 'manager_validation';
            $field_commentaire = 'commentaire_manager';
            $next_role = 3;
            $msg_valide = "Mission validée par Manager. En attente du Directeur de Service.";
            break;

        case 3: // Directeur de Service
            $field_validation = 'dir_service_validation';
            $field_commentaire = 'commentaire_dir';
            $next_role = 4;
            $msg_valide = "Mission validée par Directeur de Service. En attente du DG.";
            break;

        case 4: // DG
            $field_validation = 'dg_validation';
            $field_commentaire = 'commentaire_dg';
            $next_role = 5;
            $msg_valide = "Mission lancée par le DG. RH doit préparer la mission.";
            break;

        case 5: // RH
            $field_validation = 'rh_preparer';
            $field_commentaire = 'commentaire_rh';
            $next_role = 6;
            $msg_valide = "Mission préparée par RH. En attente du DF.";
            break;

        case 6: // DF
            $field_validation = 'df_valide';
            $field_commentaire = 'commentaire_df';
            $next_role = 1; // Retour à collaborateur (ou aucun)
            $msg_valide = "Mission validée par le DF. Elle est maintenant en cours.";
            break;

        default:
            die("Rôle non autorisé.");
    }

    $valide = ($decision === 'valider') ? 1 : 0;

    try {
        $pdo->beginTransaction();

        // Mise à jour de la mission
        $stmt = $pdo->prepare("UPDATE missions SET $field_validation = ?, $field_commentaire = ? WHERE id = ?");
        $stmt->execute([$valide, $commentaire, $mission_id]);

        // Si le DG valide, on lance la mission
        if ($role === 4 && $valide) {
            $pdo->prepare("UPDATE missions SET lancement = 1, statut = 'Lancée' WHERE id = ?")->execute([$mission_id]);
        }

        // Si le DF valide, la mission passe à "En cours"
        if ($role === 6 && $valide) {
            $pdo->prepare("UPDATE missions SET statut = 'En cours' WHERE id = ?")->execute([$mission_id]);
        }

        // Insertion de notification
        if ($valide) {
            insererNotification($pdo, $mission_id, $next_role, $msg_valide);
        } else {
            insererNotification($pdo, $mission_id, 1, "Mission rejetée par un responsable avec commentaire : $commentaire");
            // Remettre le statut à "Rejetée"
            $pdo->prepare("UPDATE missions SET statut = 'Rejetée' WHERE id = ?")->execute([$mission_id]);
        }

        $pdo->commit();
        header("Location: dashboard.php?success=1");
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur : " . htmlspecialchars($e->getMessage()));
    }
} else {
    die("Méthode non autorisée.");
}
