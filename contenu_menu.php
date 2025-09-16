<?php
session_start();
require 'config.php';

$role = $_SESSION['role_id'] ?? 0;
$type = $_GET['type'] ?? '';

// Fonction sécurisée d’échappement HTML
function h($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

if ($role == 5) { // RH
    if ($type == 'rh_statuts') {
        // Stats des missions par statut (tous services)
        $sql = "SELECT statut, COUNT(*) AS nb FROM missions GROUP BY statut";
        $stmt = $pdo->query($sql);
        $stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

        echo "<h3>Statuts des missions (tous services)</h3>";
        echo "<table><thead><tr><th>Statut</th><th>Nombre</th></tr></thead><tbody>";
        $allStatus = ['en_attente'=>'En attente', 'validee'=>'Validées', 'lancee'=>'Lancées', 'en_cours'=>'En cours', 'rejete'=>'Rejetées'];
        foreach ($allStatus as $code => $label) {
            $nb = $stats[$code] ?? 0;
            echo "<tr><td>" . h($label) . "</td><td>" . h($nb) . "</td></tr>";
        }
        echo "</tbody></table>";

    } elseif ($type == 'rh_services') {
        // Liste services + missions (nombre par statut)
        $sql = "
            SELECT s.nom AS service_nom, m.statut, COUNT(*) AS nb
            FROM services s
            LEFT JOIN missions m ON m.service_id = s.id
            GROUP BY s.id, m.statut
            ORDER BY s.nom, m.statut
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Organiser les données
        $stats = [];
        foreach ($rows as $r) {
            $srv = $r['service_nom'];
            $statut = $r['statut'] ?? 'aucun';
            $nb = $r['nb'];
            if (!isset($stats[$srv])) $stats[$srv] = [];
            $stats[$srv][$statut] = $nb;
        }

        echo "<h3>Services et missions</h3>";
        echo "<table><thead><tr><th>Service</th><th>En attente</th><th>Validées</th><th>Lancées</th><th>En cours</th><th>Rejetées</th></tr></thead><tbody>";
        foreach ($stats as $srv => $sdata) {
            echo "<tr>";
            echo "<td>" . h($srv) . "</td>";
            echo "<td>" . h($sdata['en_attente'] ?? 0) . "</td>";
            echo "<td>" . h($sdata['validee'] ?? 0) . "</td>";
            echo "<td>" . h($sdata['lancee'] ?? 0) . "</td>";
            echo "<td>" . h($sdata['en_cours'] ?? 0) . "</td>";
            echo "<td>" . h($sdata['rejete'] ?? 0) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";

    } else {
        echo "<p>Option invalide.</p>";
    }
} elseif ($role == 4) { // DG
    if ($type == 'dg_services') {
        // Services + nombre total de missions
        $sql = "
            SELECT s.nom AS service_nom, COUNT(m.id) AS nb_missions
            FROM services s
            LEFT JOIN missions m ON m.service_id = s.id
            GROUP BY s.id
            ORDER BY s.nom
        ";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Services et nombre total de missions</h3>";
        echo "<table><thead><tr><th>Service</th><th>Nombre de missions</th></tr></thead><tbody>";
        foreach ($rows as $r) {
            echo "<tr><td>" . h($r['service_nom']) . "</td><td>" . h($r['nb_missions']) . "</td></tr>";
        }
        echo "</tbody></table>";

    } else {
        echo "<p>Option invalide.</p>";
    }
} elseif ($role == 6) { // DF
    if ($type == 'df_missions') {
        // Missions validées par ce DF (supposons qu'on ait un champ df_id dans missions)
        $df_id = $_SESSION['user_id'];
        $sql = "
            SELECT id, titre, statut, date_creation
            FROM missions
            WHERE df_validation = 1 AND df_id = :df_id
            ORDER BY date_creation DESC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['df_id' => $df_id]);
        $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<h3>Missions validées par vous</h3>";
        if (count($missions) === 0) {
            echo "<p>Aucune mission validée par vous.</p>";
        } else {
            echo "<table><thead><tr><th>ID</th><th>Titre</th><th>Statut</th><th>Date création</th></tr></thead><tbody>";
            foreach ($missions as $m) {
                echo "<tr>";
                echo "<td>" . h($m['id']) . "</td>";
                echo "<td>" . h($m['titre']) . "</td>";
                echo "<td>" . h($m['statut']) . "</td>";
                echo "<td>" . h($m['date_creation']) . "</td>";
                echo "</tr>";
            }
            echo "</tbody></table>";
        }
    } else {
        echo "<p>Option invalide.</p>";
    }
} else {
    echo "<p>Accès refusé ou rôle non géré.</p>";
}
