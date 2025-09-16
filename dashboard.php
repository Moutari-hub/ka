<?php
/* ------------------ BOOTSTRAP ------------------ */
session_start();
require 'config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'], $_SESSION['role_id'], $_SESSION['user_nom'])) {
    header('Location: login.php'); exit;
}
$services = $pdo->query("SELECT id, nom FROM services ORDER BY nom")->fetchAll(PDO::FETCH_KEY_PAIR);
// Récupération des utilisateurs connectés avec leur service
$users = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.role_id, u.service_id
    FROM utilisateurs u
    ORDER BY u.nom, u.prenom
")->fetchAll(PDO::FETCH_ASSOC);

$user = [
    'id'         => (int)$_SESSION['user_id'],
    'role'       => (int)$_SESSION['role_id'],
    'nom'        => htmlspecialchars($_SESSION['user_nom']),
    'service_id' => isset($_SESSION['service_id']) ? (int)$_SESSION['service_id'] : null
];


/* ------------------ Fonction message workflow ------------------ */

/* ------------------ Fonctions workflow ------------------ */



/* ------------------ Fonctions workflow ------------------ */

/* ------------------ FONCTIONS ------------------ */

/**
 * Retourne le message de workflow selon l'état de la mission
 */
function getWorkflowMessage(array $m): string {
    if (empty($m['manager_validation']))        return "En attente validation Manager";
    if (empty($m['dir_service_validation']))    return "Validée Manager → attente Directeur de Service";
    if (empty($m['lancement']))                 return "Validée Dir. Service → attente DG (lancement)";
    if (empty($m['rh_preparer']))               return "Lancée par DG → attente RH (préparation)";
    if (empty($m['df_valide']))                 return "Préparée RH → attente DF (validation financière)";
    if (empty($m['dg_valide_final']))           return "Validée DF → attente DG (validation finale)";
    return "En cours / Complète";
}

/**
 * Vérifie si l'utilisateur peut agir sur cette mission
 */
function canUserAct(array $m, array $user): bool {
    $manager      = isset($m['manager_validation']) ? (int)$m['manager_validation'] : 0;
    $dirService   = isset($m['dir_service_validation']) ? (int)$m['dir_service_validation'] : 0;
    $lancement    = isset($m['lancement']) ? (int)$m['lancement'] : 0;
    $rhPreparer   = isset($m['rh_preparer']) ? (int)$m['rh_preparer'] : 0;
    $dfValide     = isset($m['df_valide']) ? (int)$m['df_valide'] : 0;
    $dgValFinal   = isset($m['dg_valide_final']) ? (int)$m['dg_valide_final'] : 0;

    switch ($user['role']) {
        case 2: // Manager
            return ($m['service_id'] == $user['service_id']) && ($manager === 0);
        case 3: // Directeur de Service
            return ($m['service_id'] == $user['service_id']) && ($manager === 1) && ($dirService === 0);
        case 4: // DG
            return (
                ($dirService === 1 && $lancement === 0) ||   // lancement initial
                ($dfValide === 1 && $dgValFinal === 0)       // validation finale après DF
            );
        case 5: // RH
            return ($lancement === 1 && $rhPreparer === 0);
        case 6: // DF
            return ($lancement === 1 && $rhPreparer === 1 && $dfValide === 0);
        default:
            return false;
    }
}

/* ------------------ FILTRAGE DES MISSIONS SELON RÔLE ------------------ */
/* ------------------ FILTRAGE DES MISSIONS SELON RÔLE ------------------ */
$where  = []; 
$params = [];

switch ($user['role']) {
    case 2: // Manager
        $where[] = '(service_id = :sid AND (manager_validation = 0 OR manager_validation IS NULL) AND (statut IS NULL OR statut != "Rejetée"))';
        $params[':sid'] = $user['service_id'];
        break;
    case 3: // Directeur
        $where[] = '(service_id = :sid AND manager_validation = 1 AND (dir_service_validation = 0 OR dir_service_validation IS NULL) AND (statut IS NULL OR statut != "Rejetée"))';
        $params[':sid'] = $user['service_id'];
        break;
    case 4: // DG
        $where[] = '(
            (dir_service_validation = 1 AND lancement = 0) OR
            (df_valide = 1 AND (dg_valide_final = 0 OR dg_valide_final IS NULL))
        ) AND (statut IS NULL OR statut != "Rejetée")';
        break;
    case 5: // RH
        $where[] = '(lancement = 1 AND (rh_preparer = 0 OR rh_preparer IS NULL) AND (statut IS NULL OR statut != "Rejetée"))';
        break;
    case 6: // DF
        $where[] = '(lancement = 1 AND rh_preparer = 1 AND (df_valide = 0 OR df_valide IS NULL) AND (statut IS NULL OR statut != "Rejetée"))';
        break;
    default:
        $where[] = '1=0'; // Aucun accès
        break;
}


$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$sqlNotif = "SELECT * FROM missions $whereSql ORDER BY id DESC";
$stmt = $pdo->prepare($sqlNotif);
$stmt->execute($params);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$nbNotif  = count($missions);



/* ------------------ DATA: Statistiques générales ------------------ */
$today = date('Y-m-d');

/* ------------------ STATISTIQUES GLOBALES ------------------ */
$stats = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(statut='En attente') AS en_attente,
        SUM(statut='Validée')   AS validees,
        SUM(statut='Lancée')    AS lancees,
        SUM(CASE WHEN statut='En cours' AND date_fin >= '$today' THEN 1 ELSE 0 END) AS en_cours,
        SUM(statut='Rejetée')   AS rejetees,
        (SELECT COUNT(*) FROM missions WHERE date_fin IS NOT NULL AND DATE(date_fin) < '$today') AS terminees
    FROM missions
