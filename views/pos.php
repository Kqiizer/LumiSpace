<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../config/functions.php";

// ============================
// 📦 Cargar productos reales
// ============================
$conn = getDBConnection();
$sql = "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen,
               c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        ORDER BY p.nombre ASC";
$res = $conn->query($sql);
$productos = [];
while ($row = $res->fetch_assoc()) {
    $productos[] = [
        'id'       => (int)$row['id'],
        'nombre'   => $row['nombre'],
        'precio'   => (float)$row['precio'],
        'stock'    => (int)$row['stock'],
        'categoria'=> $row['categoria'] ?? 'General',
        'estado'   => $row['stock'] > 20 ? 'disponible' : ($row['stock'] > 5 ? 'poco' : 'bajo'),
        // ✅ Ruta absoluta con BASE_URL para que siempre carguen
        'img'      => !empty($row['imagen'])
                        ? (defined('BASE_URL') ? BASE_URL : '/LumiSpace/') . "uploads/productos/" . $row['imagen']
                        : (defined('BASE_URL') ? BASE_URL : '/LumiSpace/') . "images/default.png"
    ];
}

// ============================
// 📊 Métricas globales (para POS + dashboards)
// ============================
$metaDiaria     = 60000;
$ventasHoy      = getVentasHoy();
$resumenHoy     = getResumenHoy();
$ventasCantidad = $resumenHoy['transacciones'] ?? 0;
$promedioVenta  = $ventasCantidad > 0 ? round($ventasHoy / $ventasCantidad, 2) : 0;

// ============================
// 🕒 Últimas ventas
// ============================
$ventasRecientes = getVentasRecientes(5);

// ============================
// 🏆 Top más vendidos (mes)
// ============================
$topProductosMes  = function_exists('getProductosMasVendidosMes') ? getProductosMasVendidosMes(5) : [];
$topCategoriasMes = function_exists('getCategoriasMasVendidasMes') ? getCategoriasMasVendidasMes(5) : [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Punto de Venta</title>
  <link rel="stylesheet" href="../css/pos.css?v=<?= time(); ?>">
</head>
<body>
<div class="pos-wrapper">

  <!-- HEADER -->
  <header class="pos-header">
    <div>
      <h1>Punto de Venta</h1>
      <p id="pos-fecha"></p>
    </div>
    <div class="stats">
      <div class="stat">
        <div class="stat-top"><span>Meta Diaria</span>
          <strong class="money">$<?= number_format($metaDiaria,0); ?></strong>
        </div>
        <?php $pct = min(100, $metaDiaria > 0 ? round(($ventasHoy/$metaDiaria)*100) : 0); ?>
        <div class="bar"><span id="barMeta" style="width:<?= $pct; ?>%"></span></div>
        <small id="metaPct"><?= $pct; ?>% cumplido</small>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Promedio x Venta</span>
          <strong class="money">$<?= number_format($promedioVenta,0); ?></strong>
        </div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Ventas hoy</span>
          <div class="pill">
            <strong class="money" id="ventasHoy">$<?= number_format($ventasHoy,0); ?></strong> 
            <em id="ventasCant"><?= $ventasCantidad; ?> ventas</em>
          </div>
        </div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Carrito</span>
          <strong id="statItems">0 ítems</strong>
        </div>
        <small id="statTotal" class="money">$0</small>
      </div>
    </div>
  </header>

  <!-- (Opcional) Buscador / Filtros si luego los quieres -->
  <!--
  <section class="filters">
    <div class="chips">
      <button class="chip is-active" data-filter="todos">Todos</button>
    </div>
    <input type="text" id="buscador" placeholder="Buscar productos (F2)">
  </section>
  -->

  <!-- LISTADO DE PRODUCTOS -->
  <main class="content">
    <section class="productos" id="productos">
      <?php foreach ($productos as $p): ?>
        <article class="card"
          data-id="<?= $p['id']; ?>"
          data-nombre="<?= htmlspecialchars($p['nombre']); ?>"
          data-precio="<?= $p['precio']; ?>"
          data-categoria="<?= htmlspecialchars($p['categoria']); ?>">
          
          <div class="thumb" style="background-image:url('<?= htmlspecialchars($p['img']); ?>')">
            <div class="labels">
              <span class="label"><?= htmlspecialchars($p['categoria']); ?></span>
            </div>
            <button class="preview" type="button">Vista previa</button>
          </div>
          
          <div class="info">
            <h3><?= htmlspecialchars($p['nombre']); ?></h3>
            <div class="row">
              <span class="price">$<?= number_format($p['precio'],0); ?></span>
              <span class="stock <?= $p['estado']; ?>">
                <?= $p['estado']==='disponible' ? "Disponible" : ($p['estado']==='poco'?"Poco stock":"Stock bajo"); ?>
                (<?= $p['stock']; ?>)
              </span>
            </div>
            <div class="actions">
              <button class="btn add" type="button">Agregar</button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <!-- CARRITO -->
    <aside class="carrito">
      <header><h2>Carrito</h2></header>
      <ul id="carritoLista" class="cart-list"></ul>

      <!-- Totales (si usas subtotal/iva/desc, añade spans con ids cSub/cIva/cDesc) -->
      <div class="totales">
        <div class="line"><span>Total:</span><strong id="cTot">$0</strong></div>
      </div>

      <!-- Pagos -->
      <div class="pagos">
        <h3>Pago</h3>
        <div class="line"><label>Efectivo:</label><input type="number" id="pEfectivo" value="0"></div>
        <div class="line"><label>Tarjeta:</label><input type="number" id="pTarjeta" value="0"></div>
        <div class="line"><label>Transferencia:</label><input type="number" id="pTransf" value="0"></div>
        <div class="line"><span>Cambio:</span><strong id="cCambio">$0</strong></div>
      </div>

      <!-- Acciones -->
      <div class="cart-actions">
        <button id="btnLimpiar" class="btn ghost" type="button">Limpiar (F4)</button>
        <button id="btnPagar" class="btn primary" type="button">Procesar pago (F9)</button>
      </div>

      <!-- Historial -->
      <div class="historial">
        <h3>Últimas ventas</h3>
        <ul id="ventasRecientes">
          <?php foreach ($ventasRecientes as $v): ?>
            <li>#<?= $v['id']; ?> - <?= htmlspecialchars($v['cliente'] ?? 'Desconocido'); ?> 
              <em>(<?= htmlspecialchars($v['productos'] ?? '---'); ?>)</em>
              <strong>$<?= number_format($v['total'],0); ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Top productos -->
      <div class="top-block">
        <h3>🔥 Top Productos (Mes)</h3>
        <ul>
          <?php foreach ($topProductosMes as $p): ?>
            <li><?= htmlspecialchars($p['nombre']); ?> 
              <strong><?= (int)$p['total_vendido']; ?> u.</strong>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <!-- Top categorías -->
      <div class="top-block">
        <h3>🏷️ Top Categorías (Mes)</h3>
        <ul>
          <?php foreach ($topCategoriasMes as $c): ?>
            <li><?= htmlspecialchars($c['categoria']); ?> 
              <strong><?= (int)$c['total_vendido']; ?> u.</strong>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

    </aside>
  </main>
</div>

<!-- ✅ Inyectar datos para pos.js -->
<script>
  window.CATALOGO_POS = <?= json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK); ?>;
  window.USUARIO_ID   = <?= isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0 ?>;
</script>

<!-- ✅ JS del POS (usa el que te pasé, no pongas más JS inline aquí) -->
<script src="../js/pos.js?v=<?= time(); ?>"></script>
</body>
</html>
<?php exit();
?>