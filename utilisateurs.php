<?php
session_start();
require 'config.php';

// Vérifie que seul le RH (role_id = 5) peut accéder
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
    header("Location: login.php");
    exit;
}

$success = false;
$errors = [];
$edit_user = null;

// Suppression utilisateur
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
    $stmt->execute([$id]);
    $success = "Utilisateur supprimé avec succès.";
}

// Charger utilisateur pour modification
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Création ou modification utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $role_id = (int)$_POST['role_id'];
    $service_id = (int)$_POST['service_id'];

    // Validation rôle
    if (!in_array($role_id, [1,2,3])) {
        $errors[] = "Rôle invalide (seulement Chef, Manager, Directeur Service)";
    }

    if (empty($errors)) {
        // Modification
        if (!empty($_POST['edit_id'])) {
            $edit_id = (int)$_POST['edit_id'];
            $stmt = $pdo->prepare("
                UPDATE utilisateurs
                SET nom = ?, prenom = ?, email = ?, role_id = ?, service_id = ?
                WHERE id = ?
            ");
            $stmt->execute([$nom, $prenom, $email, $role_id, $service_id, $edit_id]);
            $success = "Utilisateur modifié avec succès.";
            $edit_user = null; // Réinitialiser le formulaire
        }
        // Création
        else {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $errors[] = "Cet email est déjà utilisé.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, service_id)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$nom, $prenom, $email, $password, $role_id, $service_id]);
                $success = "Utilisateur créé avec succès.";
            }
        }
    }
}

// Récupérer services et utilisateurs
$services = $pdo->query("SELECT id, nom FROM services ORDER BY nom")->fetchAll();
$utilisateurs = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.email, r.nom AS role, s.nom AS service
    FROM utilisateurs u
    JOIN roles r ON u.role_id = r.id
    LEFT JOIN services s ON u.service_id = s.id
    WHERE u.role_id IN (1,2,3)
    ORDER BY u.nom ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des utilisateurs - RH</title>
<style>
body { font-family: Arial, sans-serif; margin: 2rem; background: #f9f9f9; }
header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; }
header img { height: 50px; }
header a { background: #007a33; color: #fff; padding: 0.5rem 1rem; border-radius: 4px; text-decoration: none; }
h2 { color: #007a33; margin-top: 0; }
form, table { background: #fff; padding: 1.5rem; margin-bottom: 2rem; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
input, select { width: 100%; padding: 0.6rem; margin-bottom: 1rem; border: 1px solid #ccc; border-radius: 4px; }
button { background: #007a33; color: #fff; padding: 0.6rem 1rem; border: none; border-radius: 4px; cursor: pointer; margin-right: 0.5rem; }
button:hover { background: #005b24; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 0.8rem; border-bottom: 1px solid #ccc; text-align: left; }
th { background: #f0f0f0; }
.success { background: #dfd; color: #060; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
.error { background: #fdd; color: #900; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; }
</style>
</head>
<body>

<header>
    <img src="images/logo.jpg" alt="Logo Niger Telecoms">
    <a href="dashboard.php">← Retour</a>
</header>

<h2><?= $edit_user ? "Modifier l'utilisateur" : "Créer un utilisateur" ?></h2>

<?php if ($success): ?>
    <div class="success"><?= $success ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="error"><?php foreach ($errors as $e) echo "<p>$e</p>"; ?></div>
<?php endif; ?>

<form method="post">
    <?php if ($edit_user): ?>
        <input type="hidden" name="edit_id" value="<?= $edit_user['id'] ?>">
    <?php endif; ?>
    <input type="text" name="nom" placeholder="Nom" required value="<?= htmlspecialchars($edit_user['nom'] ?? '') ?>">
    <input type="text" name="prenom" placeholder="Prénom" required value="<?= htmlspecialchars($edit_user['prenom'] ?? '') ?>">
    <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
    <?php if (!$edit_user): ?>
        <input type="password" name="password" placeholder="Mot de passe" required>
    <?php endif; ?>

    <label for="role_id">Rôle :</label>
    <select name="role_id" required>
        <option value="">-- Choisir un rôle --</option>
        <option value="1" <?= isset($edit_user) && $edit_user['role_id']==1 ? 'selected' : '' ?>>Chef de service</option>
        <option value="2" <?= isset($edit_user) && $edit_user['role_id']==2 ? 'selected' : '' ?>>Manager</option>
        <option value="3" <?= isset($edit_user) && $edit_user['role_id']==3 ? 'selected' : '' ?>>Directeur de service</option>
    </select>

    <label for="service_id">Service :</label>
    <select name="service_id" required>
        <option value="">-- Choisir un service --</option>
        <?php foreach ($services as $s): ?>
            <option value="<?= $s['id'] ?>" <?= isset($edit_user) && $edit_user['service_id']==$s['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($s['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit"><?= $edit_user ? "Enregistrer les modifications" : "Créer l'utilisateur" ?></button>
</form>

<h2>Liste des utilisateurs</h2>
<table>
    <thead>
        <tr>
            <th>Nom complet</th>
            <th>Email</th>
            <th>Rôle</th>
            <th>Service</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($utilisateurs as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['nom'] . ' ' . $u['prenom']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['service'] ?? 'N/A') ?></td>
                <td>
                    <a href="?edit=<?= $u['id'] ?>"><button type="button">Modifier</button></a>
                    <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Confirmer la suppression ?')"><button type="button">Supprimer</button></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
