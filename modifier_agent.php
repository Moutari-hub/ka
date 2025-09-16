<?php
session_start();
require 'config.php';

// Vérifier que l'utilisateur est RH
if (!isset($_SESSION['user_id'], $_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
    header('Location: login.php');
    exit;
}

// --- Gestion du formulaire d'ajout ---
$errors = [];
$success = "";

try {
    $stmtServices = $pdo->query("SELECT id, nom FROM services ORDER BY nom ASC");
    $services = $stmtServices->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des services : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_personnel'])) {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $poste = trim($_POST['poste'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $service_id = intval($_POST['service_id'] ?? 0);

    if ($service_id <= 0) $errors[] = "Veuillez sélectionner un service valide.";
    if ($nom === '') $errors[] = "Le nom est obligatoire.";
    if ($prenom === '') $errors[] = "Le prénom est obligatoire.";
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO personnels (nom, prenom, poste, service_id, email, telephone) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nom, $prenom, $poste, $service_id, $email, $telephone]);
            $success = "Personnel ajouté avec succès.";
            $nom = $prenom = $poste = $email = $telephone = '';
            $service_id = 0;
        } catch (PDOException $e) {
            $errors[] = "Erreur base de données : " . $e->getMessage();
        }
    }
}

// --- Récupérer tous les personnels groupés par service ---
$stmt = $pdo->query("
    SELECT p.id, p.nom, p.prenom, p.poste, s.nom AS service_nom
    FROM personnels p
    LEFT JOIN services s ON s.id = p.service_id
    ORDER BY s.nom, p.nom, p.prenom
");
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
$grouped = [];
foreach ($personnels as $p) {
    $service = $p['service_nom'] ?? 'Non affecté';
    $grouped[$service][] = $p;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Dashboard RH - Gestion du personnel</title>
<style>
body { font-family: Arial, sans-serif; margin:0; background:#f4f6f8; color:#333; }
header { display:flex; align-items:center; background:#006837; color:#fff; padding:15px; }
header img { height:50px; margin-right:15px; }
header h1 { font-size:1.8rem; margin:0; flex-grow:1; }

.container { padding:20px; max-width:1200px; margin:auto; }

.button-add { background:#ff8c00; color:#fff; padding:10px 20px; border:none; border-radius:5px; cursor:pointer; font-weight:bold; margin-bottom:20px; }
.button-add:hover { background:#e67e22; }

.service-card { background:#fff; padding:15px; margin-bottom:30px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); }
.service-header { background:#27ae60; color:#fff; padding:10px 15px; border-radius:5px; font-size:1.3rem; margin-bottom:10px; display:flex; justify-content:space-between; align-items:center; }
.service-header span { font-weight:bold; font-size:1rem; background:#fff; color:#27ae60; padding:3px 8px; border-radius:5px; }

table { width:100%; border-collapse: collapse; margin-top:10px; }
th, td { padding:10px; border-bottom:1px solid #ddd; text-align:left; }
th { background:#006837; color:#fff; }
tr:hover { background:#f0f9f5; }

.action-buttons button { padding:6px 12px; margin:2px; border:none; border-radius:5px; cursor:pointer; font-weight:600; transition:0.2s; }
button.historique { background:#f39c12; color:#fff; }
button.historique:hover { background:#e67e22; }
button.modifier { background:#2980b9; color:#fff; }
button.modifier:hover { background:#1f5c8b; }

form label { display:block; margin-top:10px; font-weight:bold; }
form input, form select { width:100%; padding:8px; margin-top:5px; border-radius:4px; border:1px solid #ccc; box-sizing:border-box; }
form button { margin-top:10px; padding:10px 15px; background:#006837; color:#fff; border:none; border-radius:5px; cursor:pointer; font-weight:bold; }
form button:hover { background:#00502a; }

.success { color: green; margin-bottom:15px; }
.error { color: red; margin-bottom:15px; }

</style>
<script>
function toggleForm() {
    var f = document.getElementById('formAdd');
    f.style.display = (f.style.display === 'none') ? 'block' : 'none';
}
</script>
</head>
<body>

<header>
    <img src="images/logo.jpg" alt="Niger Telecoms">
    <h1>Dashboard RH - Gestion du personnel</h1>
</header>

<div class="container">

<button class="button-add" onclick="toggleForm()">+ Ajouter un personnel</button>

<div id="formAdd" style="display:none;">
<?php if (!empty($errors)): ?>
    <div class="error"><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>
<?php if ($success !== ""): ?>
    <div class="success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<form method="post" action="">
<input type="hidden" name="add_personnel" value="1">
<label for="service_id">Service *</label>
<select id="service_id" name="service_id" required>
    <option value="">-- Sélectionnez un service --</option>
    <?php foreach ($services as $service): ?>
        <option value="<?= $service['id'] ?>" <?= (isset($service_id) && $service_id == $service['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($service['nom']) ?>
        </option>
    <?php endforeach; ?>
</select>

<label for="nom">Nom *</label>
<input type="text" id="nom" name="nom" required value="<?= htmlspecialchars($nom ?? '') ?>">

<label for="prenom">Prénom *</label>
<input type="text" id="prenom" name="prenom" required value="<?= htmlspecialchars($prenom ?? '') ?>">

<label for="poste">Poste</label>
<input type="text" id="poste" name="poste" value="<?= htmlspecialchars($poste ?? '') ?>">

<label for="email">Email</label>
<input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>">

<label for="telephone">Téléphone</label>
<input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($telephone ?? '') ?>">

<button type="submit">Ajouter</button>
</form>
</div>

<?php foreach($grouped as $service => $personnels_service): ?>
<div class="service-card">
    <div class="service-header">
        <?= htmlspecialchars($service) ?>
        <span><?= count($personnels_service) ?> personnels</span>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Poste</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($personnels_service as $index => $p): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><?= htmlspecialchars($p['nom']) ?></td>
                <td><?= htmlspecialchars($p['prenom']) ?></td>
                <td><?= htmlspecialchars($p['poste']) ?></td>
                <td class="action-buttons">
                    <form action="historique_personnel.php" method="GET" style="display:inline;">
                        <input type="hidden" name="personnel_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="historique">Historique</button>
                    </form>
                    <form action="modifier_personnel.php" method="GET" style="display:inline;">
                        <input type="hidden" name="personnel_id" value="<?= $p['id'] ?>">
                        <button type="submit" class="modifier">Modifier</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

</div>
</body>
</html>
