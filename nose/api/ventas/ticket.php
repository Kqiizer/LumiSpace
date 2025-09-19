<?php
// api/ventas/ticket.php

require_once __DIR__ . '/../../config/functions.php';

// 📦 Autoload de Composer o fallback de FPDF
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;
if (!class_exists('FPDF')) {
    $fpdfLocal = __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php';
    if (file_exists($fpdfLocal)) require_once $fpdfLocal;
}
if (!class_exists('FPDF')) {
    die('No se encontró la clase FPDF. Instálala con Composer (setasign/fpdf) o incluye fpdf.php.');
}

// ===== Helpers =====
function latin(string $s): string {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
}
function money($n): string {
    return number_format((float)$n, 2);
}

// ===== Validar ID =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die('Falta ID de venta.');

// ===== Consulta de venta =====
$conn = getDBConnection();
$sql = "SELECT 
            id, fecha, subtotal, descuento_total, iva, total, 
            pago_efectivo, pago_tarjeta, pago_transferencia, 
            metodo_principal, nota 
        FROM ventas WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
if (!$venta) die('Venta no encontrada.');

// ===== Detalles de productos =====
$sqlDet = "SELECT producto_id, nombre, precio, cantidad, descuento_pct, total_linea 
           FROM detalle_ventas WHERE venta_id = ?";
$stmtDet = $conn->prepare($sqlDet);
$stmtDet->bind_param("i", $id);
$stmtDet->execute();
$items = $stmtDet->get_result()->fetch_all(MYSQLI_ASSOC);

// ===== PDF =====
$pdf = new \FPDF('P', 'mm', [80, 200]); // Ticket 80mm
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Logo
$logo = __DIR__ . '/../../images/logo.png';
if (file_exists($logo)) {
    $pdf->Image($logo, 30, 5, 20);
    $pdf->Ln(20);
}

$pdf->Cell(0, 8, latin("LumiSpace POS"), 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 4, latin("Venta #".$venta['id']), 0, 1, 'C');
$pdf->Cell(0, 4, date("d/m/Y H:i", strtotime($venta['fecha'])), 0, 1, 'C');
$pdf->Ln(2);

// Encabezado productos
$pdf->SetFont('Arial','B',9);
$pdf->Cell(40, 5, latin("Producto"), 0, 0);
$pdf->Cell(10, 5, latin("Cant"), 0, 0, 'R');
$pdf->Cell(15, 5, latin("P.Unit"), 0, 0, 'R');
$pdf->Cell(15, 5, latin("Total"), 0, 1, 'R');
$pdf->SetFont('Arial','',9);

// Productos
foreach ($items as $it) {
    $pdf->Cell(40, 5, latin(mb_substr($it['nombre'], 0, 20, 'UTF-8')), 0, 0);
    $pdf->Cell(10, 5, (int)$it['cantidad'], 0, 0, 'R');
    $pdf->Cell(15, 5, money($it['precio']), 0, 0, 'R');
    $pdf->Cell(15, 5, money($it['total_linea']), 0, 1, 'R');
}

$pdf->Ln(2);
$pdf->Cell(70, 0, str_repeat('-', 70), 0, 1);

// Totales
$pdf->SetFont('Arial','',9);
$pdf->Cell(50,5, latin("Subtotal"),0,0,'R');
$pdf->Cell(20,5, money($venta['subtotal']),0,1,'R');

$pdf->Cell(50,5, latin("Descuento"),0,0,'R');
$pdf->Cell(20,5, "-".money($venta['descuento_total']),0,1,'R');

$pdf->Cell(50,5, latin("IVA 16%"),0,0,'R');
$pdf->Cell(20,5, money($venta['iva']),0,1,'R');

$pdf->SetFont('Arial','B',10);
$pdf->Cell(50,6, latin("TOTAL"),0,0,'R');
$pdf->Cell(20,6, money($venta['total']),0,1,'R');

$pdf->SetFont('Arial','',9);
$pdf->Ln(2);

// Pagos
if ($venta['pago_efectivo'] > 0) {
    $pdf->Cell(50,5, latin("Pago Efectivo"),0,0,'R');
    $pdf->Cell(20,5, money($venta['pago_efectivo']),0,1,'R');
}
if ($venta['pago_tarjeta'] > 0) {
    $pdf->Cell(50,5, latin("Pago Tarjeta"),0,0,'R');
    $pdf->Cell(20,5, money($venta['pago_tarjeta']),0,1,'R');
}
if ($venta['pago_transferencia'] > 0) {
    $pdf->Cell(50,5, latin("Pago Transferencia"),0,0,'R');
    $pdf->Cell(20,5, money($venta['pago_transferencia']),0,1,'R');
}

// Nota
if (!empty($venta['nota'])) {
    $pdf->Ln(2);
    $pdf->MultiCell(0, 5, latin("Nota: ".$venta['nota']));
}

$pdf->Ln(4);
$pdf->SetFont('Arial','I',9);
$pdf->Cell(0,5,latin("¡Gracias por su compra!"),0,1,'C');

// Mostrar PDF en navegador
$pdf->Output('I', 'ticket_venta_'.$venta['id'].'.pdf');
