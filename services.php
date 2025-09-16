<?php
session_start();
require 'config.php';

// Vérifier si DG connecté
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header("Location: login.php");
    exit;
}

// Ajout / modification / suppression
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $nom = trim($_POST['nom'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($nom === '') {
        $message = "⚠️ Le nom du service est obligatoire.";
    } else {
        try {
            if ($action === 'ajouter') {
                $stmt = $pdo->prepare("INSERT INTO services (nom) VALUES (?)");
                $stmt->execute([$nom]);
                $message = "✅ Service ajouté avec succès.";
            } elseif ($action === 'modifier') {
                $stmt = $pdo->prepare("UPDATE services SET nom=? WHERE id=?");
                $stmt->execute([$nom, $id]);
                $message = "✅ Service modifié avec succès.";
            } elseif ($action === 'supprimer') {
                $stmt = $pdo->prepare("DELETE FROM services WHERE id=?");
                $stmt->execute([$id]);
                $message = "✅ Service supprimé avec succès.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "❌ Ce nom de service existe déjà.";
            } else {
                $message = "❌ Erreur : " . $e->getMessage();
            }
        }
    }
}

// Récupérer tous les services
$services = $pdo->query("SELECT * FROM services ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des Services</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 30px; }
    .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .logo { display: block; margin: 0 auto 15px; width: 120px; }
    h2 { text-align: center; color: #007a33; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { padding: 10px; border-bottom: 1px solid #ccc; text-align: left; }
    input[type="text"] { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc; }
    .form-container { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 6px; }
    .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; color: white; display: inline-block; }
    .btn-add { background: #f7941d; }
    .btn-edit { background: #007a33; }
    .btn-delete { background: #c0392b; }
    .btn-dashboard { background: #555; margin-bottom: 10px; }
    .message { margin: 10px 0; padding: 10px; border-radius: 5px; }
    .success { background: #dfd; color: #060; }
    .error { background: #fdd; color: #900; }
    form { display: inline; }
</style>
</head>
<body>

<div class="container">
    <img src="images/logo.jpg" class="logo" alt="Logo">
    <h2>Gestion des Services</h2>

    <a href="dashboard.php" class="btn btn-dashboard"><i class="fas fa-arrow-left"></i> Retour au Dashboard</a>

    <?php if ($message): ?>
        <div class="message <?= strpos($message,'✅')===0?'success':'error' ?>"><?= $message ?></div>
    <?php endif; ?>

    <?php if(isset($_GET['modifier'])): 
        $edit_id = (int)$_GET['modifier'];
        $service_edit = $pdo->prepare("SELECT * FROM services WHERE id=?");
        $service_edit->execute([$edit_id]);
        $s = $service_edit->fetch(PDO::FETCH_ASSOC);
    ?>
    <div class="form-container">
        <h3>Modifier le service</h3>
        <form method="post">
            <input type="hidden" name="id" value="<?= $s['id'] ?>">
            <input type="hidden" name="action" value="modifier">
            <input type="text" name="nom" value="<?= htmlspecialchars($s['nom']) ?>" required>
            <button type="submit" class="btn btn-edit"><i class="fas fa-save"></i> Enregistrer</button>
            <a href="services.php" class="btn btn-dashboard"><i class="fas fa-arrow-left"></i> Retour</a>
        </form>
    </div>
    <?php else: ?>
    <div class="form-container">
        <h3>Ajouter un service</h3>
        <form method="post">
            <input type="hidden" name="action" value="ajouter">
            <input type="text" name="nom" placeholder="Nom du service" required>
            <button type="submit" class="btn btn-add"><i class="fas fa-plus-circle"></i> Ajouter</button>
        </form>
    </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr><th>Nom</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach($services as $s): ?>
            <tr>
                <td><?= htmlspecialchars($s['nom']) ?></td>
                <td>
                    <a href="services.php?modifier=<?= $s['id'] ?>" class="btn btn-edit"><i class="fas fa-edit"></i> Modifier</a>
                    <form method="post" onsubmit="return confirm('Supprimer ce service ?');">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn btn-delete"><i class="fas fa-trash"></i> Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
