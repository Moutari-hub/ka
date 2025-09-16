<?php
// Assure que la session est démarrée
if (session_status() == PHP_SESSION_NONE) session_start();

$userRole = $_SESSION['role_id'] ?? 0;
$userName = htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur');
?>
<aside class="sidebar" role="navigation" aria-label="Menu principal">
    <div class="logo">
        <img src="images/logo.png" alt="Niger Telecoms Logo" />
    </div>

    <div class="user-info" aria-label="Informations utilisateur">
        <h3><?= $userName ?></h3>
    </div>

    <nav class="nav-menu" role="menu">
        <a href="dashboard.php" class="nav-link" role="menuitem" tabindex="0">
            <span>🏠</span> Tableau de bord
        </a>
        <a href="historique.php" class="nav-link" role="menuitem" tabindex="0">
            <span>📋</span> Historique
        </a>
        <?php if ($userRole == 1): // Collaborateur ?>
            <a href="proposer_mission.php" class="nav-link" role="menuitem" tabindex="0">
                <span>➕</span> Proposer mission
            </a>
        <?php endif; ?>
        <?php if ($userRole == 5): // RH ?>
            <a href="ordre_mission.php" class="nav-link" role="menuitem" tabindex="0">
                <span>📄</span> Imprimer Ordre de Mission
            </a>
        <?php endif; ?>
        <a href="logout.php" class="nav-link" role="menuitem" tabindex="0">
            <span>🚪</span> Déconnexion
        </a>
    </nav>
</aside>
