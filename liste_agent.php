<?php
session_start();
require 'config.php';

// Vérification connexion
if (!isset($_SESSION['user_id'])) exit('Accès interdit');

// Récupération personnel
$stmt = $pdo->query("
    SELECT p.id, p.nom, p.prenom, p.poste, s.nom AS service
    FROM personnels p
    LEFT JOIN services s ON p.service_id = s.id
    ORDER BY p.nom
");
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Liste du personnel</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Poste</th>
            <th>Service</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($personnels as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p['nom']) ?></td>
            <td><?= htmlspecialchars($p['prenom']) ?></td>
            <td><?= htmlspecialchars($p['poste']) ?></td>
            <td><?= htmlspecialchars($p['service']) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
