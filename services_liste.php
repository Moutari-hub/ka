<?php
session_start();
require 'config.php';

// Vérification autorisation (seulement DG ou RH)
if (!isset($_SESSION['role_id']) || !in_array($_SESSION['role_id'], [4,5])) {
    header('Location: login.php');
    exit;
}

$message = '';

// Suppression
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    // Supprimer service
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = "✅ Service supprimé.";
    } else {
        $message = "❌ Erreur lors de la suppression.";
    }
}

// Récupération services
$services = $pdo->query("SELECT * FROM services ORDER BY nom_service ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <title>Liste des services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 30px; }
        table { border-collapse: collapse; width: 100%; background: white; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        th { background: #007a33; color: white; }
        a.btn { text-decoration: none; color: white; padding: 6px 12px; border-radius: 4px; font-weight: bold; }
        a.edit { background: #f7941d; }
        a.delete { background: #d9534f; }
        .message { margin-bottom: 20px; font-weight: bold; color: #007a33; }
        .top-link { margin-bottom: 20px; display: inline-block; background: #007a33; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; }
    </style>
</head>
<body>

<h1>Liste des services</h1>

<?php if ($message): ?>
    <div class="message"><?= $message ?></div>
<?php endif; ?>

<a href="ajouter_service.php" class="top-link"><i class="fas fa-plus"></i> Ajouter un service</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nom du service</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($services as $s): ?>
        <tr>
            <td><?= $s['id'] ?></td>
            <td><?= htmlspecialchars($s['nom_service']) ?></td>
            <td><?= htmlspecialchars($s['description']) ?></td>
            <td>
                <a href="modifier_service.php?id=<?= $s['id'] ?>" class="btn edit"><i class="fas fa-edit"></i> Modifier</a>
                <a href="services_liste.php?delete=<?= $s['id'] ?>" class="btn delete" onclick="return confirm('Confirmer la suppression ?')"><i class="fas fa-trash"></i> Supprimer</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
