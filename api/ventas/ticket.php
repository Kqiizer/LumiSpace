<?php
// api/ventas/ticket.php

// ⛽ Conexión MySQLi de tu proyecto
require_once __DIR__ . '/../../config/functions.php';

// 📦 FPDF (sin namespace). Si usas Composer, intenta autoload; sino, carga fpdf.php manual.
$autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($autoload)) require_once $autoload;
if (!class_exists('FPDF')) {
  // Fallbacks comunes; ajusta la ruta si guardaste fpdf.php en otro lado
  $fpdfLocal = __DIR__ . '/../../vendor/setasign/fpdf/fpdf.php';
  if (file_exists($fpdfLocal)) require_once $fpdfLocal;
}
if (!class_exists('FPDF')) {
  die('No se encontró la clase FPDF. Instálala con Composer (setasign/fpdf) o incluye fpdf.php.');
}

// ===== Helpers =====
function latin(string $s): string {
  // FPDF espera ISO-8859-1; convertimos desde UTF-8
  return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
}
function money($n): string {
  return number_format((float)$n, 2);
}

$IVA_RATE = 0.16;

// ===== Validación de ID =====
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die('Falta ID de venta.');

// ===== Consulta de venta =====
$conn = getDBConnection();
$sql = "SELECT id, fecha, total, metodo_pago, nota, detalles FROM ventas WHERE id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
if (!$venta) die('Venta no encontrada.');

// Items desde JSON
$items = [];
if (!empty($venta['detalles'])) {
  $decoded = json_decode($venta['detalles'], true);
  if (is_array($decoded)) $items = $decoded;
}

// Fecha segura
$fecha = $venta['fecha'] ?? date('Y-m-d H:i:s');

// Recalcular base/iva a partir del total (si no tienes columnas separadas)
$total = (float)($venta['total'] ?? 0);
$base  = $total > 0 ? round($total / (1 + $IVA_RATE), 2) : 0.00;
$iva   = $total - $base;

// ===== PDF =====
$pdf = new \FPDF('P', 'mm', [80, 200]); // Ticket 80mm (alto flexible)
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 12);

// Logo opcional
$logo = __DIR__ . '/../../images/logo.png';
if (file_exists($logo)) {
  $pdf->Image($logo, 30, 5, 20);
  $pdf->Ln(20);
}

$pdf->Cell(0, 8, latin("LumiSpace POS"), 0, 1, 'C');
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 4, latin("Venta #".$venta['id']), 0, 1, 'C');
$pdf->Cell(0, 4, date("d/m/Y H:i", strtotime($fecha)), 0, 1, 'C');
$pdf->Ln(2);

// Encabezado tabla
$pdf->SetFont('Arial','B',9);
$pdf->Cell(40, 5, latin("Producto"), 0, 0);
$pdf->Cell(10, 5, latin("Cant"), 0, 0, 'R');
$pdf->Cell(15, 5, latin("P.Unit"), 0, 0, 'R');
$pdf->Cell(15, 5, latin("Total"), 0, 1, 'R');
$pdf->SetFont('Arial','',9);

// Filas de productos desde JSON (espera: {nombre, precio, qty, descPct?})
foreach ($items as $it) {
  $nombre = isset($it['nombre']) ? (string)$it['nombre'] : 'Producto';
  $qty    = isset($it['qty']) ? (int)$it['qty'] : 1;
  $precio = isset($it['precio']) ? (float)$it['precio'] : 0.0;
  $descPct = isset($it['descPct']) ? (float)$it['descPct'] : 0.0;

  $precioDesc = $precio * (1 - ($descPct / 100));
  $totalLinea = $precioDesc * $qty;

  $pdf->Cell(40, 5, latin(mb_substr($nombre, 0, 20, 'UTF-8')), 0, 0);
  $pdf->Cell(10, 5, $qty, 0, 0, 'R');
  $pdf->Cell(15, 5, money($precioDesc), 0, 0, 'R');
  $pdf->Cell(15, 5, money($totalLinea), 0, 1, 'R');
}

$pdf->Ln(2);
$pdf->Cell(70, 0, str_repeat('-', 70), 0, 1);

// Totales (reconstruidos)
$pdf->SetFont('Arial','',9);
$pdf->Cell(50,5, latin("Subtotal"), 0, 0, 'R');
$pdf->Cell(20,5, money($base), 0, 1, 'R');

$pdf->Cell(50,5, latin("IVA 16%"), 0, 0, 'R');
$pdf->Cell(20,5, money($iva), 0, 1, 'R');

$pdf->SetFont('Arial','B',10);
$pdf->Cell(50,6, latin("TOTAL"), 0, 0, 'R');
$pdf->Cell(20,6, money($total), 0, 1, 'R');

$pdf->SetFont('Arial','',9);
$pdf->Ln(2);

// Método de pago (no hay desglose si no está en BD)
$metodo = $venta['metodo_pago'] ?? 'efectivo';
$pdf->Cell(50,5, latin("Método de pago"), 0, 0, 'R');
$pdf->Cell(20,5, latin(ucfirst($metodo)), 0, 1, 'R');

// Nota
if (!empty($venta['nota'])) {
  $pdf->Ln(2);
  $pdf->MultiCell(0, 5, latin("Nota: ".$venta['nota']));
}

$pdf->Ln(4);
$pdf->SetFont('Arial','I',9);
$pdf->Cell(0,5,latin("¡Gracias por su compra!"),0,1,'C');

// Nombre de archivo útil
$pdf->Output('I', 'ticket_venta_'.$venta['id'].'.pdf');