")->fetch(PDO::FETCH_ASSOC);

// Cast en int pour toutes les clés
$keys = ['total','en_attente','validees','lancees','en_cours','rejetees','terminees'];
foreach ($keys as $k) {
    $stats[$k] = (int)$stats[$k];
}





/* ------------------ DATA PAR RÔLE ------------------ */
// Récupération des stats par statut et service
if ($user['service_id']) {
    // Pour utilisateur avec serviceen 
    $stmt = $pdo->prepare("
        SELECT statut, COUNT(*) AS nb
        FROM missions
        WHERE service_id = :service_id
        GROUP BY statut
    ");
    $stmt->execute([':service_id'=>$user['service_id']]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    foreach ($data as $d) {
        $labels[] = $d['statut'];
        $values[] = (int)$d['nb'];
    }

    $chartOptions = json_encode([
        'plugins' => ['legend' => ['display' => false]]
    ]);

} else {
    // Pour DG/RH sans service, tous les services
    $data = $pdo->query("
        SELECT s.nom AS service, m.statut, COUNT(*) AS nb
        FROM missions m
        LEFT JOIN services s ON m.service_id = s.id
        GROUP BY s.nom, m.statut
    ")->fetchAll(PDO::FETCH_ASSOC);

    $services = [];
    $statuts = [];
    foreach ($data as $d) {
        $services[$d['service']] ??= [];
        $services[$d['service']][$d['statut']] = (int)$d['nb'];
        $statuts[$d['statut']] = true;
    }

    $labels = array_keys($statuts);
    $datasets = [];
    $colors = ['#1abc9c','#3498db','#f39c12','#e74c3c','#9b59b6','#34495e']; 
    $i = 0;
    foreach ($services as $serviceName => $counts) {
        $dataService = [];
        foreach ($labels as $l) $dataService[] = $counts[$l] ?? 0;
        $datasets[] = [
            'label' => $serviceName,
            'data' => $dataService,
            'backgroundColor' => $colors[$i % count($colors)]
        ];
        $i++;
    }
}
/* DG (4): missions par service + montants  */
$missions_par_service = [];
$dg_tot_montant_prev = 0; $dg_tot_montant_depense = 0; $dg_tot_missions = 0;
if ($user['role']===4) {
    $missions_par_service = $pdo->query("
        SELECT s.id, s.nom AS service,
               COUNT(m.id) AS nb_missions,
               COALESCE(SUM(m.montant_prevu),0) AS montant_prev,
               COALESCE(SUM(m.montant_utilise),0) AS montant_depense
        FROM services s
        LEFT JOIN missions m ON m.service_id=s.id
        GROUP BY s.id
        ORDER BY nb_missions DESC, s.nom
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($missions_par_service as $row) {
        $dg_tot_missions += (int)$row['nb_missions'];
        $dg_tot_montant_prev += (float)$row['montant_prev'];
        $dg_tot_montant_depense += (float)$row['montant_depense'];
    }
}

if ($user['role'] === 3 && $user['service_id']) {
    // Tous les personnels de son service
    $dir_personnels = $pdo->prepare("SELECT id, nom, prenom FROM personnels WHERE service_id = :sid");
    $dir_personnels->execute([':sid'=>$user['service_id']]);
    $dir_personnels = $dir_personnels->fetchAll(PDO::FETCH_ASSOC);

    // IDs actifs dans des missions en cours/lancées
   $encours = $pdo->prepare("
    SELECT DISTINCT u.id
    FROM utilisateurs u
    JOIN missions m ON FIND_IN_SET(u.id, m.personnels)>0
    WHERE u.service_id=:sid AND m.statut IN ('Lancée','En cours')
");
$encours->execute([':sid'=>$user['service_id']]);
$actifs_ids = array_column($encours->fetchAll(PDO::FETCH_ASSOC), 'id');

    $dir_actifs = []; $dir_disponibles = [];
    foreach ($dir_personnels as $p) {
        if (in_array($p['id'], $actifs_ids)) $dir_actifs[] = $p;
        else $dir_disponibles[] = $p;
    }

    // Statistiques missions
    $dir_missions_encours = (int)$pdo->query("
        SELECT COUNT(*) FROM missions 
        WHERE service_id = {$user['service_id']} AND statut IN ('Lancée','En cours')
    ")->fetchColumn();

    $dir_missions_terminees = (int)$pdo->query("
        SELECT COUNT(*) FROM missions 
        WHERE service_id = {$user['service_id']} AND statut IN ('Validée','Rejetée')
    ")->fetchColumn();
}
$sql = "
    SELECT p.id, p.nom, p.prenom, p.poste, s.nom AS service,
           COUNT(m.id) AS missions_attribuees
    FROM personnels p
    LEFT JOIN services s ON p.service_id = s.id
    LEFT JOIN missions m ON FIND_IN_SET(p.id, m.personnels) > 0 AND m.lancement = 1
    GROUP BY p.id, p.nom, p.prenom, p.poste, s.nom
    ORDER BY missions_attribuees ASC, p.nom ASC
";
$stmt = $pdo->query($sql);
$personnels = $stmt->fetchAll(PDO::FETCH_ASSOC);


/* RH (5): tableau personnel + services les plus actifs + mois courant */
$rh_personnels = []; $rh_services = []; $rh_top_services = []; $rh_mois = [];
if ($user['role']===5) {
    $rh_personnels = $pdo->query("
        SELECT p.id, p.nom, p.prenom, COALESCE(s.nom,'-') AS service,
               SUM(CASE WHEN m.statut IN ('Lancée','En cours') AND FIND_IN_SET(p.id,m.personnels)>0 THEN 1 ELSE 0 END) AS missions_attribuees
        FROM personnels p
        LEFT JOIN services s ON s.id=p.service_id
        LEFT JOIN missions m ON FIND_IN_SET(p.id,m.personnels)>0
        GROUP BY p.id
        ORDER BY s.nom, p.nom
    ")->fetchAll(PDO::FETCH_ASSOC);

    $rh_services = $pdo->query("
        SELECT s.nom AS service, COUNT(m.id) AS nb_missions,
               COALESCE(SUM(m.montant_prevu),0) AS montant_prev,
               COALESCE(SUM(m.montant_utilise),0) AS montant_depense
        FROM services s
        LEFT JOIN missions m ON m.service_id=s.id
        GROUP BY s.id
        ORDER BY nb_missions DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $rh_top_services = array_slice($rh_services, 0, 5);

    $rh_mois = $pdo->query("
        SELECT DATE_FORMAT(date_debut,'%Y-%m') AS ym,
               COUNT(*) nb
        FROM missions
        GROUP BY ym
        ORDER BY ym DESC
        LIMIT 6
    ")->fetchAll(PDO::FETCH_ASSOC);
}


/* DF (6): missions à valider + agrégats */
$df_missions_a_valider = []; $df_missions_financees = 0; $df_montant_engage = 0;
if ($user['role']===6) {
    $df_missions_a_valider = $pdo->query("
        SELECT * FROM missions
        WHERE lancement=1 AND rh_preparer=1 AND (df_valide=0 OR df_valide IS NULL)
        ORDER BY id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $df_missions_financees = (int)$pdo->query("SELECT COUNT(*) FROM missions WHERE df_valide=1")->fetchColumn();
    $df_montant_engage     = (float)$pdo->query("SELECT COALESCE(SUM(montant_utilise),0) FROM missions WHERE df_valide=1")->fetchColumn();
}

/* ------------------ PRÉP CHARTS (JSON) ------------------ */
$chart_global_labels = ['En attente','Validée','Lancée','En cours','Rejetée'];
$chart_global_data   = [$stats['en_attente'],$stats['validees'],$stats['lancees'],$stats['en_cours'],$stats['rejetees']];

$dg_labels = []; $dg_depense = []; $dg_prev = [];
if ($user['role']===4) {
    foreach ($missions_par_service as $r) {
        $dg_labels[]  = $r['service'];
        $dg_depense[] = (float)$r['montant_depense'];
        $dg_prev[]    = (float)$r['montant_prev'];
    }
}
$rh_labels_services=[]; $rh_data_services=[];
if ($user['role']===5) {
    foreach ($rh_top_services as $r) { $rh_labels_services[]=$r['service']; $rh_data_services[]=(int)$r['nb_missions']; }
}
$mois_labels=[]; $mois_data=[];
if ($user['role']===5) {
    $tmp = array_reverse($rh_mois);
    foreach ($tmp as $r){ $mois_labels[]=$r['ym']; $mois_data[]=(int)$r['nb']; }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard - Niger Telecoms</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <style>
        /* === Sidebar === */
        .sidebar {
            width: 240px;
            background: #fff;
            box-shadow: 2px 0 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: width 0.3s ease;
            z-index: 10000;
        }

        /* Logo */
        .sidebar-logo {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
            background: #fff;
        }
        .sidebar-logo img {
            max-width: 120px;
            height: auto;
        }

        /* Liens du menu */
        .sidebar nav {
            display: flex;
            flex-direction: column;
            padding: 10px 0;
            flex-grow: 1;
        }
        .sidebar nav a,
        .sidebar nav button.submenu-toggle {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            text-decoration: none;
            color: #1a202c;
            font-weight: 500;
            background: none;
            border: none;
            transition: background 0.2s ease, color 0.2s ease;
            cursor: pointer;
            width: 100%;
            text-align: left;
            font-size: 1rem;
        }
        .sidebar nav a i,
        .sidebar nav button.submenu-toggle i {
            font-size: 1.2rem;
            color: #007a33;
        }
        .sidebar nav a:hover,
        .sidebar nav a.active,
        .sidebar nav button.submenu-toggle:hover {
            background: #007a33;
            color: #fff;
        }
        .sidebar nav a:hover i,
        .sidebar nav a.active i,
        .sidebar nav button.submenu-toggle:hover i {
            color: #fff;
        }

        /* Sous-menus */
        .submenu {
            list-style: none;
            padding-left: 20px;
            display: none; /* masqué par défaut */
            flex-direction: column;
            margin: 0;
        }
        .submenu.show {
            display: flex;
        }
        .submenu li a {
            padding: 8px 0;
            font-size: 0.95rem;
            color: #1a202c;
        }
        .submenu li a:hover {
            color: #f7941d;
        }

        /* Flèche sous-menu */
        .submenu-toggle .fa-caret-down {
            margin-left: auto;
            transition: transform 0.3s ease;
        }
        .submenu-toggle[aria-expanded="true"] .fa-caret-down {
            transform: rotate(180deg);
        }

        /* Content Wrapper */
        .content-wrapper {
            margin-left: 240px;
            padding: 20px;
            transition: margin-left 0.3s ease;
        }

        /* Quand sidebar est repliée */
        .sidebar.collapsed {
            width: 60px;
        }
        .content-wrapper.collapsed {
            margin-left: 60px;
        }

        /* Dropdown notifications */
        .dropdown {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 300px;
            max-height: 300px;
            overflow-y: auto;
            display: none;
            z-index: 11000;
            right: 20px;
            top: 50px;
            padding: 10px;
            border-radius: 4px;
        }
        .dropdown.show {
            display: block;
        }
        .dropdown a {
            display: block;
            padding: 8px 10px;
            color: #007a33;
            text-decoration: none;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }
        .dropdown a:hover {
            background: #f0f8f5;
            color: #004d1a;
        }
        .dropdown p {
            margin: 0;
            padding: 10px;
            color: #777;
            font-style: italic;
        }

        /* Style bouton */
        .btn {
            padding: 6px 12px;
            background: #007a33;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        .btn:hover {
            background: #005d24;
            color: #fff;
            text-decoration: none;
        }

        /* Table */
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
        }
        th {
            background-color: #007a33;
            color: white;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        /* Header */
        .header {
            position: fixed;
            top: 0;
            left: 240px;
            right: 0;
            height: 50px;
            background: #007a33;
            color: white;
            display: flex;
            align-items: center;
            padding: 0 20px;
            justify-content: space-between;
            transition: left 0.3s ease;
            z-index: 12000;
        }
        .content-wrapper.collapsed + .header {
            left: 60px;
        }
        #toggleSidebar {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
        }
        .notif-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.3rem;
        }
        .badge {
            position: absolute;
            top: -5px;
            right: -10px;
            background: #f7941d;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Stats cards */
        .stats {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 70px; /* sous header fixe */
        }
        .card {
            flex: 1 1 150px;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgb(0 0 0 / 0.1);
            padding: 15px;
            text-align: center;
            color: #007a33;
            font-weight: 600;
            font-size: 1.2rem;
        }

        /* Animations */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                left: -240px;
                position: fixed;
                transition: left 0.3s ease;
                z-index: 20000;
            }
            .sidebar.show {
                left: 0;
            }
            .content-wrapper {
                margin-left: 0;
                padding: 70px 15px 15px;
            }
            .header {
                left: 0;
            }
        }
        .mission-bubbles {
    margin: 20px 0;
}
.bubbles-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}
.bubble {
    background: #fff;
    padding: 15px 20px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    flex: 1 1 250px;
    max-width: 300px;
    transition: transform 0.2s;
}
.bubble:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
.bubble h3 {
    margin-top: 0;
}
.bubble .btn {
    display: inline-block;
    margin-top: 10px;
    padding: 8px 12px;
    background: #3498db;
    color: #fff;
    border-radius: 6px;
    text-decoration: none;
}
.bubble .btn:hover {
    background: #2980b9;
}
.content-wrapper {
    background: #f8fafc; /* clair mais soft pour matcher avec ton header vert */
    min-height: 100vh;
    padding: 20px;
    color: #1a202c;
}

/* KPI Cards */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit,minmax(200px,1fr));
    gap: 16px;
    margin-top: 20px;
}
.card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.08);
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-3px);
}
.card h3 {
    font-size: 14px;
    color: #64748b;
    margin: 0 0 6px;
}
.kpi {
    font-size: 22px;
    font-weight: 800;
    color: #007a33;
}

/* Badges / pills pour statuts */
.pill {
    padding: 2px 8px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
}
.pill.wait { background:#fff7ed; color:#f59e0b; }
.pill.ok { background:#ecfdf5; color:#10b981; }
.pill.run { background:#e0f2fe; color:#0284c7; }
.pill.err { background:#fef2f2; color:#ef4444; }

/* Section titres */
.section-title {
    margin: 20px 0 10px;
    font-size: 18px;
    font-weight: 700;
    color: #1a202c;
}
.subtitle {
    margin: 4px 0 12px;
    font-size: 14px;
    color: #64748b;
}

/* Table moderne */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
    font-size: 14px;
}
th, td {
    padding: 10px;
    border-bottom: 1px solid #e2e8f0;
}
th {
    background: #f1f5f9;
    font-weight: 600;
    color: #334155;
}
tr:hover td {
    background: #f9fafb;
}

/* Graphiques / sections */
.charts {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-top: 20px;
}
@media(max-width: 980px) {
    .charts {
        grid-template-columns: 1fr;
    }
}
.missions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.mission-card {
    background: #fff;
    border: 1px solid #e3e3e3;
    border-radius: 10px;
    padding: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: transform 0.2s;
}
.mission-card:hover {
    transform: translateY(-3px);
}

.mission-card h4 {
    margin: 0 0 10px;
    font-size: 1.1rem;
    color: #2c3e50;
}

.status {
    font-size: 0.9rem;
    font-weight: 500;
}
.status-green {
    color: #27ae60; /* vert */
}
.status-orange {
    color: #e67e22; /* orange */
}
.status-red {
    color: #c0392b; /* rouge */
}

.mission-card .date {
    font-size: 0.85rem;
    color: #7f8c8d;
    margin: 5px 0 15px;
}

.actions {
    display: flex;
    gap: 10px;
}

.btn {
    display: inline-block;
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    text-decoration: none;
    transition: background 0.2s;
}

.btn-validate {
    background: #27ae60;
    color: #fff;
}
.btn-validate:hover {
    background: #219150;
}

.btn-secondary {
    background: #f4f4f4;
    color: #333;
    border: 1px solid #ddd;
}
.btn-secondary:hover {
    background: #eaeaea;
}
.btn-toggle {
    display: inline-block;
    margin: 10px 0;
    padding: 8px 14px;
    background: #34db66ff;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
}
.btn-toggle:hover {
    background: #b98729ff;
}
.users-connected {
    background-color: #fff;
    color: #333;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
    max-height: 200px;
    overflow-y: auto;
}

.users-connected ul {
    list-style: none;
    margin: 5px 0 0;
    padding-left: 0;
}

.users-connected li {
    margin-bottom: 3px;
    font-weight: 600;
    color: #16a085;
}
.badge {
    padding: 5px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9em;
    display: inline-block;
}

.badge.dispo {
    background-color: #28a745; /* vert */
    color: white;
}

.badge.mission {
    background-color: #dc3545; /* rouge */
    color: white;
}
.kpi {
    background: rgba(255,255,255,0.2);
    padding: 0.5rem;
    border-radius: 4px;
}


    </style>
</head> 

<body>
        
<div class="dashboard">
    <aside class="sidebar" id="sidebar" role="navigation" aria-label="Menu principal">
        <div class="sidebar-logo">
            <img src="images/logo.jpg" alt="Niger Telecoms Logo" />
        </div>
        <nav>
            <a href="dashboard.php" class="active">
        <i class="fas fa-tachometer-alt"></i> Tableau de bord
    </a>

    <!-- Missions -->
    <a href="#" 
   class="submenu-toggle" 
   aria-expanded="false" 
   aria-controls="submenu-missions">
   <i class="fas fa-briefcase"></i> Missions
   <i class="fas fa-chevron-down"></i>
</a>

<ul class="submenu" id="submenu-missions">
    <?php if (in_array($user['role'], [1,2,3])): ?>
        <li><a href="mes_missions.php"><i class="fas fa-list"></i> Mes missions</a></li>
    <?php endif; ?>

    <?php if ($user['role'] == 5): ?>
        <li><a href="historique_mission.php"><i class="fas fa-tasks"></i> Toutes les missions</a></li>
    <?php endif; ?>

    <?php if ($user['role'] == 1): ?>
        <li><a href="proposer_mission.php"><i class="fas fa-plus-circle"></i> Proposer une mission</a></li>
    <?php endif; ?>
</ul>

 <!-- Traitement missions RH -->
                
            <?php if ($user['role'] === 5): ?>
                <button class="submenu-toggle" aria-expanded="false" aria-controls="submenu-traitement" tabindex="0">
                    <i class="fas fa-tasks"></i> Traitement <i class="fas fa-caret-down"></i>
                </button>
                <ul class="submenu" id="submenu-traitement" >
                     <li><a href="rh_preparer.php" tabindex="0"><i class="fas fa-tasks"></i> Traiter les missions</a></li>
                    
                    <li><a href="liste_ordres.php" tabindex="0"><i class="fas fa-list"></i> Suivi des demandes</a></li>
                </ul>
            <?php endif; ?>
            <!-- Gestion de service pour DG (4) -->
            <?php if (in_array($user['role'], [4])): ?>
                <button class="submenu-toggle" aria-expanded="false" aria-controls="submenu-gestion-service" tabindex="0">
                    <i class="fa fa-cogs"></i> Gestion de service <i class="fa fa-caret-down"></i>
                </button>
                <ul class="submenu" id="submenu-gestion-service" >
                    <li><a href="services.php" tabindex="0">Ajouter un service</a></li>
                    
                </ul>
            <?php endif; ?>

            <!-- Gestion Personnel RH -->
            <?php if ($user['role'] === 5): ?>
                <button class="submenu-toggle" aria-expanded="false" aria-controls="submenu-gestion-personnel" tabindex="0">
                    <i class="fas fa-user-friends"></i> Gestion  des agents <i class="fas fa-caret-down"></i>
                </button>
                <ul class="submenu" id="submenu-gestion-personnel" >
                    <li><a href="personnel_ajouter.php" tabindex="0"><i class="fas fa-user-plus"></i> Ajouter un agent</a></li>
                    
                </ul>
                
            <?php endif; ?>

            <!-- DG role (4) - gestion utilisateurs et stats -->
            <?php if ($user['role'] === 4): ?>
                <button class="submenu-toggle" aria-expanded="false" aria-controls="submenu-utilisateurs-dg" tabindex="0">
                    <i class="fas fa-users-cog"></i> Gestion des roles <i class="fas fa-caret-down"></i>
                </button>
                <ul class="submenu" id="submenu-utilisateurs-dg" >
                    <li><a href="roles.php" tabindex="0"><i class="fas fa-user-plus"></i> RH/DF</a></li>
                   
                    
                </ul>
              <?php endif; ?> 
            <!-- DG role (4) - gestion utilisateurs et stats -->
            <?php if ($user['role'] === 5): ?>
                <button class="submenu-toggle" aria-expanded="false" aria-controls="submenu-utilisateurs-dg" tabindex="0">
                    <i class="fas fa-users-cog"></i> Gestion des utilisateurs <i class="fas fa-caret-down"></i>
                </button>
                <ul class="submenu" id="submenu-utilisateurs-dg" >
                    <li><a href="utilisateurs.php" tabindex="0"><i class="fas fa-user-plus"></i> agent</a></li>
                  
                </ul>
              <?php endif; ?>  
                <?php if ($user['role'] === 6): ?>
                    <li><a href="df_fonds.php"><i class="fas fa-plus-circle"></i>Gerer les fonds</a></li>
                <?php endif; ?>

              <a href="profil.php" tabindex="0"><i class="fas fa-sign-out-alt"></i> profile </a>  
            <a href="logout.php" tabindex="0"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
        </nav>
    </aside>

    <div class="content-wrapper" id="content-wrapper">
        <header class="header">
            <button id="toggleSidebar" aria-label="Basculer menu">
                <i class="fas fa-bars"></i>
            </button>
                    
            <div style="display: flex; align-items:center; gap: 15px;">
                <div style="font-weight:700; font-size:1.1rem; color:#fff;">
                    <?= $user['nom'] ?> (<?= getRoleName($user['role']) ?>)
                     <?php if (isset($user['service_id']) && $user['service_id'] != null): ?>
                - Service: <?= htmlspecialchars($services[$user['service_id']] ?? '') ?>
            <?php endif; ?>
                </div>
                
                 <div class="notif-icon" id="notifToggle" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false" aria-label="Notifications">
    <i class="fas fa-bell"></i>
    <?php if ($nbNotif > 0): ?>
        <span class="badge"><?= $nbNotif ?></span>
    <?php endif; ?>
</div>


<div id="notifDropdown" class="dropdown" aria-hidden="true" tabindex="-1">
    <?php if ($nbNotif > 0): ?>
        <?php foreach ($missions as $mission): ?>
           <?php if (canUserAct($mission, $user)): ?>
        <a href="valider_mission.php?id=<?= $mission['id'] ?>" tabindex="0">
            <?= htmlspecialchars($mission['titre']) ?> - <?= getWorkflowMessage($mission) ?>
        </a>
    <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Aucune notification.</p>
    <?php endif; ?>
</div>

            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        </header>

    <div id="mainContent" class="main-content">
    <div class="dashboard-cards">
     
 <!-- KPIs globaux communs -->
<div class="cards" style="display:flex; gap:1rem; flex-wrap:wrap;">
    <div class="card" style="flex:1; padding:1rem; background:#f9f9f9; color:#333; border-radius:8px; text-align:center; box-shadow:0 0 5px rgba(0,0,0,0.1);">
        <h3>Total missions</h3>
        <div class="kpi" style="font-size:2rem; font-weight:bold;"><?= $stats['total'] ?></div>
    </div>
    
    <div class="card" style="flex:1; padding:1rem; background:#f9f9f9; color:#333; border-radius:8px; text-align:center; box-shadow:0 0 5px rgba(0,0,0,0.1);">
        <h3>Terminées</h3>
        <div class="kpi" style="font-size:2rem; font-weight:bold;"><?= $stats['terminees'] ?></div>
    </div>
    <div class="card" style="flex:1; padding:1rem; background:#f9f9f9; color:#333; border-radius:8px; text-align:center; box-shadow:0 0 5px rgba(0,0,0,0.1);">
        <h3>Lancées</h3>
        <div class="kpi" style="font-size:2rem; font-weight:bold;"><?= $stats['lancees'] ?></div>
    </div>
    <div class="card" style="flex:1; padding:1rem; background:#f9f9f9; color:#333; border-radius:8px; text-align:center; box-shadow:0 0 5px rgba(0,0,0,0.1);">
        <h3>En cours</h3>
        <div class="kpi" style="font-size:2rem; font-weight:bold;"><?= $stats['en_cours'] ?></div>
    </div>
    
    <div class="card" style="flex:1; padding:1rem; background:#f9f9f9; color:#333; border-radius:8px; text-align:center; box-shadow:0 0 5px rgba(0,0,0,0.1);">
        <h3>Rejetées</h3>
        <div class="kpi" style="font-size:2rem; font-weight:bold;"><?= $stats['rejetees'] ?></div>
    </div>
</div>


   <div class="grid-2">
    <!-- Notifications / Missions à traiter -->
    <div class="card">
        <div class="section-title">
             Vous Avez  (<?= $nbNotif ?>) Missions à traiter
        </div>

        <?php if ($nbNotif === 0): ?>
            <div class="subtitle">Aucune mission à traiter.</div>
        <?php else: ?>
            <!-- Bouton pour afficher/masquer -->
            <button class="btn-toggle" onclick="toggleMissions()">Afficher / Masquer</button>

            <div id="missionsContainer" class="missions-grid" style="display:none;">
                <?php foreach ($missions as $m): if (!canUserAct($m, $user)) continue; ?>
                    <?php
                        // Couleur statut
                        $statusClass = "status-orange";
                        if ($m['statut'] === "Validée") $statusClass = "status-green";
                        if ($m['statut'] === "Rejetée") $statusClass = "status-red";

                        // Lien selon rôle
                        switch ($user['role']) {
                            case 1: case 2: case 3: case 4: 
                                $lien = "valider_mission.php?id=" . $m['id'];
                                break;
                            case 5:
                                $lien = "rh_preparer.php?id=" . $m['id'];
                                break;
                            case 6:
                                $lien = "df_fonds.php?id=" . $m['id'];
                                break;
                            default:
                                $lien = "#";
                        }
                    ?>
                    <div class="mission-card">
                        <h4><?= htmlspecialchars($m['titre']) ?></h4>
                        <p class="status <?= $statusClass ?>"><?= getWorkflowMessage($m) ?></p>
                        <p class="date">📅 Début : <?= htmlspecialchars($m['date_debut']) ?></p>
                        
                        <div class="actions">
                            <a href="<?= $lien ?>" class="btn btn-validate">Traiter</a>
                            <a href="details_mission.php?id=<?= $m['id'] ?>" class="btn btn-secondary">Voir détails</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<div class="card">
    <div class="section-title">Répartition par statut</div>
    <canvas id="chartGlobal_<?= $user['id'] ?>" height="100"></canvas>
</div>

<script>
const ctx_<?= $user['id'] ?> = document.getElementById('chartGlobal_<?= $user['id'] ?>').getContext('2d');

<?php if ($user['service_id']): ?>
// Chart simple pour son service
new Chart(ctx_<?= $user['id'] ?>, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [{
            label: "<?= addslashes(getServiceName($user['service_id'], $pdo)) ?>",
            data: <?= json_encode($values) ?>,
            backgroundColor: '#1abc9c'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: true, position: 'bottom' },
            title: { display: true, text: 'Répartition des missions par statut' }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Nombre de missions' } },
            x: { title: { display: true, text: 'Statuts' } }
        }
    }
});
<?php else: ?>
// Chart pour tous les services avec légende
new Chart(ctx_<?= $user['id'] ?>, {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: <?= json_encode($datasets) ?>
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 20, font: { weight: 'bold' } } },
            title: { display: true, text: 'Comparaison des services' }
        },
        scales: {
            y: { beginAtZero: true, title: { display: true, text: 'Nombre de missions' } },
            x: { title: { display: true, text: 'Statuts' } }
        }
    }
});
<?php endif; ?>
</script>


