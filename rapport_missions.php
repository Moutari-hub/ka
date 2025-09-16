<?php
session_start();
require 'config.php';
require 'fpdf/fpdf.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$mission_id = intval($_GET['id'] ?? 0);

// Récupérer la ou les missions
if ($mission_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ?");
    $stmt->execute([$mission_id]);
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Si aucun ID, récupérer toutes les missions terminées ou rejetées
    $today = date('Y-m-d');
    $stmt = $pdo->query("SELECT * FROM missions WHERE statut IN ('Rejetée') OR date_fin < '$today' ORDER BY date_debut DESC");
    $missions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fonction pour afficher les personnels
function getPersonnelsInfo($pdo, $ids_str) {
    if(empty($ids_str)) return '-';
    $ids = array_map('intval', explode(',', $ids_str));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT nom, prenom, poste FROM personnels WHERE id IN ($placeholders) ORDER BY nom");
    $stmt->execute($ids);
    $res = [];
    foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $p){
        $res[] = $p['nom'].' '.$p['prenom'].' ('.$p['poste'].')';
    }
    return implode(', ', $res);
}

// Création PDF
$pdf = new FPDF();
$pdf->SetAutoPageBreak(true, 15);

foreach($missions as $m){
    $pdf->AddPage();

    // Logo
    $pdf->Image('images/logo.jpg',10,10,40); 
    $pdf->SetFont('Arial','B',16);
    $pdf->Cell(0,10,'Rapport Mission',0,1,'C');
    $pdf->Ln(10);

    // Informations principales
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'Titre :',0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->MultiCell(0,8,$m['titre']);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'Zone :',0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,$m['zone_mission'],0,1);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'Dates :',0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,$m['date_debut'].' → '.$m['date_fin'],0,1);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'Montant prévu :',0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,number_format($m['montant_prevu'],0,',',' ').' XOF',0,1);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'Statut :',0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,$m['statut'],0,1);

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'Personnels :',0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->MultiCell(0,8,getPersonnelsInfo($pdo,$m['personnels']));

    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(50,8,'Logistique :',0,0);
    $pdf->SetFont('Arial','',12);
    $pdf->MultiCell(0,8,$m['logistique']);
}

// Sortie PDF
$pdf->Output('I','rapport_missions.pdf');
exit;
?>
