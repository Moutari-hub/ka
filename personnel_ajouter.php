<?php
session_start();
require 'config.php';
if (!isset($_SESSION['user_id'], $_SESSION['role_id']) || $_SESSION['role_id'] != 5) {
    header('Location: login.php'); exit;
}

// Pagination
$limit = 10;
$page = intval($_GET['page'] ?? 1);
$offset = ($page-1)*$limit;

// Ajouter / Modifier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['personnel_id'] ?? 0);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $poste = trim($_POST['poste']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $service_id = intval($_POST['service_id']);
    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE personnels SET nom=?, prenom=?, poste=?, service_id=?, email=?, telephone=? WHERE id=?");
        $stmt->execute([$nom,$prenom,$poste,$service_id,$email,$telephone,$id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO personnels (nom, prenom, poste, service_id, email, telephone) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$nom,$prenom,$poste,$service_id,$email,$telephone]);
    }
}

// Supprimer
if (isset($_GET['delete_id'])) {
    $id = intval($_GET['delete_id']);
    $pdo->prepare("DELETE FROM personnels WHERE id=?")->execute([$id]);
    header('Location: personnels.php'); exit;
}

// Récupérer services
$services = $pdo->query("SELECT id, nom FROM services ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer personnels avec pagination
$total = $pdo->query("SELECT COUNT(*) FROM personnels")->fetchColumn();
$personnels = $pdo->query("SELECT p.*, s.nom AS service_nom FROM personnels p LEFT JOIN services s ON s.id = p.service_id ORDER BY s.nom, p.nom LIMIT $limit OFFSET $offset")->fetchAll(PDO::FETCH_ASSOC);

// Récupérer personnel pour édition (AJAX)
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM personnels WHERE id=?");
    $stmt->execute([$id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Gestion des personnels - RH</title>
<style>
body{font-family:Arial;background:#f4f6f8;margin:0;padding:20px;color:#333;}
header{display:flex;align-items:center;background:#006837;color:#fff;padding:15px;}
header img{height:50px;margin-right:15px;}
header h1{font-size:1.8rem;margin:0;flex-grow:1;}
.container{max-width:1200px;margin:auto;}
button{cursor:pointer;font-weight:bold;border:none;border-radius:5px;padding:6px 12px;margin:2px;}
button:hover{opacity:0.85;}
#search{padding:8px;width:250px;margin-bottom:15px;border-radius:4px;border:1px solid #ccc;}
table{width:100%;border-collapse:collapse;background:#fff;margin-top:10px;box-shadow:0 4px 12px rgba(0,0,0,0.05);}
th,td{padding:12px;border-bottom:1px solid #ddd;text-align:left;}
th{background:#006837;color:#fff;}
tr:hover{background:#f0f9f5;}
.modal{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);justify-content:center;align-items:center;}
.modal-content{background:#fff;padding:20px;border-radius:8px;width:500px;position:relative;}
.close{position:absolute;top:10px;right:10px;cursor:pointer;color:red;font-weight:bold;}
label{display:block;margin-top:10px;}
input,select{width:100%;padding:8px;margin-top:5px;border-radius:4px;border:1px solid #ccc;box-sizing:border-box;}
button.save{background:#006837;color:#fff;}
button.delete{background:#c0392b;color:#fff;}
.pagination{margin-top:15px;}
.pagination button{background:#eee;color:#333;}
.pagination button.active{background:#006837;color:#fff;}
</style>
</head>
<body>
<header>
<img src="images/logo.jpg" alt="Niger Telecoms">
<h1>Gestion des personnels</h1>
</header>
<div class="container">
<input type="text" id="search" placeholder="Recherche...">
<button onclick="openModal()">+ Ajouter</button>
<a href="dashboard.php"><button>Retour Dashboard</button></a>
<table id="personnelTable">
<tr><th>#</th><th>Nom</th><th>Prénom</th><th>Poste</th><th>Service</th><th>Actions</th></tr>
<?php foreach($personnels as $index => $p): ?>
<tr>
<td><?= ($offset+$index+1) ?></td>
<td><?= htmlspecialchars($p['nom']) ?></td>
<td><?= htmlspecialchars($p['prenom']) ?></td>
<td><?= htmlspecialchars($p['poste']) ?></td>
<td><?= htmlspecialchars($p['service_nom']) ?></td>
<td>
<button class="save" onclick="edit(<?= $p['id'] ?>)">Modifier</button>
<a href="?delete_id=<?= $p['id'] ?>" onclick="return confirm('Supprimer ?')"><button class="delete">Supprimer</button></a>
</td>
</tr>
<?php endforeach; ?>
</table>
<div class="pagination">
<?php 
$totalPages = ceil($total/$limit);
for($i=1;$i<=$totalPages;$i++): ?>
<a href="?page=<?= $i ?>"><button class="<?= $i==$page?'active':'' ?>"><?= $i ?></button></a>
<?php endfor; ?>
</div>
</div>

<div class="modal" id="modal">
<div class="modal-content">
<span class="close" onclick="closeModal()">X</span>
<h2 id="title">Ajouter</h2>
<form method="post" id="form">
<input type="hidden" name="personnel_id" id="personnel_id">
<label>Nom</label><input type="text" name="nom" id="nom" required>
<label>Prénom</label><input type="text" name="prenom" id="prenom" required>
<label>Poste</label><input type="text" name="poste" id="poste">
<label>Email</label><input type="email" name="email" id="email">
<label>Telephone</label><input type="text" name="telephone" id="telephone">
<label>Service</label>
<select name="service_id" id="service_id">
<?php foreach($services as $s): ?>
<option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
<?php endforeach; ?>
</select>
<button type="submit">Enregistrer</button>
</form>
</div>
</div>

<script>
const table = document.getElementById('personnelTable');
const searchInput = document.getElementById('search');
searchInput.addEventListener('keyup', function(){
let filter = this.value.toLowerCase();
for(let i=1;i<table.rows.length;i++){
let row = table.rows[i];
row.style.display = row.cells[1].textContent.toLowerCase().includes(filter) ||
                 row.cells[2].textContent.toLowerCase().includes(filter) ||
                 row.cells[3].textContent.toLowerCase().includes(filter) ||
                 row.cells[4].textContent.toLowerCase().includes(filter) ? '' : 'none';
}
});
function openModal(){
document.getElementById('modal').style.display='flex';
document.getElementById('title').textContent='Ajouter';
document.getElementById('form').reset();
document.getElementById('personnel_id').value='';
}
function closeModal(){document.getElementById('modal').style.display='none';}
function edit(id){
fetch('?action=get&id='+id).then(r=>r.json()).then(d=>{
openModal();
document.getElementById('title').textContent='Modifier';
document.getElementById('personnel_id').value=d.id;
document.getElementById('nom').value=d.nom;
document.getElementById('prenom').value=d.prenom;
document.getElementById('poste').value=d.poste;
document.getElementById('email').value=d.email;
document.getElementById('telephone').value=d.telephone;
document.getElementById('service_id').value=d.service_id;
});
}
</script>
</body>
</html>
