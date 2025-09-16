<?php
session_start();
require 'config.php';

// Vérifier que l'utilisateur est RH (role_id = 5)
if (!isset($_SESSION['user_id'], $_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$service_id = $_SESSION['service_id'] ?? null;
$errors = [];
$success = "";

// Récupérer les services pour la sélection
try {
    $stmtServices = $pdo->query("SELECT id, nom FROM services ORDER BY nom");
    $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des services : " . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre         = trim($_POST['titre'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $type_mission  = trim($_POST['type_mission'] ?? '');
    $zone_mission  = trim($_POST['zone_mission'] ?? '');
    $date_debut    = $_POST['date_debut'] ?? '';
    $date_fin      = $_POST['date_fin'] ?? '';
    $service_id    = intval($_POST['service_id'] ?? 0);
    $montant_prevu = floatval($_POST['montant_prevu'] ?? 0);

    // Validation simple
    if ($titre === '') $errors[] = "Le titre est obligatoire.";
    if ($description === '') $errors[] = "La description est obligatoire.";
    if ($type_mission === '') $errors[] = "Le type de mission est obligatoire.";
    if ($zone_mission === '') $errors[] = "La zone de mission est obligatoire.";
    if ($date_debut === '') $errors[] = "La date de début est obligatoire.";
    if ($date_fin === '') $errors[] = "La date de fin est obligatoire.";
    if ($service_id <= 0) $errors[] = "Veuillez choisir un service.";
    if ($montant_prevu <= 0) $errors[] = "Le montant prévu doit être supérieur à 0.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO missions
                (titre, description, type_mission, zone_mission, date_debut, date_fin, propose_par, service_id, montant_prevu, dg_validation, statut)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, 'En attente validation DG')
            ");
            $stmt->execute([
                $titre,
                $description,
                $type_mission,
                $zone_mission,
                $date_debut,
                $date_fin,
                $user_id,     // RH qui propose
                $service_id,
                $montant_prevu
            ]);

            $success = "Mission envoyée pour validation au DG ✅";
        } catch (PDOException $e) {
            $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8" />
<title>Demande de mission - RH</title>
<style>
    body { font-family: Arial, sans-serif; max-width: 700px; margin: 30px auto; background: #f9f9f9; padding: 20px; border-radius: 8px; }
    h1 { text-align: center; color: #007b5e; }
    label { display: block; margin-top: 15px; font-weight: bold; }
    input[type=text], input[type=date], input[type=number], select, textarea {
        width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; border: 1px solid #ccc; border-radius: 4px;
    }
    textarea { resize: vertical; height: 80px; }
    button {
        margin-top: 20px; padding: 12px 20px; background-color: #007b5e; border: none; color: white; font-weight: bold; border-radius: 5px;
        cursor: pointer;
    }
    button:hover { background-color: #005f46; }
    .error { background-color: #f8d7da; color: #842029; padding: 10px; border-radius: 5px; margin-top: 15px; }
    .success { background-color: #d1e7dd; color: #0f5132; padding: 10px; border-radius: 5px; margin-top: 15px; }
</style>
</head>
<body>

<h1>Demande de mission (RH)</h1>

<?php if (!empty($errors)): ?>
    <div class="error">
        <ul>
        <?php foreach ($errors as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
        <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success !== ''): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" action="">
    <label for="titre">Titre *</label>
    <input type="text" id="titre" name="titre" required value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>">

    <label for="description">Description *</label>
    <textarea id="description" name="description" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>

    <label for="type_mission">Type de mission *</label>
    <input type="text" id="type_mission" name="type_mission" required value="<?= htmlspecialchars($_POST['type_mission'] ?? '') ?>">

    <label for="zone_mission">Zone de mission *</label>
    <input type="text" id="zone_mission" name="zone_mission" required value="<?= htmlspecialchars($_POST['zone_mission'] ?? '') ?>">

    <label for="date_debut">Date de début *</label>
    <input type="date" id="date_debut" name="date_debut" required value="<?= htmlspecialchars($_POST['date_debut'] ?? '') ?>">

    <label for="date_fin">Date de fin *</label>
    <input type="date" id="date_fin" name="date_fin" required value="<?= htmlspecialchars($_POST['date_fin'] ?? '') ?>">

    <label for="service_id">Service concerné *</label>
    <select id="service_id" name="service_id" required>
        <option value="">-- Sélectionnez un service --</option>
        <?php foreach ($services as $srv): ?>
            <option value="<?= $srv['id'] ?>" <?= (isset($_POST['service_id']) && $_POST['service_id'] == $srv['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($srv['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="montant_prevu">Montant prévu (FCFA) *</label>
    <input type="number" step="0.01" id="montant_prevu" name="montant_prevu" required value="<?= htmlspecialchars($_POST['montant_prevu'] ?? '') ?>">

    <button type="submit">Envoyer la demande</button>
</form>

</body>
</html>
