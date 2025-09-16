<?php
session_start();
require 'config.php';

// Vérification que l'utilisateur est connecté et est DG
if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['user_nom']) || $_SESSION['role_id'] != 4) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- Récupérer les missions selon le workflow ---
/*
 Workflow :
 - Cas 1 : DG après Directeur de Service (dir_service_validation=1, dg_validation IS NULL)
 - Cas 2 : DG après DF (df_validation=1, mission lancée par DG, mission pas encore "En cours")
*/
$sql = "
    SELECT m.*, u.nom AS propose_par,
           mgr.nom AS manager_nom, m.manager_commentaire,
           dir.nom AS dir_nom, m.dir_commentaire,
           rh.nom AS rh_nom, m.rh_commentaire,
           df.nom AS df_nom, m.df_commentaire
    FROM missions m
    JOIN users u ON m.propose_par = u.id
    LEFT JOIN users mgr ON m.manager_id = mgr.id
    LEFT JOIN users dir ON m.dir_service_id = dir.id
    LEFT JOIN users rh ON m.rh_id = rh.id
    LEFT JOIN users df ON m.df_id = df.id
    WHERE (m.dir_service_validation=1 AND m.dg_validation IS NULL)
       OR (m.df_validation=1 AND m.dg_final_validation IS NULL)
    ORDER BY m.date_proposee DESC
";

$stmt = $pdo->query($sql);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Traitement Validation DG ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mission_id = (int)$_POST['mission_id'];
    $action     = $_POST['action'];
    $comment    = trim($_POST['commentaire']);

    // Cas 1 : Validation initiale DG
    if ($_POST['step'] === 'dg_initial') {
        if ($action === 'valider') {
            $sql = "UPDATE missions 
                    SET dg_validation=1, dg_commentaire=?, statut='Lancée', dg_id=? 
                    WHERE id=?";
            $pdo->prepare($sql)->execute([$comment, $user_id, $mission_id]);
        } else {
            $sql = "UPDATE missions 
                    SET dg_validation=0, dg_commentaire=?, statut='Rejetée', dg_id=? 
                    WHERE id=?";
            $pdo->prepare($sql)->execute([$comment, $user_id, $mission_id]);
        }
    }

    // Cas 2 : Validation finale DG
    if ($_POST['step'] === 'dg_final') {
        if ($action === 'valider') {
            $sql = "UPDATE missions 
                    SET dg_final_validation=1, dg_final_commentaire=?, statut='En cours', dg_id=? 
                    WHERE id=?";
            $pdo->prepare($sql)->execute([$comment, $user_id, $mission_id]);
        } else {
            $sql = "UPDATE missions 
                    SET dg_final_validation=0, dg_final_commentaire=?, statut='Rejetée', dg_id=? 
                    WHERE id=?";
            $pdo->prepare($sql)->execute([$comment, $user_id, $mission_id]);
        }
    }

    header("Location: dg_lancer_missions.php?success=1");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>DG - Lancer Missions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7; margin: 0; padding: 20px; }
        h1 { color: #2c3e50; }
        .mission { background: #fff; padding: 15px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);}
        .actions { margin-top: 10px; }
        textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px; }
        .valider { background: #27ae60; color: #fff; }
        .rejeter { background: #c0392b; color: #fff; }
        .details { margin: 10px 0; padding: 10px; background: #f0f0f0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1><i class="fas fa-user-tie"></i> Tableau de bord DG - Lancer Missions</h1>

    <?php if (isset($_GET['success'])): ?>
        <p style="color:green;">✅ Action enregistrée avec succès.</p>
    <?php endif; ?>

    <?php if (!$missions): ?>
        <p>Aucune mission en attente de votre décision.</p>
    <?php endif; ?>

    <?php foreach ($missions as $m): ?>
        <div class="mission">
            <h3><?= htmlspecialchars($m['objet']) ?> (<?= htmlspecialchars($m['destination']) ?>)</h3>
            <p><b>Proposée par :</b> <?= htmlspecialchars($m['propose_par']) ?>, 
               <b>Date :</b> <?= htmlspecialchars($m['date_proposee']) ?></p>
            <p><b>Durée :</b> <?= htmlspecialchars($m['date_debut']) ?> → <?= htmlspecialchars($m['date_fin']) ?></p>

            <?php if ($m['dir_service_validation'] == 1 && $m['dg_validation'] === null): ?>
                <!-- CAS 1 : Première validation DG -->
                <div class="details">
                    <p><b>Commentaire Directeur :</b> <?= nl2br(htmlspecialchars($m['dir_commentaire'])) ?></p>
                </div>
                <form method="post">
                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                    <input type="hidden" name="step" value="dg_initial">
                    <textarea name="commentaire" placeholder="Votre commentaire..."></textarea><br>
                    <div class="actions">
                        <button type="submit" name="action" value="valider" class="valider">Valider & Lancer</button>
                        <button type="submit" name="action" value="rejeter" class="rejeter">Rejeter</button>
                    </div>
                </form>

            <?php elseif ($m['df_validation'] == 1 && $m['dg_final_validation'] === null): ?>
                <!-- CAS 2 : Validation finale DG -->
                <div class="details">
                    <p><b>Manager :</b> <?= htmlspecialchars($m['manager_nom']) ?> - <?= nl2br(htmlspecialchars($m['manager_commentaire'])) ?></p>
                    <p><b>Directeur :</b> <?= htmlspecialchars($m['dir_nom']) ?> - <?= nl2br(htmlspecialchars($m['dir_commentaire'])) ?></p>
                    <p><b>RH :</b> <?= htmlspecialchars($m['rh_nom']) ?> - <?= nl2br(htmlspecialchars($m['rh_commentaire'])) ?></p>
                    <p><b>DF :</b> <?= htmlspecialchars($m['df_nom']) ?> - <?= nl2br(htmlspecialchars($m['df_commentaire'])) ?></p>
                    <hr>
                    <p><b>Personnel :</b> <?= htmlspecialchars($m['personnel']) ?></p>
                    <p><b>Logistique :</b> <?= htmlspecialchars($m['logistique']) ?></p>
                    <p><b>Budget programmé :</b> <?= htmlspecialchars($m['montant_prevu']) ?> FCFA</p>
                </div>
                <form method="post">
                    <input type="hidden" name="mission_id" value="<?= $m['id'] ?>">
                    <input type="hidden" name="step" value="dg_final">
                    <textarea name="commentaire" placeholder="Votre commentaire final..."></textarea><br>
                    <div class="actions">
                        <button type="submit" name="action" value="valider" class="valider">Approuver Définitivement</button>
                        <button type="submit" name="action" value="rejeter" class="rejeter">Rejeter</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</body>
</html>
