<?php
require_once __DIR__ . '/../_db.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // asegúrate de tener fpdf instalado

use Fpdf\Fpdf;

$id = (int)($_GET['id'] ?? 0);
if (!$id) { die("Falta ID de venta"); }

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM ventas WHERE id=?");
$stmt->execute([$id]);
$venta = $stmt->fetch();
if (!$venta) die("Venta no encontrada");

$stmt = $pdo->prepare("SELECT * FROM ventas_detalle WHERE venta_id=?");
$stmt->execute([$id]);
$items = $stmt->fetchAll();

// =============== PDF ===============
$pdf = new FPDF('P','mm',[80,200]); // ancho 80 mm
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);

// Logo opcional
$logo = __DIR__ . '/../../images/logo.png';
if (file_exists($logo)) $pdf->Image($logo, 30, 5, 20);

$pdf->Cell(0, 8, utf8_decode("LumiSpace POS"), 0, 1, 'C');
$pdf->SetFont('Arial','',8);
$pdf->Cell(0, 4, utf8_decode("Venta #".$venta['id']), 0, 1, 'C');
$pdf->Cell(0, 4, date("d/m/Y H:i", strtotime($venta['created_at'])), 0, 1, 'C');
$pdf->Ln(2);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(40, 5, utf8_decode("Producto"), 0, 0);
$pdf->Cell(10, 5, "Cant", 0, 0, 'R');
$pdf->Cell(15, 5, "P.Unit", 0, 0, 'R');
$pdf->Cell(15, 5, "Total", 0, 1, 'R');
$pdf->SetFont('Arial','',9);

foreach ($items as $it) {
    $pdf->Cell(40, 5, utf8_decode(substr($it['nombre'],0,20)), 0, 0);
    $pdf->Cell(10, 5, $it['cantidad'], 0, 0, 'R');
    $pdf->Cell(15, 5, number_format($it['precio'],2), 0, 0, 'R');
    $pdf->Cell(15, 5, number_format($it['total_linea'],2), 0, 1, 'R');
}

$pdf->Ln(2);
$pdf->Cell(70, 0, str_repeat('-',70), 0, 1);

$pdf->SetFont('Arial','',9);
$pdf->Cell(50,5,"Subtotal",0,0,'R');
$pdf->Cell(20,5,number_format($venta['subtotal'],2),0,1,'R');
$pdf->Cell(50,5,"Descuento",0,0,'R');
$pdf->Cell(20,5,"-".number_format($venta['descuento_total'],2),0,1,'R');
$pdf->Cell(50,5,"IVA 16%",0,0,'R');
$pdf->Cell(20,5,number_format($venta['iva'],2),0,1,'R');
$pdf->SetFont('Arial','B',10);
$pdf->Cell(50,6,"TOTAL",0,0,'R');
$pdf->Cell(20,6,number_format($venta['total'],2),0,1,'R');

$pdf->SetFont('Arial','',9);
$pdf->Ln(2);
$pdf->Cell(50,5,"Pago Efectivo",0,0,'R');
$pdf->Cell(20,5,number_format($venta['pago_efectivo'],2),0,1,'R');
$pdf->Cell(50,5,"Pago Tarjeta",0,0,'R');
$pdf->Cell(20,5,number_format($venta['pago_tarjeta'],2),0,1,'R');
$pdf->Cell(50,5,"Pago Transferencia",0,0,'R');
$pdf->Cell(20,5,number_format($venta['pago_transferencia'],2),0,1,'R');

if ($venta['nota']) {
    $pdf->Ln(2);
    $pdf->MultiCell(0,5,"Nota: ".utf8_decode($venta['nota']));
}

$pdf->Ln(4);
$pdf->Cell(0,5,utf8_decode("¡Gracias por su compra!"),0,1,'C');

$pdf->Output();