<script>
function toggleMissions() {
    const container = document.getElementById("missionsContainer");
    container.style.display = (container.style.display === "none") ? "grid" : "none";
}
</script>



    <!-- SECTIONS PAR RÔLE -->
    <?php if ($user['role']===4): /* -------- DG -------- */ ?>
        <div class="grid" style="margin-top:14px">
            <div class="cards">
                
                <div class="card"><h3>Montant prévu total</h3><div class="kpi"><?= number_format($dg_tot_montant_prev,0,',',' ') ?> FCFA</div></div>
                <div class="card"><h3>Missions (tous services)</h3><div class="kpi"><?= $dg_tot_missions ?></div></div>
                <div class="card"><h3>Services actifs</h3><div class="kpi"><?= count($missions_par_service) ?></div></div>
            </div>

            <div class="card">
                <div class="section-title">Missions & montants par service</div>
                <table>
                    <thead><tr>
                        <th>Service</th><th>Nb missions</th><th>Montant prévu</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($missions_par_service as $r):
                        $prev=(float)$r['montant_prev']; $dep=(float)$r['montant_depense'];
                        $pct = $prev>0 ? ($dep/$prev*100) : 0;
                        $class = $pct>110?'err':($pct>85?'wait':'ok');
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($r['service']) ?></td>
                            <td><?= (int)$r['nb_missions'] ?></td>
                            <td><?= number_format($prev,0,',',' ') ?> FCFA</td>
                           
                           
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="charts">
                
                <div class="card"><div class="section-title">Top services (par missions)</div>
                    <table>
                        <thead><tr><th>Service</th><th>Missions</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($missions_par_service,0,8) as $r): ?>
                            <tr><td><?= htmlspecialchars($r['service']) ?></td><td><?= (int)$r['nb_missions'] ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php elseif ($user['role']===3): /* -------- Directeur de service -------- */ ?>
        <div class="grid" style="margin-top:14px">
            <div class="cards">
                <div class="card"><h3>Missions en cours (mon service)</h3><div class="kpi"><?= $dir_missions_encours ?></div></div>
                <div class="card"><h3>Missions terminées</h3><div class="kpi"><?= $dir_missions_terminees ?></div></div>
                <div class="card"><h3>Agents actifs</h3><div class="kpi"><?= count($dir_actifs) ?></div></div>
                <div class="card"><h3>Agents disponibles</h3><div class="kpi"><?= count($dir_disponibles) ?></div></div>
            </div>

           <div class="grid-2">
        <!-- Personnel disponible -->
        <div class="card">
            <h3>Personnels disponibles</h3>
            <ul>
                <?php if(empty($dir_disponibles)): ?>
                    <li>Aucun personnel disponible</li>
                <?php else: ?>
                    <?php foreach($dir_disponibles as $p): ?>
                        <li><?= htmlspecialchars($p['prenom'].' '.$p['nom']) ?></li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

       
    </div>

    <!-- Statistiques missions -->
   
    </div>
        </div>

    <?php elseif ($user['role']===5): /* -------- RH -------- */ ?>
        <div class="grid" style="margin-top:14px">
            <div class="cards">
                <div class="card"><h3>Agents (total)</h3><div class="kpi"><?= count($rh_personnels) ?></div></div>
                <div class="card"><h3>Services</h3><div class="kpi"><?= count($rh_services) ?></div></div>
                <div class="card"><h3>Top service (missions)</h3>
                    <div class="kpi"><?= $rh_top_services[0]['service'] ?? '-' ?></div>
                    <div class="subtitle"><?= $rh_top_services[0]['nb_missions'] ?? 0 ?> missions</div>
                </div>
                <div class="card"><h3>Missions (6 derniers mois)</h3><div class="kpi"><?= array_sum($mois_data) ?></div></div>
            </div>


