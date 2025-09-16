<?php
require 'config.php';
$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role_id = intval($_POST['role_id']);

    // Vérification si email déjà utilisé
    $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        $error = "Cet email est déjà utilisé.";
    } else {
        // Chiffrement du mot de passe
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        // Insertion sans service_id
        $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, email, password, role_id) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nom, $email, $hashed, $role_id])) {
            header("Location: login.php?register=1");
            exit;
        } else {
            $error = "Erreur lors de l'enregistrement.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - DG / RH / DF</title>
    <style>
        body {
            background: #f3f3f3;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .register-container {
            width: 100%;
            max-width: 400px;
            margin: 5% auto;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #007a33;
        }
        .form-group {
            margin-bottom: 1.2rem;
        }
        label {
            display: block;
            margin-bottom: .5rem;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: .6rem;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .btn {
            width: 100%;
            padding: .7rem;
            background: #007a33;
            border: none;
            color: white;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
        }
        .btn:hover {
            background: #005f27;
        }
        .error {
            background: #fdd;
            color: #900;
            padding: .8rem;
            margin-bottom: 1rem;
            border-radius: 5px;
            border: 1px solid #d99;
        }
    </style>
</head>
<body>
<div class="register-container">
    <h2>Créer un compte</h2>
    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label for="nom">Nom complet</label>
            <input type="text" name="nom" id="nom" required>
        </div>
        <div class="form-group">
            <label for="email">Adresse e-mail</label>
            <input type="email" name="email" id="email" required>
        </div>
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" name="password" id="password" required minlength="6">
        </div>
        <div class="form-group">
            <label for="role_id">Rôle</label>
            <select name="role_id" id="role_id" required>
                <option value="">-- Choisir un rôle --</option>
                <option value="4">Directeur Général</option>
                <option value="5">Ressources Humaines</option>
                <option value="6">Directeur Financier</option>
            </select>
        </div>
        <button type="submit" class="btn">S'inscrire</button>
    </form>
</div>
</body>
</html>
