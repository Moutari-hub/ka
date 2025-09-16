<?php
session_start();
require 'config.php'; // contient la connexion $pdo

// Vérification session
if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Récupération des infos de l'utilisateur
$stmt = $pdo->prepare("SELECT u.nom, u.prenom, u.email, r.nom AS role_nom, s.nom AS service_nom
                       FROM utilisateurs u
                       LEFT JOIN roles r ON u.role_id = r.id
                       LEFT JOIN services s ON u.service_id = s.id
                       WHERE u.id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "Utilisateur introuvable.";
    exit;
}

// Traitement du formulaire de modification
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $password = trim($_POST['password']);
    $confirm = trim($_POST['confirm']);

    if (empty($nom) || empty($prenom)) {
        $message = "Le nom et le prénom ne peuvent pas être vides.";
    } elseif (!empty($password) && $password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    } else {
        if (!empty($password)) {
            // Modifier nom, prénom et mot de passe
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = :nom, prenom = :prenom, password = :password WHERE id = :id");
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'password' => $hashedPassword,
                'id' => $user_id
            ]);
        } else {
            // Modifier seulement nom et prénom
            $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = :nom, prenom = :prenom WHERE id = :id");
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'id' => $user_id
            ]);
        }
        $message = "Profil mis à jour avec succès.";
        $user['nom'] = $nom;
        $user['prenom'] = $prenom;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Profil - Niger Telecoms</title>
<style>
body { font-family: Arial, sans-serif; margin: 2rem; background: #f9f9f9; color: #333; }
h1 { color: #007a33; }
.profil-container { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1); max-width: 500px; }
.profil-row { margin-bottom: 1rem; }
.profil-label { font-weight: bold; display: inline-block; width: 150px; }
input[type=text], input[type=password] { width: 100%; padding: 8px; margin-top: 4px; border-radius: 4px; border: 1px solid #ccc; }
input[type=submit] { margin-top: 1rem; padding: 8px 16px; background: #f7941d; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
input[type=submit]:hover { background: #e07b14; }
.message { margin-top: 1rem; color: green; }
.error { color: red; }
a { display: inline-block; margin-bottom: 1rem; text-decoration: none; color: #007a33; }
</style>
</head>
<body>

<a href="dashboard.php">← Retour au tableau de bord</a>

<h1>Mon profil</h1>

<div class="profil-container">
    <div class="profil-row"><span class="profil-label">Nom :</span> <?= htmlspecialchars($user['nom']) ?></div>
    <div class="profil-row"><span class="profil-label">Prénom :</span> <?= htmlspecialchars($user['prenom']) ?></div>
    <div class="profil-row"><span class="profil-label">Email :</span> <?= htmlspecialchars($user['email']) ?></div>
    <div class="profil-row"><span class="profil-label">Rôle :</span> <?= htmlspecialchars($user['role_nom']) ?></div>
    <div class="profil-row"><span class="profil-label">Service :</span> <?= htmlspecialchars($user['service_nom'] ?? 'N/A') ?></div>

    <hr style="margin: 1.5rem 0;">

    <h2>Modifier mon profil</h2>

    <?php if ($message): ?>
        <div class="<?= strpos($message, 'succès') !== false ? 'message' : 'error' ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="post">
        <label for="nom">Nom :</label>
        <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>

        <label for="prenom">Prénom :</label>
        <input type="text" name="prenom" id="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required>

        <label for="password">Nouveau mot de passe (laisser vide si non changé) :</label>
        <input type="password" name="password" id="password">

        <label for="confirm">Confirmer le mot de passe :</label>
        <input type="password" name="confirm" id="confirm">

        <input type="submit" value="Mettre à jour">
    </form>
</div>

</body>
</html>
