<?php
require_once __DIR__ . "/../../vendor/autoload.php";
require_once __DIR__ . "/../../config/functions.php";

use FPDF\FPDF;

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    die("Falta ID de venta");
}

// ===================
// 🔹 Obtener datos
// ===================
$conn = getDBConnection();

// Venta
$sql = "SELECT v.id, v.total, v.fecha, v.metodo_pago, u.nombre AS usuario, c.nombre AS cliente
        FROM ventas v
        LEFT JOIN usuarios u ON v.usuario_id = u.id
        LEFT JOIN clientes c ON v.cliente_id = c.id
        WHERE v.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$venta = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Detalles
$sql = "SELECT dv.cantidad, dv.subtotal, p.nombre
        FROM detalle_ventas dv
        JOIN productos p ON dv.producto_id = p.id
        WHERE dv.venta_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$detalles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// ===================
// 🔹 Generar PDF
// ===================
$pdf = new FPDF('P','mm',[80,200]); // Ticket tamaño 80mm
$pdf->AddPage();
$pdf->SetFont('Arial','B',12);
$pdf->Cell(60,10,"TICKET DE VENTA",0,1,'C');

$pdf->SetFont('Arial','',9);
$pdf->Cell(60,5,"Venta ID: ".$venta['id'],0,1,'C');
$pdf->Cell(60,5,"Fecha: ".$venta['fecha'],0,1,'C');
$pdf->Cell(60,5,"Cajero: ".$venta['usuario'],0,1,'C');
$pdf->Cell(60,5,"Cliente: ".($venta['cliente'] ?? 'General'),0,1,'C');
$pdf->Ln(3);

$pdf->SetFont('Arial','B',9);
$pdf->Cell(30,5,"Producto",0,0);
$pdf->Cell(10,5,"Cant",0,0,'C');
$pdf->Cell(20,5,"Subtotal",0,1,'R');
$pdf->SetFont('Arial','',9);

foreach ($detalles as $d) {
    $pdf->Cell(30,5,substr($d['nombre'],0,15),0,0);
    $pdf->Cell(10,5,$d['cantidad'],0,0,'C');
    $pdf->Cell(20,5,"$".number_format($d['subtotal'],2),0,1,'R');
}

$pdf->Ln(2);
$pdf->SetFont('Arial','B',10);
$pdf->Cell(60,6,"TOTAL: $".number_format($venta['total'],2),0,1,'R');
$pdf->SetFont('Arial','',9);
$pdf->Cell(60,5,"Metodo: ".$venta['metodo_pago'],0,1,'C');

$pdf->Ln(5);
$pdf->Cell(60,5,"¡Gracias por su compra!",0,1,'C');

$pdf->Output();
