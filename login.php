<?php
session_start();
require 'config.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['user_nom'] = $user['nom'] . ' ' . $user['prenom'];
        $_SESSION['service_id'] = $user['service_id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $errors[] = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <style>
        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 400px;
            margin: 5rem auto;
            background: #fff;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 { text-align: center; margin-bottom: 1rem; }
        input, button {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        button {
            background-color: #007a33;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        .errors {
            background: #fdd;
            color: #900;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
        }
        .link {
            text-align: center;
        }
        a {
            color: #007a33;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Connexion</h2>

        <?php if (!empty($errors)): ?>
        <div class="errors">
            <?php foreach ($errors as $e): echo "<p>$e</p>"; endforeach; ?>
        </div>
        <?php endif; ?>

        <form method="post">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>

        <div class="link">
            <p>Pas encore de compte ? <a href="register.php">Créer un compte DG </a></p>
        </div>
    </div>
</body>
</html>
