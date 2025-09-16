<?php
session_start();
require 'config.php';

// Missions par service
$stmt = $pdo->query("SELECT s.nom as service, COUNT(m.id) as total 
                     FROM missions m 
                     LEFT JOIN services s ON m.service_id = s.id 
                     GROUP BY s.nom");
$missionsService = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<canvas id="missionsService"></canvas>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById("missionsService"), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($missionsService, 'service')) ?>,
        datasets: [{
            label: 'Missions par service',
            data: <?= json_encode(array_column($missionsService, 'total')) ?>,
            backgroundColor: '#007a33'
        }]
    },
    options: { indexAxis: 'y' }
});
</script>