</tbody>

    </table>
</div>


            <div class="charts">
                <div class="card">
                    <div class="section-title">Services les plus actifs (missions)</div>
                    <canvas id="chartRHservices" height="160"></canvas>
                </div>
                <div class="card">
                    <div class="section-title">Volume mensuel (6 derniers mois)</div>
                    <canvas id="chartRHmois" height="160"></canvas>
                </div>
            </div>

            <div class="card">
                <div class="section-title">Missions par service (montants)</div>
                <table>
                    <thead><tr>
                        <th>Service</th><th>Nb</th><th>Montant prévu</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($rh_services as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['service']) ?></td>
                            <td><?= (int)$r['nb_missions'] ?></td>
                            <td><?= number_format((float)$r['montant_prev'],0,',',' ') ?> FCFA</td>
                            
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php elseif ($user['role']===6): /* -------- DF -------- */ ?>
        <div class="grid" style="margin-top:14px">
            <div class="cards">
                <div class="card"><h3>Missions financées</h3><div class="kpi"><?= $df_missions_financees ?></div></div>
                <div class="card"><h3>Montant engagé</h3><div class="kpi"><?= number_format($df_montant_engage,0,',',' ') ?> FCFA</div></div>
                <div class="card"><h3>À valider (DF)</h3><div class="kpi"><?= count($df_missions_a_valider) ?></div></div>
                <div class="card"><h3>Reste à lancer</h3><div class="kpi"><?= (int)$stats['lancees'] ?></div></div>
            </div>

            <div class="card">
                <div class="section-title">Missions en attente de validation DF</div>
                <table>
                    <thead><tr>
                        <th>Titre</th><th>Service</th><th>Début</th><th>Fin</th><th>Montant prévu</th><th>Action</th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($df_missions_a_valider as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['titre']) ?></td>
                            <td><?= htmlspecialchars($services[$m['service_id']] ?? '-') ?></td>
                            <td><?= htmlspecialchars($m['date_debut']) ?></td>
                            <td><?= htmlspecialchars($m['date_fin']) ?></td>
                            <td><?= number_format((float)$m['montant_prevu'],0,',',' ') ?> FCFA</td>
                            <td><a class="btn" href="valider_mission.php?id=<?= $m['id'] ?>">Valider</a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="foot">© Niger Télécoms — Dashboard missions</div>
