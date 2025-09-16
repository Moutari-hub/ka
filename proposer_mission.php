<?php
session_start();
require 'config.php';

// Vérification utilisateur connecté + rôle Chef de Service (id=1)
if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['service_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$service_id = $_SESSION['service_id'];
$message = "";

// Récupérer les types existants pour ce service
try {
    $stmt = $pdo->prepare("SELECT DISTINCT type_mission FROM missions WHERE service_id = ?");
    $stmt->execute([$service_id]);
    $types = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Erreur récupération types de mission : " . $e->getMessage());
}

// Traitement formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre'] ?? '');
    $type_mission_select = trim($_POST['type_mission'] ?? '');
    $nouveau_type = trim($_POST['nouveau_type'] ?? '');
    $zone_mission = trim($_POST['zone_mission'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $type_mission = $nouveau_type !== '' ? $nouveau_type : $type_mission_select;

    if ($titre === '' || $type_mission === '' || $zone_mission === '' || $description === '') {
        $message = "<p class='error'>Veuillez remplir tous les champs obligatoires.</p>";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO missions
                (titre, type_mission, zone_mission, description, propose_par, statut, service_id, date_proposition)
                VALUES (?, ?, ?, ?, ?, 'En attente Manager', ?, NOW())");
            $stmt->execute([$titre, $type_mission, $zone_mission, $description, $user_id, $service_id]);
            $message = "<p class='success'>Mission proposée avec succès.</p>";
            header("Refresh:2");
        } catch (PDOException $e) {
            $message = "<p class='error'>Erreur lors de l'insertion : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Proposer une mission</title>
<style>
    body { font-family: "Times New Roman", serif; background:#f8f9fa; padding: 30px; }
    .container { max-width: 700px; margin: auto; background: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);}
    h2 { text-align: center; color: #007b5e; margin-bottom: 20px; }
    label { font-weight: bold; display: block; margin-top: 15px; }
    input[type=text], select, textarea {
        width: 100%; padding: 10px; margin-top: 5px;
        border: 1px solid #ced4da; border-radius: 4px; font-size: 15px;
        box-sizing: border-box;
    }
    textarea { resize: vertical; }
    button {
        margin-top: 25px; width: 100%; background: #007b5e; border:none; padding: 12px;
        color: white; font-size: 16px; border-radius: 5px; cursor: pointer;
    }
    button:hover { background: #005f46; }
    .success { background: #d4edda; color: #155724; padding: 12px; border-left: 5px solid #28a745; margin-top: 20px; }
    .error { background: #f8d7da; color: #721c24; padding: 12px; border-left: 5px solid #dc3545; margin-top: 20px; }
    .note { font-size: 0.9em; color: #666; margin-top: 5px;}
    .logo { display: block; margin: 0 auto 20px; width: 150px; }
    .btn-retour { background: #6c757d; margin-top: 10px; }
    .btn-retour:hover { background: #5a6268; }
</style>
</head>
<body>
<div class="container">
    <img src="images/logo.jpg" alt="Logo" class="logo">
    <h2>Proposer une mission</h2>

    <?= $message ?>

    <form method="POST" autocomplete="off">
        <label for="titre">Titre de la mission :</label>
        <input type="text" name="titre" id="titre" placeholder="Titre de la mission" required />

        <label for="type_mission">Type de mission existant :</label>
        <select name="type_mission" id="type_mission">
            <option value="">-- Sélectionnez un type --</option>
            <?php foreach ($types as $type): ?>
                <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
            <?php endforeach; ?>
        </select>

        <div class="note">Ou saisissez un nouveau type de mission :</div>
        <input type="text" name="nouveau_type" placeholder="Nouveau type de mission (ex: Audit interne)" />

        <label for="zone_mission">Lieu de la mission :</label>
        <input type="text" name="zone_mission" id="zone_mission" required />

        <label for="description">Objet de la mission :</label>
        <textarea name="description" id="description" rows="4" required></textarea>

        <button type="submit">Proposer la mission</button>
    </form>

    <form action="dashboard.php" method="get">
        <button type="submit" class="btn-retour">Retour au tableau de bord</button>
    </form>
</div>
</body>
</html>
