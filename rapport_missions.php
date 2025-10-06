<?php
session_start();
require 'config.php';
require 'fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$today = date('Y-m-d');
$mission_id = intval($_GET['id'] ?? 0);

if ($mission_id <= 0) {
    die("Mission non spécifiée.");
}

// Récupération de la mission avec le nom du service
$stmt = $pdo->prepare("
    SELECT m.*, s.nom AS service_nom 
    FROM missions m
    LEFT JOIN services s ON m.service_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$mission_id]);
$missions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$missions) {
    die("Mission introuvable.");
}

// Fonction pour récupérer les personnels sous forme de tableau
function getPersonnelsTable($pdo, $ids_str) {
    if(empty($ids_str)) return [];
    $ids = array_map('intval', explode(',', $ids_str));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT nom, prenom, poste FROM personnels WHERE id IN ($placeholders) ORDER BY nom");
    $stmt->execute($ids);
    $res = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $p){
        $res[] = [$p['nom'].' '.$p['prenom'], $p['poste']];
    }
    return $res;
}

$pdf = new FPDF();
$pdf->SetAutoPageBreak(true,15);
$pdf->SetMargins(15,15,15);

foreach($missions as $m){
    $pdf->AddPage();

    // --- En-tête ---
    if(file_exists('images/logo.jpg')){
        $pdf->Image('images/logo.jpg',15,10,35);
    }
    $pdf->SetFont('Arial','B',18);
    $pdf->Cell(0,10,'Rapport de Mission',0,1,'C');
    $pdf->Ln(5);

    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,6,'Date du rapport : '.date('d/m/Y'),0,1,'R');
    $pdf->Cell(0,6,'Mission ID : '.$m['id'],0,1,'R');
    $pdf->Ln(5);

    // --- Informations principales ---
    $pdf->SetFont('Arial','B',12);
    $pdf->SetDrawColor(0,108,55);
    $pdf->SetFillColor(224,235,222);
    $pdf->Cell(0,8,'Informations principales',1,1,'C',true);

    $pdf->SetFont('Arial','',12);
    $info = [
        'Service' => $m['service_nom'] ?? '-',
        'Titre' => $m['titre'],
        'Zone' => $m['zone_mission'],
        'Dates' => $m['date_debut'].' → '.$m['date_fin'],
        'Montant prévu' => number_format($m['montant_prevu'],0,',',' ').' XOF',
        'Montant utilisé' => number_format($m['montant_utilise'],0,',',' ').' XOF'
    ];

    foreach($info as $label => $val){
        $pdf->Cell(50,8,$label.':',1,0);
        $pdf->Cell(0,8,$val,1,1);
    }

    // --- État de la mission ---
    if($m['statut'] === 'Rejetée'){
        $etat = 'Rejetée';
        $color = [255,0,0]; // rouge
    } else {
        $etat = 'Terminée';
        $color = [0,128,0]; // vert
    }

    $pdf->SetFillColor($color[0],$color[1],$color[2]);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'État :',1,0,'L',true);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,$etat,1,1,'L',true);
    $pdf->SetTextColor(0,0,0);
    $pdf->Ln(5);

    // --- Personnels assignés ---
    $personnels = getPersonnelsTable($pdo,$m['personnels']);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(224,235,222);
    $pdf->Cell(0,8,'Personnels assignés',1,1,'C',true);
    $pdf->SetFont('Arial','',12);

    if(!empty($personnels)){
        $pdf->SetFillColor(245,245,245);
        foreach($personnels as $i => $p){
            $pdf->Cell(120,8,$p[0],1,0,'L',$i%2==0);
            $pdf->Cell(0,8,$p[1],1,1,'L',$i%2==0);
        }
    } else {
        $pdf->Cell(0,8,'Aucun personnel assigné',1,1,'C');
    }
    $pdf->Ln(5);

    // --- Logistique ---
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(224,235,222);
    $pdf->Cell(0,8,'Logistique',1,1,'C',true);
    $pdf->SetFont('Arial','',12);
    $pdf->MultiCell(0,8,$m['logistique'] ?? '-',1);
    $pdf->Ln(5);
}

$pdf->Output('I','rapport_mission.pdf');
exit;
?>