</div>


</div>
    </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.getElementById('sidebar');
    const contentWrapper = document.getElementById('content-wrapper');
    const toggleBtn = document.getElementById('toggleSidebar');
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    const notifToggle = document.getElementById('notifToggle');
    const notifDropdown = document.getElementById('notifDropdown');

    // Toggle sidebar narrow / large
    toggleBtn.addEventListener('click', () => {
        sidebar.classList.toggle('collapsed');
        contentWrapper.classList.toggle('collapsed');
    });

    // Toggle sous-menus
    submenuToggles.forEach(btn => {
        btn.addEventListener('click', () => {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            btn.setAttribute('aria-expanded', String(!expanded));
            const submenuId = btn.getAttribute('aria-controls');
            const submenu = document.getElementById(submenuId);
            if (submenu) {
                submenu.classList.toggle('show');
            }
        });
    });

    // Toggle notifications dropdown
    notifToggle.addEventListener('click', () => {
        const isVisible = notifDropdown.classList.contains('show');
        notifDropdown.classList.toggle('show', !isVisible);
        notifToggle.setAttribute('aria-expanded', String(!isVisible));
        notifDropdown.setAttribute('aria-hidden', String(isVisible));
    });

    // Close notifications dropdown on outside click
    document.addEventListener('click', (e) => {
        if (!notifToggle.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.classList.remove('show');
            notifToggle.setAttribute('aria-expanded', 'false');
            notifDropdown.setAttribute('aria-hidden', 'true');
        }
    });

    // Close sous-menus when clicking outside (optional)
    document.addEventListener('click', (e) => {
        submenuToggles.forEach(btn => {
            const submenuId = btn.getAttribute('aria-controls');
            const submenu = document.getElementById(submenuId);
            if (submenu && !btn.contains(e.target) && !submenu.contains(e.target)) {
                btn.setAttribute('aria-expanded', 'false');
                submenu.classList.remove('show');
            }
        });
    });

});

