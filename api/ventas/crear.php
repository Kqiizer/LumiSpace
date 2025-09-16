<?php
require_once __DIR__ . '/../_db.php';
$in = json_input();

try{
  $pdo = db();
  $pdo->beginTransaction();

  $usuario_id = (int)($in['usuario_id'] ?? 0);
  $cart = $in['cart'] ?? [];
  $desc_global_pct = (float)($in['desc_global_pct'] ?? 0);
  $pagos = $in['pagos'] ?? ['efectivo'=>0,'tarjeta'=>0,'transferencia'=>0];
  $nota = trim($in['nota'] ?? '');

  if (!$cart) json_out(['ok'=>false,'msg'=>'Carrito vacío'], 400);

  // Calcular totales
  $subtotal = 0;
  foreach ($cart as $it){
    $precio = (float)$it['precio'];
    $qty = (int)$it['qty'];
    $descPct = (float)($it['descPct'] ?? 0);
    $precioDesc = $precio * (1 - $descPct/100);
    $subtotal += $precioDesc * $qty;
  }
  $desc_monto = $subtotal * ($desc_global_pct/100);
  $base = $subtotal - $desc_monto;
  $iva = round($base * 0.16, 2);
  $total = round($base + $iva, 2);

  // Insert venta
  $stmt = $pdo->prepare("INSERT INTO ventas (usuario_id, subtotal, descuento_total, iva, total, pago_efectivo, pago_tarjeta, pago_transferencia, metodo_principal, nota, created_at)
                         VALUES (?,?,?,?,?,?,?,?,?,?,NOW())");
  $metodo_principal = array_search(max($pagos), $pagos) ?: 'efectivo';
  $stmt->execute([
    $usuario_id, $subtotal, $desc_monto, $iva, $total,
    (float)$pagos['efectivo'], (float)$pagos['tarjeta'], (float)$pagos['transferencia'],
    $metodo_principal, $nota
  ]);
  $venta_id = (int)$pdo->lastInsertId();

  // Insert detalles y mover inventario
  $stmtDet = $pdo->prepare("INSERT INTO ventas_detalle (venta_id, producto_id, nombre, precio, cantidad, descuento_pct, total_linea)
                            VALUES (?,?,?,?,?,?,?)");
  $stmtStock = $pdo->prepare("UPDATE productos SET stock = stock - ? WHERE id = ?");

  foreach ($cart as $it){
    $precio = (float)$it['precio'];
    $qty = (int)$it['qty'];
    $descPct = (float)($it['descPct'] ?? 0);
    $precioDesc = $precio * (1 - $descPct/100);
    $totalLinea = round($precioDesc * $qty, 2);

    $stmtDet->execute([$venta_id, (int)$it['id'], $it['nombre'], $precio, $qty, $descPct, $totalLinea]);
    $stmtStock->execute([$qty, (int)$it['id']]);
  }

  $pdo->commit();
  json_out(['ok'=>true,'venta_id'=>$venta_id,'total'=>$total]);
}catch(Throwable $e){
  if(isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  json_out(['ok'=>false,'msg'=>$e->getMessage()], 500);
}
