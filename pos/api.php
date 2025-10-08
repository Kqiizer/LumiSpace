<?php
declare(strict_types=1);
require_once __DIR__.'/utils.php';
header('Content-Type: application/json; charset=UTF-8');

$db = db();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function out($arr){ echo json_encode($arr, JSON_UNESCAPED_UNICODE); exit; }

try {
  switch ($action) {

/* =========================
   PRODUCTOS (listado con filtros para Inventario)
========================= */
case 'productos_list': {
  $q          = trim($_REQUEST['q'] ?? '');
  $estado     = trim($_REQUEST['estado'] ?? ''); // en_stock | bajo | agotado | ''
  $categoria  = trim($_REQUEST['categoria'] ?? '');
  $order      = trim($_REQUEST['order'] ?? 'fecha_creado');
  $dir        = strtoupper(trim($_REQUEST['dir'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
  $umbral     = (int)($_REQUEST['low_threshold'] ?? 5);

  $page     = max(1, (int)($_REQUEST['page'] ?? 1));
  $perPage  = max(1, min(50, (int)($_REQUEST['per_page'] ?? 20)));
  $offset   = ($page - 1) * $perPage;

  $where  = [];
  $types  = '';
  $params = [];

  if ($q !== '') { $where[] = 'nombre LIKE ?'; $types .= 's'; $params[] = "%$q%"; }
  if ($categoria !== '') { $where[] = 'categoria = ?'; $types .= 's'; $params[] = $categoria; }

  if ($estado === 'en_stock') {
    $where[] = 'stock > 0';
  } elseif ($estado === 'agotado') {
    $where[] = 'stock <= 0';
  } elseif ($estado === 'bajo') {
    $where[] = 'stock > 0 AND stock <= ?';
    $types  .= 'i'; $params[] = $umbral;
  }

  $whereSql = $where ? ('WHERE '.implode(' AND ', $where)) : '';

  // total
  $sqlCnt = "SELECT COUNT(*) c FROM productos $whereSql";
  $stmt = $db->prepare($sqlCnt);
  if ($types !== '') $stmt->bind_param($types, ...$params);
  $stmt->execute(); $total = (int)$stmt->get_result()->fetch_assoc()['c'];

  // order seguro
  $allowed = ['fecha_creado','nombre','stock','precio','id'];
  if (!in_array($order, $allowed, true)) $order = 'fecha_creado';

  $sql = "SELECT id, nombre, precio, stock, estado, imagen, categoria
          FROM productos
          $whereSql
          ORDER BY $order $dir
          LIMIT ?, ?";
  $types2  = $types.'ii';
  $params2 = array_merge($params, [$offset, $perPage]);

  $stmt = $db->prepare($sql);
  $stmt->bind_param($types2, ...$params2);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  out(['ok'=>true, 'data'=>$rows, 'total'=>$total, 'page'=>$page, 'per_page'=>$perPage]);
  break;
}

/* =========================
   PRODUCTO: update rápido (nombre/precio)
   Input: id, [nombre], [precio]
========================= */
case 'productos_update_quick': {
  $id = (int)($_POST['id'] ?? 0);
  if (!$id) out(['ok'=>false,'error'=>'id requerido']);

  $nombre = array_key_exists('nombre', $_POST) ? trim((string)$_POST['nombre']) : null;
  $precio = array_key_exists('precio', $_POST) ? (float)$_POST['precio'] : null;

  if ($nombre === null && $precio === null) out(['ok'=>false,'error'=>'Sin cambios']);

  $sets=[]; $types=''; $params=[];
  if ($nombre !== null) { $sets[]='nombre=?'; $types.='s'; $params[]=$nombre; }
  if ($precio !== null) { $sets[]='precio=?'; $types.='d'; $params[]=$precio; }
  $params[] = $id; $types .= 'i';

  $stmt = $db->prepare("UPDATE productos SET ".implode(',', $sets)." WHERE id=? LIMIT 1");
  $stmt->bind_param($types, ...$params);
  $stmt->execute();

  out(['ok'=>true]);
  break;
}

/* =========================
   LISTA DE CAJEROS (para el popup)
========================= */
case 'cajeros_list': {
  // Detectar columna del nombre en usuarios (nombre|name|usuario|email)
  $res = $db->query("SHOW COLUMNS FROM usuarios");
  $nameCol = 'nombre';
  if ($res) {
    $cols = [];
    while ($c = $res->fetch_assoc()) $cols[$c['Field']] = true;
    foreach (['nombre','name','usuario','email'] as $try) {
      if (isset($cols[$try])) { $nameCol = $try; break; }
    }
  }
  // Traer todos los usuarios (si tienes un campo 'estado' o 'rol', aquí puedes filtrar)
  $rows = [];
  $sql  = "SELECT id, $nameCol AS nombre FROM usuarios ORDER BY id ASC";
  if ($q = $db->query($sql)) while ($r = $q->fetch_assoc()) $rows[] = $r;

  out(['ok'=>true, 'data'=>$rows]);
  break;
}

/* =========================
   ÚLTIMO TURNO POR CAJA (prefill saldo inicial con el último saldo_final)
========================= */
case 'turno_last_by_caja': {
  $caja = $_POST['caja_id'] ?? $_GET['caja_id'] ?? 'Caja 1';
  $stmt = $db->prepare("SELECT saldo_final FROM turnos_caja WHERE caja_id=? AND saldo_final IS NOT NULL ORDER BY id DESC LIMIT 1");
  $stmt->bind_param('s', $caja);
  $stmt->execute();
  $r = $stmt->get_result()->fetch_assoc();
  out(['ok'=>true, 'saldo_final'=> (float)($r['saldo_final'] ?? 0)]);
  break;
}

/* =========================
   TURNOS
========================= */

/* Abrir turno */
case 'turno_open': {
  $caja_id       = trim($_POST['caja_id'] ?? '');
  $cajero_id     = (int)($_POST['cajero_id'] ?? 0);
  $saldo_inicial = (float)($_POST['saldo_inicial'] ?? 0);
  if ($caja_id==='') out(['ok'=>false,'error'=>'caja_id requerido']);
  if ($cajero_id<=0)  out(['ok'=>false,'error'=>'Selecciona el cajero']);

  // Verificar que el cajero exista
  $stmt = $db->prepare("SELECT id FROM usuarios WHERE id=? LIMIT 1");
  $stmt->bind_param('i',$cajero_id);
  $stmt->execute();
  if (!$stmt->get_result()->fetch_assoc()) out(['ok'=>false,'error'=>'Cajero no existe']);

  // Solo un turno abierto por caja
  $stmt = $db->prepare("SELECT id FROM turnos_caja WHERE caja_id=? AND fecha_cierre IS NULL LIMIT 1");
  $stmt->bind_param('s',$caja_id);
  $stmt->execute();
  if ($stmt->get_result()->fetch_assoc()) out(['ok'=>false,'error'=>'Ya existe un turno abierto para esta caja.']);

  $stmt = $db->prepare("INSERT INTO turnos_caja (cajero_id, saldo_inicial, caja_id) VALUES (?,?,?)");
  $stmt->bind_param('ids', $cajero_id, $saldo_inicial, $caja_id);
  $stmt->execute();
  out(['ok'=>true, 'turno_id'=>$stmt->insert_id]);
  break;
}


/* Cerrar turno */
case 'turno_close': {
  $turno_id    = (int)($_POST['turno_id'] ?? 0);
  $saldo_final = (float)($_POST['saldo_final'] ?? 0);

  if ($turno_id<=0) out(['ok'=>false,'error'=>'turno_id requerido']);
  $stmt = $db->prepare("UPDATE turnos_caja SET saldo_final=?, fecha_cierre=NOW() WHERE id=? AND fecha_cierre IS NULL LIMIT 1");
  $stmt->bind_param('di', $saldo_final, $turno_id);
  $stmt->execute();

  if ($stmt->affected_rows < 1) out(['ok'=>false,'error'=>'No se pudo cerrar (¿ya estaba cerrado?)']);
  out(['ok'=>true]);
  break;
}
/* Turno actual por caja
   Input: caja_id (string) */
case 'turno_actual': {
  $caja = $_POST['caja_id'] ?? $_GET['caja_id'] ?? ($_COOKIE['pos_caja'] ?? 'Caja 1');
  out(['ok'=>true, 'turno'=> turno_actual($db, $caja)]);
  break;
}


/* ========= TURNOS ACTIVOS (todas las cajas abiertas ahora) ========= */
case 'turnos_activos': {
  // Nombre del cajero robusto (depende de tu tabla usuarios)
  $res = $db->query("SHOW COLUMNS FROM usuarios");
  $nameCol = 'nombre';
  if ($res) {
    $cols=[]; while($c=$res->fetch_assoc()) $cols[$c['Field']]=true;
    foreach(['nombre','name','usuario','email'] as $try){ if(isset($cols[$try])){$nameCol=$try;break;} }
  }

  $sql = "SELECT t.id, t.caja_id, t.cajero_id, u.$nameCol AS cajero_nombre, t.fecha_apertura
          FROM turnos_caja t
          LEFT JOIN usuarios u ON u.id=t.cajero_id
          WHERE t.fecha_cierre IS NULL
          ORDER BY t.caja_id ASC, t.id DESC";
  $rows=[]; if ($q=$db->query($sql)) while($r=$q->fetch_assoc()) $rows[]=$r;
  out(['ok'=>true,'data'=>$rows]);
  break;
}

case 'turnos_historial': {
  $caja   = trim($_REQUEST['caja_id'] ?? '');
  $cajero = (int)($_REQUEST['cajero_id'] ?? 0);
  $desde  = $_REQUEST['desde'] ? ($_REQUEST['desde'].' 00:00:00') : '1970-01-01 00:00:00';
  $hasta  = $_REQUEST['hasta'] ? ($_REQUEST['hasta'].' 23:59:59') : '2999-12-31 23:59:59';

  $where = "t.fecha_apertura BETWEEN ? AND ?";
  $types = 'ss'; $params = [$desde,$hasta];
  if ($caja !== '') { $where .= " AND t.caja_id=?"; $types.='s'; $params[]=$caja; }
  if ($cajero>0)    { $where .= " AND t.cajero_id=?"; $types.='i'; $params[]=$cajero; }

  $sql = "SELECT t.*,
                 u.nombre AS cajero_nombre
          FROM turnos_caja t
          LEFT JOIN usuarios u ON u.id=t.cajero_id
          WHERE $where
          ORDER BY t.id DESC
          LIMIT 200";
  $stmt = $db->prepare($sql);
  $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  out(['ok'=>true,'data'=>$rows]);
  break;
}

/* =========================
   VENTA (crear desde POS)
========================= */
/* Input esperado:
   - caja_id      (string)
   - cajero_id    (int)   → se guarda en ventas.gestor_id
   - metodo       (efectivo|tarjeta)
   - items        (JSON)  → [{producto_id, cantidad}]
*/
case 'venta_crear': {
  $caja_id   = trim($_POST['caja_id'] ?? 'Caja 1');
  $cajero_id = (int)($_POST['cajero_id'] ?? 0);
  $metodo    = $_POST['metodo'] ?? 'efectivo';
  $itemsJson = $_POST['items'] ?? '[]';
  $items     = json_decode($itemsJson, true) ?: [];

  if (!$items) out(['ok'=>false,'error'=>'Carrito vacío']);

  // 1) Traer precios + calcular totales
  $ids = array_map(fn($x)=>(int)$x['producto_id'], $items);
  $in  = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids));
  $precios = []; $nombres = [];

  $sql = "SELECT id, nombre, precio, stock FROM productos WHERE id IN ($in)";
  $stmt = $db->prepare($sql);
  $stmt->bind_param($types, ...$ids);
  $stmt->execute();
  $res = $stmt->get_result();
  while($r=$res->fetch_assoc()){ $precios[(int)$r['id']] = (float)$r['precio']; $nombres[(int)$r['id']]=$r['nombre']; }

  $subtotal = 0.0;
  foreach($items as $it){
    $pid=(int)$it['producto_id']; $cant=(int)$it['cantidad'];
    $precio = (float)($precios[$pid] ?? 0);
    $subtotal += $precio * $cant;
  }
  $iva   = round($subtotal * 0.16, 2);
  $total = round($subtotal + $iva, 2);

  // 2) Insert venta
  $pago_ef  = $metodo==='efectivo' ? $total : 0.0;
  $pago_tj  = $metodo==='tarjeta'  ? $total : 0.0;

  $stmt = $db->prepare("
    INSERT INTO ventas (gestor_id, subtotal, descuento_total, iva, total, pago_efectivo, pago_tarjeta, pago_transferencia, metodo_principal)
    VALUES (?,?,0,?,?,?, ?, 0, ?)");
  $stmt->bind_param('iddddds', $cajero_id, $subtotal, $iva, $total, $pago_ef, $pago_tj, $metodo);
  $stmt->execute();
  $venta_id = $stmt->insert_id;

  // 3) Detalle + stock + movimiento inventario
  foreach($items as $it){
    $pid=(int)$it['producto_id']; $cant=(int)$it['cantidad'];
    $precio = (float)($precios[$pid] ?? 0);
    $nombre = (string)($nombres[$pid] ?? '');
    $total_linea = round($precio*$cant, 2);

    // detalle
    $stmt = $db->prepare("INSERT INTO detalle_ventas (venta_id, producto_id, nombre, precio, cantidad, descuento_pct, total_linea) VALUES (?,?,?,?,?,0,?)");
    $stmt->bind_param('iisdid', $venta_id, $pid, $nombre, $precio, $cant, $total_linea);
    $stmt->execute();

    // stock
    $stmt = $db->prepare("UPDATE productos SET stock = stock - ? WHERE id=?");
    $stmt->bind_param('ii', $cant, $pid);
    $stmt->execute();

    // movimiento inventario (salida)
    $stmt = $db->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, motivo) VALUES (?,?, 'salida', ?, ?)");
    $motivo = "Venta #$venta_id ($caja_id)";
    $stmt->bind_param('iiis', $pid, $cajero_id, $cant, $motivo);
    $stmt->execute();
  }

  // 4) (Opcional) pago en tabla pagos (no estrictamente necesario)
  $stmt = $db->prepare("INSERT INTO pagos (venta_id, metodo, monto) VALUES (?, ?, ?)");
  $stmt->bind_param('isd', $venta_id, $metodo, $total);
  $stmt->execute();

  out(['ok'=>true,'venta_id'=>$venta_id,'subtotal'=>$subtotal,'iva'=>$iva,'total'=>$total]);
  break;
}

/* =========================
   VENTAS (Facturación)
========================= */
/* Listado con filtros y KPIs
   Input: desde (Y-m-d), hasta (Y-m-d), metodo (str), caja (str), cajero (int), page, per_page
   Nota caja: inferimos caja por rango de turno donde cayó la venta.
*/
case 'ventas_list': {
  $desde   = $_REQUEST['desde'] ? ($_REQUEST['desde'].' 00:00:00') : '1970-01-01 00:00:00';
  $hasta   = $_REQUEST['hasta'] ? ($_REQUEST['hasta'].' 23:59:59') : '2999-12-31 23:59:59';
  $metodo  = $_REQUEST['metodo'] ?? '';
  $caja    = trim($_REQUEST['caja'] ?? '');
  $cajero  = (int)($_REQUEST['cajero'] ?? 0);
  $page    = max(1, (int)($_REQUEST['page'] ?? 1));
  $perPage = 20; $offset = ($page-1)*$perPage;

  // KPIs
  $stmt = $db->prepare("SELECT COALESCE(SUM(pago_efectivo),0) ef, COALESCE(SUM(pago_tarjeta),0) tj, COALESCE(SUM(total),0) tt, COUNT(*) c
                        FROM ventas WHERE fecha BETWEEN ? AND ?");
  $stmt->bind_param('ss',$desde,$hasta);
  $stmt->execute(); $k = $stmt->get_result()->fetch_assoc();
  $kpis = ['efectivo'=>(float)$k['ef'], 'tarjeta'=>(float)$k['tj'], 'ecommerce'=>0.0, 'ventas'=>(int)$k['c']];

  // build filtros
  $where = "v.fecha BETWEEN ? AND ?";
  $types = 'ss'; $params = [$desde,$hasta];

  if ($metodo!==''){ $where.=" AND v.metodo_principal=?"; $types.='s'; $params[]=$metodo; }
  if ($cajero>0){ $where.=" AND v.gestor_id=?"; $types.='i'; $params[]=$cajero; }

  // Filtro por caja (usando ventana de turnos)
  $joinCaja = "";
  if ($caja!==''){
    $joinCaja = "LEFT JOIN turnos_caja t ON v.fecha BETWEEN t.fecha_apertura AND COALESCE(t.fecha_cierre, NOW())";
    $where   .= " AND t.caja_id=?";
    $types   .= 's'; $params[]=$caja;
  }

  // total filas
  $sqlCnt = "SELECT COUNT(*) c FROM ventas v $joinCaja WHERE $where";
  $stmt = $db->prepare($sqlCnt);
  $stmt->bind_param($types, ...$params);
  $stmt->execute(); $total = (int)$stmt->get_result()->fetch_assoc()['c'];

  // rows
  $sql = "SELECT v.id, v.fecha, v.metodo_principal, v.total, v.gestor_id,
                 u.nombre AS cajero,
                 ".($caja!=='' ? "t.caja_id" : "NULL AS caja_id")."
          FROM ventas v
          LEFT JOIN usuarios u ON u.id=v.gestor_id
          $joinCaja
          WHERE $where
          ORDER BY v.fecha DESC
          LIMIT ?, ?";
  $types2 = $types.'ii'; $params2 = array_merge($params, [$offset, $perPage]);

  $stmt = $db->prepare($sql);
  $stmt->bind_param($types2, ...$params2);
  $stmt->execute(); $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  out(['ok'=>true,'kpis'=>$kpis,'data'=>$rows,'total'=>$total,'page'=>$page,'per_page'=>$perPage]);
  break;
}

/* =========================
   DETALLE DE VENTA
   Input: venta_id (int)
========================= */
case 'venta_detalle': {
  $venta_id = (int)($_POST['venta_id'] ?? 0);
  if ($venta_id<=0) out(['ok'=>false,'error'=>'venta_id requerido']);

  // Venta + cajero
  $sqlV = "SELECT v.*, u.nombre AS cajero
           FROM ventas v
           LEFT JOIN usuarios u ON u.id=v.gestor_id
           WHERE v.id=? LIMIT 1";
  $stmt = $db->prepare($sqlV);
  $stmt->bind_param('i', $venta_id);
  $stmt->execute();
  $venta = $stmt->get_result()->fetch_assoc();
  if (!$venta) out(['ok'=>false,'error'=>'Venta no encontrada']);

  // Inferir caja (si cae dentro de un turno)
  $sqlCaja = "SELECT t.caja_id
              FROM turnos_caja t
              WHERE ? BETWEEN t.fecha_apertura AND COALESCE(t.fecha_cierre, NOW())
              ORDER BY t.id DESC LIMIT 1";
  $stmt = $db->prepare($sqlCaja);
  $stmt->bind_param('s', $venta['fecha']);
  $stmt->execute();
  $rCaja = $stmt->get_result()->fetch_assoc();
  $venta['caja_id'] = $rCaja ? $rCaja['caja_id'] : null;

  // Items
  $items = [];
  $sqlD = "SELECT producto_id, nombre, precio, cantidad, descuento_pct, total_linea
           FROM detalle_ventas WHERE venta_id=?";
  $stmt = $db->prepare($sqlD);
  $stmt->bind_param('i', $venta_id);
  $stmt->execute();
  $res = $stmt->get_result();
  while($row=$res->fetch_assoc()) $items[] = $row;

  out(['ok'=>true,'venta'=>$venta,'items'=>$items]);
  break;
}

/* =========================
   CORTE DE CAJA (resumen)
========================= */
/* Input: caja_id (string) */
case 'corte_resumen': {
  $caja = $_POST['caja_id'] ?? 'Caja 1';
  // turno abierto o último
  $stmt = $db->prepare("SELECT * FROM turnos_caja WHERE caja_id=? AND fecha_cierre IS NULL ORDER BY id DESC LIMIT 1");
  $stmt->bind_param('s',$caja); $stmt->execute(); $turno = $stmt->get_result()->fetch_assoc();
  if (!$turno) {
    $stmt = $db->prepare("SELECT * FROM turnos_caja WHERE caja_id=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s',$caja); $stmt->execute(); $turno = $stmt->get_result()->fetch_assoc();
  }
  if (!$turno) out(['ok'=>true,'data'=>null]);

  $ini = $turno['fecha_apertura']; $fin = $turno['fecha_cierre'] ?: date('Y-m-d H:i:s');

  $stmt = $db->prepare("
     SELECT COALESCE(SUM(pago_efectivo),0) ef,
            COALESCE(SUM(pago_tarjeta),0)  tj,
            COALESCE(SUM(total),0)        tt,
            COUNT(*) c
     FROM ventas
     WHERE fecha BETWEEN ? AND ?");
  $stmt->bind_param('ss',$ini,$fin);
  $stmt->execute(); $s = $stmt->get_result()->fetch_assoc();

  $saldo_inicial = (float)$turno['saldo_inicial'];
  $saldo_actual  = $saldo_inicial + (float)$s['ef'];

  $stmt = $db->prepare("
     SELECT fecha, metodo_principal AS metodo, total AS monto
     FROM ventas
     WHERE fecha BETWEEN ? AND ?
     ORDER BY fecha DESC LIMIT 50");
  $stmt->bind_param('ss',$ini,$fin);
  $stmt->execute(); $movs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

  out(['ok'=>true,'data'=>[
    'turno_id'=>(int)$turno['id'],
    'caja_id'=>$caja,
    'inicio'=>$ini,'fin'=>$fin,
    'saldo_inicial'=>$saldo_inicial,
    'ventas_efectivo'=>(float)$s['ef'],
    'ventas_tarjeta' =>(float)$s['tj'],
    'ventas_total'   =>(float)$s['tt'],
    'ventas_count'   =>(int)$s['c'],
    'saldo_actual'   =>$saldo_actual,
    'movimientos'    =>$movs
  ]]);
  break;
}

/* =========================
   ESTADÍSTICAS
========================= */

/* Ventas de HOY: total e ítems */
case 'stats_hoy': {
  $ini = date('Y-m-d 00:00:00');
  $fin = date('Y-m-d 23:59:59');

  $stmt = $db->prepare("SELECT COALESCE(SUM(total),0) total, COUNT(*) c FROM ventas WHERE fecha BETWEEN ? AND ?");
  $stmt->bind_param('ss',$ini,$fin);
  $stmt->execute(); $r = $stmt->get_result()->fetch_assoc();

  out(['ok'=>true,'total'=>(float)$r['total'],'ventas'=>(int)$r['c']]);
  break;
}

/* Ventas de la SEMANA actual (lun-dom): total e ítems */
case 'stats_semana': {
  // lunes de esta semana
  $mondayTs = strtotime('monday this week');
  $sundayTs = strtotime('sunday this week 23:59:59');
  $ini = date('Y-m-d 00:00:00', $mondayTs);
  $fin = date('Y-m-d H:i:s', $sundayTs);

  $stmt=$db->prepare("SELECT COALESCE(SUM(total),0) total, COUNT(*) c FROM ventas WHERE fecha BETWEEN ? AND ?");
  $stmt->bind_param('ss',$ini,$fin);
  $stmt->execute(); $r=$stmt->get_result()->fetch_assoc();

  out(['ok'=>true,'total'=>(float)$r['total'],'ventas'=>(int)$r['c'],'desde'=>$ini,'hasta'=>$fin]);
  break;
}

/* Producto más vendido (por cantidad). Ventana opcional: desde/hasta */
case 'stats_producto_top': {
  $desde = $_REQUEST['desde'] ? ($_REQUEST['desde'].' 00:00:00') : '1970-01-01 00:00:00';
  $hasta = $_REQUEST['hasta'] ? ($_REQUEST['hasta'].' 23:59:59') : '2999-12-31 23:59:59';

  $sql = "SELECT d.producto_id,
                 COALESCE(p.nombre, d.nombre) AS nombre,
                 COALESCE(SUM(d.cantidad),0) AS cant,
                 COALESCE(SUM(d.total_linea),0) AS importe
          FROM detalle_ventas d
          JOIN ventas v ON v.id = d.venta_id
          LEFT JOIN productos p ON p.id = d.producto_id
          WHERE v.fecha BETWEEN ? AND ?
          GROUP BY d.producto_id, nombre
          ORDER BY cant DESC
          LIMIT 1";
  $stmt=$db->prepare($sql);
  $stmt->bind_param('ss',$desde,$hasta);
  $stmt->execute(); $row = $stmt->get_result()->fetch_assoc();

  out(['ok'=>true,'data'=>$row ?: null]);
  break;
}

/* Cajero más productivo (por #ventas) */
case 'stats_cajero_top': {
  $desde = $_REQUEST['desde'] ? ($_REQUEST['desde'].' 00:00:00') : '1970-01-01 00:00:00';
  $hasta = $_REQUEST['hasta'] ? ($_REQUEST['hasta'].' 23:59:59') : '2999-12-31 23:59:59';

  // Detectar columna nombre en usuarios
  $res = $db->query("SHOW COLUMNS FROM usuarios");
  $nameCol='nombre'; if ($res){ $cols=[]; while($c=$res->fetch_assoc()) $cols[$c['Field']]=true;
    foreach(['nombre','name','usuario','email'] as $try){ if(isset($cols[$try])){$nameCol=$try;break;} } }

  $sql = "SELECT v.gestor_id AS id, u.$nameCol AS nombre, COUNT(*) ventas
          FROM ventas v
          LEFT JOIN usuarios u ON u.id=v.gestor_id
          WHERE v.fecha BETWEEN ? AND ?
          GROUP BY v.gestor_id, u.$nameCol
          HAVING v.gestor_id IS NOT NULL
          ORDER BY ventas DESC
          LIMIT 1";
  $stmt=$db->prepare($sql);
  $stmt->bind_param('ss',$desde,$hasta);
  $stmt->execute(); $row=$stmt->get_result()->fetch_assoc();

  out(['ok'=>true,'data'=>$row ?: null]);
  break;
}

/* Serie ventas por día (últimos N días; default 7) */
case 'stats_ventas_por_dia': {
  $dias = max(1, min(60, (int)($_REQUEST['dias'] ?? 7)));
  $ini = date('Y-m-d 00:00:00', strtotime("-".($dias-1)." days"));
  $fin = date('Y-m-d 23:59:59');

  // Traer totales por día
  $stmt=$db->prepare("
    SELECT DATE(fecha) d, COALESCE(SUM(total),0) t
    FROM ventas
    WHERE fecha BETWEEN ? AND ?
    GROUP BY DATE(fecha)
    ORDER BY d ASC
  ");
  $stmt->bind_param('ss',$ini,$fin);
  $stmt->execute();
  $map = []; $res=$stmt->get_result();
  while($r=$res->fetch_assoc()) $map[$r['d']] = (float)$r['t'];

  // Construir serie completa con días faltantes = 0
  $labels=[]; $data=[];
  for($i=$dias-1;$i>=0;$i--){
    $day = date('Y-m-d', strtotime("-$i days"));
    $labels[] = $day;
    $data[] = isset($map[$day]) ? $map[$day] : 0.0;
  }
  out(['ok'=>true,'labels'=>$labels,'data'=>$data]);
  break;
}

/* Ventas por categoría (suma de importes). Si no hay categoría -> 'Sin categoría' */
case 'stats_ventas_por_categoria': {
  $desde = $_REQUEST['desde'] ? ($_REQUEST['desde'].' 00:00:00') : '1970-01-01 00:00:00';
  $hasta = $_REQUEST['hasta'] ? ($_REQUEST['hasta'].' 23:59:59') : '2999-12-31 23:59:59';

  $sql="SELECT COALESCE(NULLIF(p.categoria,''),'Sin categoría') AS categoria,
               COALESCE(SUM(d.total_linea),0) AS total
        FROM detalle_ventas d
        JOIN ventas v ON v.id=d.venta_id
        LEFT JOIN productos p ON p.id=d.producto_id
        WHERE v.fecha BETWEEN ? AND ?
        GROUP BY categoria
        ORDER BY total DESC";
  $stmt=$db->prepare($sql);
  $stmt->bind_param('ss',$desde,$hasta);
  $stmt->execute();
  $labels=[];$data=[];
  $res=$stmt->get_result(); while($r=$res->fetch_assoc()){ $labels[]=$r['categoria']; $data[]=(float)$r['total']; }

  out(['ok'=>true,'labels'=>$labels,'data'=>$data]);
  break;
}

/* =========================
   INVENTARIO (ajuste simple)
========================= */
/* Input: producto_id (int), cantidad (int, +entrada / -salida), motivo (string), usuario_id (int cajero) */
case 'inventario_ajuste': {
  $pid  = (int)($_POST['producto_id'] ?? 0);
  $cant = (int)($_POST['cantidad'] ?? 0);
  $motivo = trim($_POST['motivo'] ?? 'Ajuste');
  $uid  = (int)($_POST['usuario_id'] ?? 0);
  if (!$pid || !$cant) out(['ok'=>false,'error'=>'Datos inválidos']);

  $stmt = $db->prepare("UPDATE productos SET stock = stock + ? WHERE id=?");
  $stmt->bind_param('ii',$cant,$pid);
  $stmt->execute();

  $tipo = $cant>=0 ? 'entrada' : 'salida';
  $cantAbs = abs($cant);
  $stmt = $db->prepare("INSERT INTO movimientos_inventario (producto_id, usuario_id, tipo, cantidad, motivo) VALUES (?,?,?, ?,?)");
  $stmt->bind_param('iisis',$pid,$uid,$tipo,$cantAbs,$motivo);
  $stmt->execute();

  out(['ok'=>true]);
  break;
}
    default:
      out(['ok'=>false, 'error'=>'Acción no definida']);
  }
} catch(Throwable $e) {
  out(['ok'=>false,'error'=>$e->getMessage()]);
}

