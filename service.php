<?php
session_start();
require 'config.php';

// Vérifier que l'utilisateur est DG
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header("Location: login.php");
    exit;
}

$message = '';
$error = '';
$edit_id = intval($_GET['edit_id'] ?? 0);

// Traitement ajout / modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_service = trim($_POST['nom_service']);
    $description = trim($_POST['description']);

    if ($nom_service === '') {
        $error = "Le nom du service est obligatoire.";
    } else {
        if (!empty($_POST['edit_id'])) {
            // Modifier
            $stmt = $pdo->prepare("UPDATE services SET nom=?, description=? WHERE id=?");
            $stmt->execute([$nom_service, $description, intval($_POST['edit_id'])]);
            $message = "✅ Service modifié avec succès.";
        } else {
            // Ajouter
            $stmt = $pdo->prepare("INSERT INTO services (nom, description) VALUES (?, ?)");
            $stmt->execute([$nom_service, $description]);
            $message = "✅ Service ajouté avec succès.";
        }
    }
}

// Suppression
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM services WHERE id=?");
    $stmt->execute([$delete_id]);
    $message = "✅ Service supprimé.";
}

// Liste des services
$services = $pdo->query("SELECT * FROM services ORDER BY nom")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des services</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body { font-family: Arial; background:#f4f4f4; margin:0; padding:20px; }
.container { max-width:900px; margin:auto; background:white; padding:20px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h2 { text-align:center; color:#007a33; }
form { margin-bottom:30px; }
input[type=text], textarea { width:100%; padding:10px; margin:5px 0 15px 0; border-radius:5px; border:1px solid #ccc; }
textarea { resize: vertical; }
button { background:#f7941d; color:white; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; }
table { width:100%; border-collapse:collapse; margin-top:20px;}
th, td { border-bottom:1px solid #ccc; padding:10px; text-align:left;}
a { text-decoration:none; color:white; padding:5px 10px; border-radius:4px; }
a.edit { background:#007a33; }
a.delete { background:#d9534f; }
.message { background:#dfd; padding:10px; margin-bottom:15px; border-radius:5px; color:#060; }
.error { background:#fdd; padding:10px; margin-bottom:15px; border-radius:5px; color:#900; }
.logo { display:block; margin:auto; width:120px; margin-bottom:20px; }
</style>
</head>
<body>
<div class="container">
<img src="images/logo.jpg" alt="Logo" class="logo">
<h2>Gestion des services</h2>

<?php if ($message) echo "<div class='message'>$message</div>"; ?>
<?php if ($error) echo "<div class='error'>$error</div>"; ?>

<form method="post">
    <input type="hidden" name="edit_id" value="<?= $edit_id ?>">
    <label>Nom du service *</label>
    <input type="text" name="nom_service" value="<?= htmlspecialchars($_GET['edit_nom'] ?? '') ?>" required>
    <label>Description</label>
    <textarea name="description" rows="3"><?= htmlspecialchars($_GET['edit_description'] ?? '') ?></textarea>
    <button type="submit"><i class="fas fa-check-circle"></i> Enregistrer</button>
    <a href="dashboard.php" style="background:#007a33; margin-left:10px; padding:10px 20px; border-radius:5px; color:white;">Retour</a>
</form>

<h2>Liste des services</h2>
<table>
<thead>
<tr><th>Nom</th><th>Description</th><th>Actions</th></tr>
</thead>
<tbody>
<?php foreach($services as $s): ?>
<tr>
    <td><?= htmlspecialchars($s['nom']) ?></td>
    <td><?= htmlspecialchars($s['description']) ?></td>
    <td>
        <a class="edit" href="?edit_id=<?= $s['id'] ?>&edit_nom=<?= urlencode($s['nom']) ?>&edit_description=<?= urlencode($s['description']) ?>">Modifier</a>
        <a class="delete" href="?delete_id=<?= $s['id'] ?>" onclick="return confirm('Supprimer ce service ?')">Supprimer</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</body>
</html>
