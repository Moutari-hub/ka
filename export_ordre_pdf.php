<?php
session_start();
require 'config.php'; // ta connexion PDO ici

// Vérifier si utilisateur connecté (optionnel, tu peux adapter la sécurité)
if (!isset($_SESSION['user_id'], $_SESSION['role_id'])) {
    header('Location: login.php');
    exit;
}

// Récupérer l'id mission en GET
$mission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($mission_id <= 0) {
    die("ID mission invalide.");
}

// Récupérer la mission complète
$stmt = $pdo->prepare("SELECT * FROM missions WHERE id = ?");
$stmt->execute([$mission_id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    die("Mission non trouvée.");
}

// Charger FPDF (modifie le chemin si besoin)
require('fpdf/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();

// Titre
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Ordre de Mission',0,1,'C');
$pdf->Ln(5);

// Informations mission
$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Titre :");
$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,8,utf8_decode($mission['titre']));

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Description :");
$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,8,utf8_decode($mission['description']));

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Type de mission :");
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,utf8_decode($mission['type_mission']),0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Zone :");
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,utf8_decode($mission['zone_mission']),0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Date début :");
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,$mission['date_debut'],0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Date fin :");
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,$mission['date_fin'],0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Montant prévu (XOF) :");
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,8,number_format($mission['montant_prevu'], 2, ',', ' '),0,1);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Logistique :");
$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,8,utf8_decode($mission['logistique'] ?? ''));

$pdf->SetFont('Arial','B',12);
$pdf->Cell(50,8,"Personnels concernés :");
$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,8,utf8_decode($mission['personnels'] ?? ''));

$pdf->Ln(15);

// Zone signature RH
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,8,"Signature RH :",0,1);
$pdf->Cell(0,15,"_______________________________",0,1);

$pdf->Ln(10);

// Zone signature Manager (si besoin)
$pdf->Cell(0,8,"Signature Manager :",0,1);
$pdf->Cell(0,15,"_______________________________",0,1);

$pdf->Output('I', 'ordre_mission_' . $mission_id . '.pdf');
exit;
?>
