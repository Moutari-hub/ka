<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'], $_SESSION['role_id']) || $_SESSION['role_id'] != 4) {
    header('Location: login.php');
    exit;
}

$message = '';
$alertType = 'info';

// --- Ajouter ou modifier utilisateur ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);

    if ($action === 'ajouter' && $nom && $prenom && $email && $password && $role_id) {
        // Vérifier si email existe déjà
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email=?");
        $stmtCheck->execute([$email]);
        if($stmtCheck->fetchColumn() > 0){
            $message = "Erreur : cet email existe déjà.";
            $alertType = 'danger';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$nom, $prenom, $email, $hash, $role_id])) {
                $message = "Utilisateur ajouté avec succès !";
                $alertType = 'success';
            } else {
                $message = "Erreur lors de l'ajout.";
                $alertType = 'danger';
            }
        }
    }

    if ($action === 'modifier' && isset($_POST['id_utilisateur'])) {
        $id = (int)$_POST['id_utilisateur'];

        // Vérifier si email est utilisé par un autre utilisateur
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email=? AND id<>?");
        $stmtCheck->execute([$email, $id]);
        if($stmtCheck->fetchColumn() > 0){
            $message = "Erreur : cet email est déjà utilisé par un autre utilisateur.";
            $alertType = 'danger';
        } else {
            $sql = "UPDATE utilisateurs SET nom=?, prenom=?, email=?, role_id=?";
            $params = [$nom, $prenom, $email, $role_id];

            if ($password) {
                $sql .= ", mot_de_passe=?";
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id=?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $message = "Utilisateur modifié avec succès !";
                $alertType = 'success';
            } else {
                $message = "Erreur lors de la modification.";
                $alertType = 'danger';
            }
        }
    }

    if ($action === 'supprimer' && isset($_POST['id_utilisateur'])) {
        $id = (int)$_POST['id_utilisateur'];
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM missions_personnels WHERE personnel_id = ?");
        $stmtCheck->execute([$id]);
        $nbMissions = (int)$stmtCheck->fetchColumn();

        if ($nbMissions > 0) {
            $message = "Impossible de supprimer cet utilisateur : il a des missions assignées.";
            $alertType = 'warning';
        } else {
            $stmt = $pdo->prepare("DELETE FROM utilisateurs WHERE id=? AND role_id IN (5,6)");
            if ($stmt->execute([$id])) {
                $message = "Utilisateur supprimé avec succès !";
                $alertType = 'success';
            } else {
                $message = "Erreur lors de la suppression.";
                $alertType = 'danger';
            }
        }
    }
}

// --- Récupérer les utilisateurs RH et DF ---
$stmt = $pdo->query("SELECT * FROM utilisateurs WHERE role_id IN (5,6) ORDER BY date_creation DESC");
$utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des utilisateurs</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background:#f4f6f8; }
.container { max-width:1000px; margin-top:30px; }
.card { margin-bottom:20px; box-shadow:0 0 8px rgba(0,0,0,0.1);}
.logo { width:120px; display:block; margin:0 auto 15px; }
.btn-niger { background:#2d8f2d; color:#fff; border:none; }
.btn-niger:hover { background:#276d24; color:#fff; }
.btn-orange { background:#f7941d; color:#fff; border:none; }
.btn-orange:hover { background:#e07a00; color:#fff; }
.table thead { background:#2d8f2d; color:#fff; }
</style>
</head>
<body>
<div class="container">
    <img src="images/logo.jpg" class="logo" alt="Logo">
    <h2 class="text-center mb-4">Gestion des Utilisateurs (RH / DF)</h2>

    <?php if($message): ?>
        <div class="alert alert-<?= $alertType ?>"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="card p-3 mb-4">
        <h4>Ajouter un utilisateur</h4>
        <form method="POST" class="row g-3">
            <input type="hidden" name="action" value="ajouter">
            <div class="col-md-6"><input type="text" name="nom" class="form-control" placeholder="Nom" required></div>
            <div class="col-md-6"><input type="text" name="prenom" class="form-control" placeholder="Prénom" required></div>
            <div class="col-md-6"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
            <div class="col-md-6"><input type="password" name="password" class="form-control" placeholder="Mot de passe" required></div>
            <div class="col-md-6">
                <select name="role_id" class="form-select" required>
                    <option value="">Sélectionner le rôle</option>
                    <option value="5">Ressources Humaines</option>
                    <option value="6">Directeur Financier</option>
                </select>
            </div>
            <div class="col-md-6"><button type="submit" class="btn btn-niger w-100">Ajouter</button></div>
        </form>
    </div>

    <h4>Liste des utilisateurs RH / DF</h4>
    <table class="table table-bordered bg-white table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($utilisateurs as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['nom']) ?></td>
                <td><?= htmlspecialchars($u['prenom']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= $u['role_id']==5 ? 'RH' : 'DF' ?></td>
                <td>
                    <button class="btn btn-orange btn-sm" data-bs-toggle="modal" data-bs-target="#modalModif<?= $u['id'] ?>">Modifier</button>
                    <form method="POST" style="display:inline-block;" onsubmit="return confirm('Confirmer la suppression ?');">
                        <input type="hidden" name="action" value="supprimer">
                        <input type="hidden" name="id_utilisateur" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Supprimer</button>
                    </form>
                </td>
            </tr>

            <!-- Modal Modifier -->
            <div class="modal fade" id="modalModif<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Modifier <?= htmlspecialchars($u['nom'].' '.$u['prenom']) ?></h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="modifier">
                                <input type="hidden" name="id_utilisateur" value="<?= $u['id'] ?>">
                                <div class="mb-2"><input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($u['nom']) ?>" required></div>
                                <div class="mb-2"><input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($u['prenom']) ?>" required></div>
                                <div class="mb-2"><input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required></div>
                                <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="Nouveau mot de passe (laisser vide pour conserver)"></div>
                                <div class="mb-2">
                                    <select name="role_id" class="form-select" required>
                                        <option value="5" <?= $u['role_id']==5?'selected':'' ?>>RH</option>
                                        <option value="6" <?= $u['role_id']==6?'selected':'' ?>>DF</option>
                                    </select>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-niger">Enregistrer</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endforeach; ?>
        </tbody>
    </table>

    <a href="dashboard.php" class="btn btn-secondary mt-3">← Retour au Dashboard</a>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