</script>
<script>
/* ------- Graphique global par statut ------- */
new Chart(document.getElementById('chartGlobal'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chart_global_labels) ?>,
        datasets: [{ label:'Missions', data: <?= json_encode($chart_global_data) ?> }]
    },
    options:{ responsive:true, plugins:{ legend:{display:false} } }
});

/* ------- DG: Dépense vs Prévu ------- */
<?php if ($user['role']===4): ?>
new Chart(document.getElementById('chartDG'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($dg_labels) ?>,
        datasets: [
            { label:'Dépensé', data: <?= json_encode($dg_depense) ?> },
            { label:'Prévu',   data: <?= json_encode($dg_prev) ?> }
        ]
    },
    options:{ responsive:true, plugins:{ legend:{ position:'top' } } }
});
<?php endif; ?>

/* ------- RH: Top services & 6 derniers mois ------- */
<?php if ($user['role']===5): ?>
new Chart(document.getElementById('chartRHservices'), {
    type: 'bar',
    data: { labels: <?= json_encode($rh_labels_services) ?>,
            datasets:[{ label:'Missions', data: <?= json_encode($rh_data_services) ?> }] },
    options:{ responsive:true, plugins:{ legend:{display:false} } }
});

new Chart(document.getElementById('chartRHmois'), {
    type: 'line',
    data: { labels: <?= json_encode($mois_labels) ?>,
            datasets:[{ label:'Missions', data: <?= json_encode($mois_data) ?>, fill:false, tension:.2 }] },
    options:{ responsive:true }
});
<?php endif; ?>
</script>
</body>
</html>
