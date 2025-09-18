<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . "/../config/functions.php";

// Catálogo DEMO (cámbialo por SELECT real en producción)
$productos = [
  ['id'=>101,'nombre'=>'LED Bombillo 12W','precio'=>250,'stock'=>35,'categoria'=>['LED','Interiores'],'estado'=>'disponible','img'=>'../images/prod_bombillo_12w.jpg'],
  ['id'=>102,'nombre'=>'Lámpara Colgante Modern','precio'=>1200,'stock'=>12,'categoria'=>['Interiores'],'estado'=>'disponible','img'=>'../images/prod_colgante_modern.jpg'],
  ['id'=>103,'nombre'=>'Foco Jardín LED','precio'=>680,'stock'=>8,'categoria'=>['LED','Exteriores'],'estado'=>'poco','img'=>'../images/prod_foco_jardin.jpg'],
  ['id'=>104,'nombre'=>'Lámpara Techo Redonda','precio'=>890,'stock'=>3,'categoria'=>['Interiores'],'estado'=>'bajo','img'=>'../images/prod_techo_redonda.jpg'],
  ['id'=>105,'nombre'=>'Tira LED RGB 5m','precio'=>420,'stock'=>45,'categoria'=>['LED','Interiores'],'estado'=>'disponible','img'=>'../images/prod_tira_rgb.jpg'],
  ['id'=>106,'nombre'=>'Reflector LED 50W','precio'=>850,'stock'=>15,'categoria'=>['LED','Exteriores'],'estado'=>'poco','img'=>'../images/prod_reflector_50w.jpg'],
  ['id'=>107,'nombre'=>'Lámpara Colgante W81','precio'=>380,'stock'=>28,'categoria'=>['Interiores'],'estado'=>'disponible','img'=>'../images/prod_colgante_w81.jpg']
];

// Métricas demo
$metaDiaria = 60000;
$ventasDia = 46290;
$ventasCantidad = 127;
$promedioVenta = 356;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Punto de Venta</title>
  <link rel="stylesheet" href="../css/pos.css?v=<?php echo time(); ?>">
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
        <div class="stat-top"><span>Meta Diaria</span><strong class="money">$<?= number_format($metaDiaria,0); ?></strong></div>
        <?php $pct=min(100,round(($ventasDia/$metaDiaria)*100)); ?>
        <div class="bar"><span id="barMeta" style="width:<?= $pct; ?>%"></span></div>
        <small id="metaPct"><?= $pct; ?>% cumplido</small>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Promedio x Venta</span><strong class="money">$<?= number_format($promedioVenta,0); ?></strong></div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Ventas hoy</span>
          <div class="pill"><strong class="money" id="ventasHoy">$<?= number_format($ventasDia,0); ?></strong> <em id="ventasCant"><?= $ventasCantidad; ?> ventas</em></div>
        </div>
      </div>
      <div class="stat">
        <div class="stat-top"><span>Carrito</span><strong id="statItems">0 ítems</strong></div>
        <small id="statTotal" class="money">$0</small>
      </div>
    </div>
  </header>

  <!-- ACCESOS RÁPIDOS -->
  <section class="quick">
    <div class="quick-grid">
      <?php foreach(array_slice($productos,0,4) as $p): ?>
        <button class="quick-item" data-id="<?= $p['id']; ?>" data-precio="<?= $p['precio']; ?>">
          <span><?= htmlspecialchars($p['nombre']); ?></span>
          <strong>$<?= number_format($p['precio'],0); ?></strong>
        </button>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- FILTROS Y BUSCADOR -->
  <section class="filters">
    <div class="chips">
      <button class="chip is-active" data-filter="todos">Todos</button>
      <button class="chip" data-filter="LED">LED</button>
      <button class="chip" data-filter="Interiores">Interiores</button>
      <button class="chip" data-filter="Exteriores">Exteriores</button>
    </div>
    <input type="text" id="buscador" placeholder="Buscar productos (F2)">
  </section>

  <main class="content">
    <!-- LISTADO DE PRODUCTOS -->
    <section class="productos" id="productos">
      <?php foreach ($productos as $p): ?>
        <article class="card"
          data-id="<?= $p['id']; ?>"
          data-nombre="<?= htmlspecialchars($p['nombre']); ?>"
          data-precio="<?= $p['precio']; ?>"
          data-categorias='<?= json_encode($p['categoria']); ?>'>
          <div class="thumb" style="background-image:url('<?= htmlspecialchars($p['img']); ?>')">
            <div class="labels">
              <?php foreach ($p['categoria'] as $c): ?>
                <span class="label"><?= htmlspecialchars($c); ?></span>
              <?php endforeach; ?>
            </div>
            <button class="preview">Vista previa</button>
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
              <button class="btn add">Agregar</button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </section>

    <!-- CARRITO -->
    <aside class="carrito">
      <header>
        <h2>Carrito</h2>
      </header>
      <ul id="carritoLista" class="cart-list"></ul>

      <!-- Descuentos y notas -->
      <div class="descuentos">
        <div class="line">
          <label>Descuento global (%):</label>
          <input type="number" id="descGlobalPct" value="0" min="0" max="100">
        </div>
        <div class="line">
          <label>Nota:</label>
          <input type="text" id="ticketNota" placeholder="Ej. entrega a domicilio">
        </div>
      </div>

      <!-- Totales -->
      <div class="totales">
        <div class="line"><span>Subtotal:</span><strong id="cSub">$0</strong></div>
        <div class="line"><span>Descuento:</span><strong id="cDesc">$0</strong></div>
        <div class="line"><span>IVA (16%):</span><strong id="cIva">$0</strong></div>
        <div class="line total"><span>Total:</span><strong id="cTot">$0</strong></div>
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
        <button id="btnLimpiar" class="btn ghost">Limpiar (F4)</button>
        <button id="btnGuardarBorrador" class="btn ghost">Guardar borrador</button>
        <button id="btnCargarBorrador" class="btn ghost">Cargar borrador</button>
        <button id="btnPagar" class="btn primary">Procesar pago (F9)</button>
      </div>

      <!-- Historial -->
      <div class="historial">
        <h3>Últimas ventas</h3>
        <ul id="ventasRecientes"></ul>
      </div>
    </aside>
  </main>
</div>

<script>
  window.CATALOGO_POS = <?= json_encode($productos, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="../js/pos.js?v=<?= time(); ?>"></script>
</body>
</html>
