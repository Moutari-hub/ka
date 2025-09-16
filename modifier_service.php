<?php
session_start();
require 'config.php';

if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [4,5])) {
    header('Location: login.php');
    exit;
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: services_liste.php');
    exit;
}

$message = '';

// Récupérer service
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$service) {
    header('Location: services_liste.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description'] ?? '');
    $type_mission = trim($_POST['type_mission'] ?? '');

    if ($nom) {
        $stmt = $pdo->prepare("UPDATE services SET nom = ?, description = ?, type_mission = ? WHERE id = ?");
        if ($stmt->execute([$nom, $description, $type_mission, $id])) {
            $message = "✅ Service mis à jour avec succès.";
            $service['nom'] = $nom;
            $service['description'] = $description;
            $service['type_mission'] = $type_mission;
        } else {
            $message = "❌ Erreur lors de la mise à jour.";
        }
    } else {
        $message = "⚠️ Le nom du service est obligatoire.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Modifier service</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 30px; }
.form-container { max-width: 500px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin: auto; }
label { display: block; margin-bottom: 8px; font-weight: bold; }
input[type="text"], textarea, select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; }
textarea { resize: vertical; }
button { background: #f7941d; color: white; padding: 10px 20px; border: none; font-weight: bold; border-radius: 4px; cursor: pointer; }
button:hover { background: #e67e00; }
.message { margin-bottom: 15px; font-weight: bold; color: #007a33; }
a.back { display: inline-block; margin-bottom: 15px; text-decoration: none; color: #007a33; }
</style>
</head>
<body>

<div class="form-container">
    <a href="services_liste.php" class="back"><i class="fas fa-arrow-left"></i> Retour à la liste</a>

    <h2><i class="fas fa-edit"></i> Modifier un service</h2>

    <?php if ($message): ?>
        <div class="message"><?= $message ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="nom">Nom du service *</label>
        <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($service['nom']) ?>" required>

        <label for="description">Description</label>
        <textarea id="description" name="description" rows="4"><?= htmlspecialchars($service['description'] ?? '') ?></textarea>

        <label for="type_mission">Type de mission</label>
        <select id="type_mission" name="type_mission">
            <option value="">-- Sélectionner --</option>
            <option value="Interne" <?= ($service['type_mission'] ?? '') == 'Interne' ? 'selected' : '' ?>>Interne</option>
            <option value="Externe" <?= ($service['type_mission'] ?? '') == 'Externe' ? 'selected' : '' ?>>Externe</option>
            <option value="Urgente" <?= ($service['type_mission'] ?? '') == 'Urgente' ? 'selected' : '' ?>>Urgente</option>
        </select>

        <button type="submit"><i class="fas fa-save"></i> Enregistrer</button>
    </form>
</div>


</body>
</html>
