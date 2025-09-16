<?php
session_start();
require 'config.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $matricule = trim($_POST['matricule']); // nouveau champ matricule

    // Vérification mot de passe
    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérifier l'email unique
    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Cet email est déjà utilisé.";
    }

    // Vérifier le matricule pour DG
    $dg_matricule_attendu = 'DG001'; // matricule prévu pour le DG
    if ($matricule !== $dg_matricule_attendu) {
        $errors[] = "Matricule incorrect. Seul le DG peut être créé avec le bon matricule.";
    }

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role_id = 4; // rôle DG
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id, matricule) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nom, $prenom, $email, $hash, $role_id, $matricule]);
        $success = true;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Créer un compte DG</title>
<style>
/* styles simples comme avant */
body { font-family: Arial; background:#f4f4f4; }
.container { max-width: 450px; margin: 3rem auto; background: #fff; padding:2rem; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
input, button { width:100%; padding:0.8rem; margin-bottom:1rem; border-radius:6px; border:1px solid #ccc; }
button { background:#007a33; color:#fff; font-weight:bold; cursor:pointer; }
.errors { background:#fdd; color:#900; padding:1rem; margin-bottom:1rem; border-radius:6px; }
.success { background:#dfd; color:#060; padding:1rem; margin-bottom:1rem; border-radius:6px; }
</style>
</head>
<body>
<div class="container">
<h2>Créer un compte DG</h2>

<?php if ($success): ?>
    <div class="success">Compte DG créé avec succès. <a href="login.php">Se connecter</a></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $e) echo "<p>$e</p>"; ?>
    </div>
<?php endif; ?>

<form method="post">
    <input type="text" name="nom" placeholder="Nom" required>
    <input type="text" name="prenom" placeholder="Prénom" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="text" name="matricule" placeholder="Matricule DG" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <input type="password" name="confirm" placeholder="Confirmer le mot de passe" required>
    <button type="submit">Créer le compte DG</button>
</form>
</div>
</body>
</html>
