<?php
require_once __DIR__ . '/../_db.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // 🚨 Asegúrate de tener instalado fpdf/fpdf

use Fpdf\Fpdf;

function json_input(): array {
    $raw = file_get_contents("php://input");
    return $raw ? json_decode($raw, true) : [];
}
function json_out(array $data, int $status = 200): void {
    http_response_code($status);
    header("Content-Type: application/json; charset=UTF-8");
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $in = json_input();

    $pdo = db();
    $pdo->beginTransaction();

    $usuario_id       = (int)($in['usuario_id'] ?? 0);
    $cart             = $in['cart'] ?? [];
    $desc_global_pct  = (float)($in['desc_global_pct'] ?? 0);
    $pagos            = $in['pagos'] ?? ['efectivo'=>0,'tarjeta'=>0,'transferencia'=>0];
    $nota             = trim($in['nota'] ?? '');

    if (!$cart) json_out(['ok'=>false,'msg'=>'Carrito vacío'], 400);

    // ===== Calcular totales =====
    $subtotal = 0;
    foreach ($cart as $it) {
        $precio  = (float)$it['precio'];
        $qty     = (int)$it['qty'];
        $descPct = (float)($it['descPct'] ?? 0);

        $precioDesc = $precio * (1 - $descPct/100);
        $subtotal  += $precioDesc * $qty;
    }

    $desc_monto = round($subtotal * ($desc_global_pct/100), 2);
    $base       = $subtotal - $desc_monto;
    $iva        = round($base * 0.16, 2);
    $total      = round($base + $iva, 2);

    // ===== Insertar venta =====
    $stmt = $pdo->prepare("
        INSERT INTO ventas 
            (usuario_id, subtotal, descuento_total, iva, total, 
             pago_efectivo, pago_tarjeta, pago_transferencia, 
             metodo_principal, nota, created_at)
        VALUES (?,?,?,?,?,?,?,?,?,?,NOW())
    ");
    $metodo_principal = array_search(max($pagos), $pagos) ?: 'efectivo';
    $stmt->execute([
        $usuario_id, $subtotal, $desc_monto, $iva, $total,
        (float)$pagos['efectivo'], (float)$pagos['tarjeta'], (float)$pagos['transferencia'],
        $metodo_principal, $nota
    ]);
    $venta_id = (int)$pdo->lastInsertId();

    // ===== Insertar detalles + actualizar stock =====
    $stmtDet = $pdo->prepare("
        INSERT INTO detalle_ventas 
            (venta_id, producto_id, nombre, precio, cantidad, descuento_pct, total_linea)
        VALUES (?,?,?,?,?,?,?)
    ");
    $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

    foreach ($cart as $it) {
        $precio  = (float)$it['precio'];
        $qty     = (int)$it['qty'];
        $descPct = (float)($it['descPct'] ?? 0);
        $precioDesc = $precio * (1 - $descPct/100);
        $totalLinea = round($precioDesc * $qty, 2);

        $stmtDet->execute([$venta_id, (int)$it['id'], $it['nombre'], $precio, $qty, $descPct, $totalLinea]);
        $stmtStock->execute([$qty, (int)$it['id']]);
    }

    $pdo->commit();

    // ===== Generar PDF =====
    $pdf = new FPDF('P','mm',[80,200]);
    $pdf->AddPage();
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,"LumiSpace POS",0,1,'C');
    $pdf->SetFont('Arial','',9);
    $pdf->Cell(0,5,"Ticket Venta #".$venta_id,0,1,'C');
    $pdf->Cell(0,5,date("d/m/Y H:i"),0,1,'C');
    $pdf->Ln(2);

    $pdf->SetFont('Arial','B',9);
    $pdf->Cell(40,5,"Producto",0,0);
    $pdf->Cell(10,5,"Cant",0,0,'R');
    $pdf->Cell(15,5,"P.Unit",0,0,'R');
    $pdf->Cell(15,5,"Total",0,1,'R');

    $pdf->SetFont('Arial','',9);
    foreach ($cart as $it) {
        $pdf->Cell(40,5,substr($it['nombre'],0,20),0,0);
        $pdf->Cell(10,5,$it['qty'],0,0,'R');
        $pdf->Cell(15,5,number_format($it['precio'],2),0,0,'R');
        $lineTotal = ($it['precio'] * $it['qty']) * (1 - ($it['descPct'] ?? 0)/100);
        $pdf->Cell(15,5,number_format($lineTotal,2),0,1,'R');
    }

    $pdf->Ln(2);
    $pdf->Cell(70,0,str_repeat('-',70),0,1);

    $pdf->Cell(50,5,"Subtotal",0,0,'R'); $pdf->Cell(20,5,number_format($subtotal,2),0,1,'R');
    $pdf->Cell(50,5,"Desc",0,0,'R');     $pdf->Cell(20,5,"-".number_format($desc_monto,2),0,1,'R');
    $pdf->Cell(50,5,"IVA",0,0,'R');      $pdf->Cell(20,5,number_format($iva,2),0,1,'R');
    $pdf->SetFont('Arial','B',10);
    $pdf->Cell(50,6,"TOTAL",0,0,'R');   $pdf->Cell(20,6,number_format($total,2),0,1,'R');

    if ($nota) {
        $pdf->Ln(2);
        $pdf->MultiCell(0,5,"Nota: ".$nota);
    }

    $pdf->Ln(4);
    $pdf->Cell(0,5,utf8_decode("¡Gracias por su compra!"),0,1,'C');

    // Guardar el archivo
    $ticketPath = __DIR__ . "/../../tickets/ticket_{$venta_id}.pdf";
    if (!is_dir(dirname($ticketPath))) mkdir(dirname($ticketPath),0777,true);
    $pdf->Output("F", $ticketPath);

    // Respuesta al POS con link al ticket
    json_out([
        'ok'=>true,
        'venta_id'=>$venta_id,
        'total'=>$total,
        'ticket'=>"../tickets/ticket_{$venta_id}.pdf"
    ]);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    json_out(['ok'=>false,'msg'=>$e->getMessage()], 500);
}
