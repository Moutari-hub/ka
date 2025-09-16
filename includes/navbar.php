<?php
if (session_status() == PHP_SESSION_NONE) session_start();

require_once 'config.php';

$userId = $_SESSION['user_id'] ?? 0;
$userRole = $_SESSION['role_id'] ?? 0;

// Récupérer les missions à traiter pour la notif
try {
    $stmt = $pdo->prepare("
        SELECT id, titre, type_mission, zone_mission 
        FROM missions
        WHERE 
            CASE
                WHEN :role = 2 THEN (manager_validation IS NULL OR manager_validation = 0)
                WHEN :role = 3 THEN (manager_validation = 1 AND (dir_service_validation IS NULL OR dir_service_validation = 0))
                WHEN :role = 4 THEN (manager_validation = 1 AND dir_service_validation = 1 AND (dg_validation IS NULL OR dg_validation = 0))
                WHEN :role = 5 THEN (dg_validation = 1 AND lancement = 1 AND (rh_preparer IS NULL OR rh_preparer = 0))
                WHEN :role = 6 THEN (dg_validation = 1 AND lancement = 1 AND rh_preparer = 1 AND (df_valide IS NULL OR df_valide = 0))
                ELSE 0
            END
        ORDER BY date_debut DESC
    ");
    $stmt->execute(['role' => $userRole]);
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $nbNotif = count($missions);
} catch (Exception $e) {
    $missions = [];
    $nbNotif = 0;
}
?>

<header class="header" role="banner">
    <h1>Tableau de bord</h1>

    <div class="notif-icon" onclick="toggleNotif()" title="Afficher les missions à traiter" role="button" aria-haspopup="true" aria-expanded="false" tabindex="0">
        <span>🔔</span>
        <?php if ($nbNotif > 0): ?>
            <span class="badge" aria-label="<?= $nbNotif ?> notifications non lues"><?= $nbNotif ?></span>
        <?php endif; ?>

        <div class="dropdown-menu" id="notifDropdown" aria-live="polite" aria-atomic="true" aria-label="Liste des missions à traiter">
            <div class="dropdown-header">
                Missions à traiter (<?= $nbNotif ?>)
            </div>
            <?php if ($nbNotif > 0): ?>
                <?php foreach ($missions as $mission): ?>
                    <?php
                    if ($userRole == 5) {
                        $link = "rh_preparer.php?id=" . $mission['id'];
                    } elseif ($userRole == 6) {
                        $link = "df_fonds.php?id=" . $mission['id'];
                    } else {
                        $link = "valider_mission.php?id=" . $mission['id'];
                    }
                    ?>
                    <a href="<?= $link ?>" class="dropdown-item" tabindex="0" aria-label="Mission <?= htmlspecialchars($mission['titre']) ?>">
                        <?= htmlspecialchars($mission['titre']) ?><br />
                        <small style="color:#6c757d;">
                            <?= htmlspecialchars($mission['type_mission']) ?> - <?= htmlspecialchars($mission['zone_mission']) ?>
                        </small>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="dropdown-item">Aucune mission à traiter</div>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
function toggleNotif() {
    const dropdown = document.getElementById('notifDropdown');
    dropdown.classList.toggle('show');
    const notifIcon = document.querySelector('.notif-icon');
    const expanded = notifIcon.getAttribute('aria-expanded') === 'true';
    notifIcon.setAttribute('aria-expanded', (!expanded).toString());
}
window.onclick = function(event) {
    const notifIcon = document.querySelector('.notif-icon');
    const dropdown = document.getElementById('notifDropdown');
    if (!notifIcon.contains(event.target)) {
        dropdown.classList.remove('show');
        notifIcon.setAttribute('aria-expanded', 'false');
    }
}
document.querySelector('.notif-icon').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleNotif();
    }
});
</script>
