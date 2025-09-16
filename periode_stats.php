<?php
session_start();
require 'config.php';

// Missions par mois
$stmt = $pdo->query("SELECT DATE_FORMAT(date_debut, '%Y-%m') as mois, COUNT(*) as total 
                     FROM missions 
                     GROUP BY mois 
                     ORDER BY mois ASC");
$missionsMois = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<canvas id="missionsMois"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById("missionsMois"), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($missionsMois, 'mois')) ?>,
        datasets: [{
            label: 'Missions par mois',
            data: <?= json_encode(array_column($missionsMois, 'total')) ?>,
            borderColor: '#f7941d',
            fill: false,
            tension: 0.3
        }]
    }
});
</script>
