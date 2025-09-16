<?php
session_start();
require_once 'config.php';
require('fpdf/fpdf.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Mission non spécifiée !");
}

$id = (int)$_GET['id'];

// Récupération mission
$stmt = $pdo->prepare("SELECT m.*, s.nom AS service_nom FROM missions m LEFT JOIN services s ON m.service_id = s.id WHERE m.id = ?");
$stmt->execute([$id]);
$mission = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mission) {
    die("Mission introuvable !");
}

// Calcul durée
$debut = new DateTime($mission['date_debut']);
$fin = new DateTime($mission['date_fin']);
$duree = $debut->diff($fin)->days + 1;

// Préparer le personnel
$personnels = [];
if (!empty($mission['personnels'])) {
    $ids = explode(',', $mission['personnels']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt2 = $pdo->prepare("SELECT nom, poste FROM personnels WHERE id IN ($placeholders)");
    $stmt2->execute($ids);
    $personnels = $stmt2->fetchAll(PDO::FETCH_ASSOC);
}

// Initialisation FPDF
$pdf = new FPDF();
$pdf->AddPage();

// Logo
$pdf->Image('images/logo.jpg',10,10,40);

// Titre
$pdf->SetFont('Arial','B',16);
$pdf->SetXY(10,55);
$pdf->Cell(0,10,utf8_decode('ORDRE DE MISSION'),0,1,'C');

// Numéro de mission
$pdf->SetFont('Arial','',12);
$pdf->Cell(0,6,utf8_decode('Mission N°: '.$mission['id']),0,1,'C');
$pdf->Ln(10);

// Section Mission
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,utf8_decode('Informations sur la mission'),0,1);
$pdf->SetFont('Arial','',12);

$pdf->Cell(50,6,'Titre:',0,0);
$pdf->Cell(0,6,utf8_decode($mission['titre']),0,1);
$pdf->Cell(50,6,'Objet:',0,0);
$pdf->MultiCell(0,6,utf8_decode($mission['description']));
$pdf->Cell(50,6,'Zone:',0,0);
$pdf->Cell(0,6,utf8_decode($mission['zone_mission']),0,1);
$pdf->Cell(50,6,'Service:',0,0);
$pdf->Cell(0,6,utf8_decode($mission['service_nom'] ?? ''),0,1);
$pdf->Cell(50,6,'Date début:',0,0);
$pdf->Cell(0,6,$mission['date_debut'],0,1);
$pdf->Cell(50,6,'Date fin:',0,0);
$pdf->Cell(0,6,$mission['date_fin'],0,1);
$pdf->Cell(50,6,'Durée:',0,0);
$pdf->Cell(0,6,$duree.' jour(s)',0,1);
$pdf->Ln(5);

// Logistique
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,utf8_decode('Logistique & détails'),0,1);
$pdf->SetFont('Arial','',12);
$pdf->MultiCell(0,6,utf8_decode($mission['logistique'] ?? 'Non précisé'));
$pdf->Ln(5);

// Personnel
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,utf8_decode('Personnel concerné'),0,1);
$pdf->SetFont('Arial','',12);
if (count($personnels) > 0) {
    foreach ($personnels as $p) {
        $pdf->Cell(0,6,utf8_decode($p['nom'].' - '.$p['poste']),0,1);
    }
} else {
    $pdf->Cell(0,6,'Non précisé',0,1);
}
$pdf->Ln(15);

// Signatures
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,utf8_decode('Signatures'),0,1);
$pdf->Ln(5);

$sign_names = ['Directeur de Service', 'Ressources Humaines', 'Directeur Général'];
$sign_width = 50;
$x_start = 20;

// Cases de signature (DG en gris)
foreach ($sign_names as $name) {
    $pdf->SetX($x_start);
    if ($name == 'Directeur Général') {
        $pdf->SetFillColor(230, 230, 230); // Gris clair
        $pdf->Cell($sign_width,12,'',1,0,'C',true);
    } else {
        $pdf->Cell($sign_width,12,'',1,0,'C');
    }
    $x_start += $sign_width + 20;
}
$pdf->Ln(15);

// Noms sous cases
$x_start = 20;
foreach ($sign_names as $name) {
    $pdf->SetX($x_start);
    $pdf->Cell($sign_width,6,utf8_decode($name),0,0,'C');
    $x_start += $sign_width + 20;
}

$pdf->Output();
