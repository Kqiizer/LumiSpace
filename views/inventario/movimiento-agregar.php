<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo admin o gestor
if (!isset($_SESSION['usuario_id']) || !in_array($_SESSION['usuario_rol'], ['admin','gestor'])) {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

$error = null;

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $producto_id = (int)($_POST['producto_id'] ?? 0);
    $tipo        = $_POST['tipo'] ?? '';
    $cantidad    = (int)($_POST['cantidad'] ?? 0);
    $motivo      = trim($_POST['motivo'] ?? '');
    $sucursal    = trim($_POST['sucursal'] ?? 'Principal');
    $uid         = $_SESSION['usuario_id'];

    $tiposValidos = ['entrada','salida','ajuste'];

    if ($producto_id > 0 && $cantidad > 0 && in_array($tipo, $tiposValidos)) {
        if (registrarMovimiento($producto_id, $uid, $tipo, $cantidad, $motivo, $sucursal)) {
            header("Location: movimiento-agregar.php?msg=" . urlencode("Movimiento registrado correctamente."));
            exit();
        } else {
            $error = "❌ No se pudo registrar el movimiento. Intenta de nuevo.";
        }
    } else {
        $error = "⚠️ Datos inválidos: verifica producto, cantidad y tipo.";
    }
}

// Productos
$productos = getProductos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registrar movimiento - Inventario</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <style>
    .page-header {
      display:flex; justify-content:space-between; align-items:center;
      padding:14px 20px; border-radius:12px;
      background:linear-gradient(135deg,var(--act1),var(--act2));
      color:#fff; box-shadow:0 4px 14px rgba(0,0,0,.15);
      margin-bottom:18px;
    }
    .center-wrapper {display:flex; justify-content:center; padding:20px;}
    form.form-card {
      width:100%; max-width:600px; background:var(--card-bg-1);
      padding:20px; border-radius:12px; box-shadow:var(--shadow);
    }
    form.form-card label {display:block; margin-top:12px; font-weight:600;}
    form.form-card input, form.form-card select {
      width:100%; padding:10px 12px; border-radius:8px; border:1px solid #ccc; margin-top:6px;
    }
    .alert.error {margin-bottom:16px; padding:12px 16px; border-radius:8px; font-weight:600;
      background:#f8d7da; color:#721c24; border:1px solid #f5c6cb;}
    .btn-row {margin-top:16px; display:flex; gap:12px;}
    #stockInfo {margin-top:8px; font-weight:bold; color:#555;}
    #errorStock {margin-top:8px; color:#c00; font-weight:bold; display:none;}
  </style>
</head>
<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">
    <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

    <section class="content">
      <div class="page-header">
        <h2>➕ Registrar Movimiento</h2>
      </div>

      <?php if (!empty($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
      <?php elseif (isset($_GET['msg'])): ?>
        <div class="alert success"><?= htmlspecialchars($_GET['msg']) ?></div>
      <?php endif; ?>

      <!-- Formulario -->
      <div class="center-wrapper">
        <form method="POST" class="form-card" id="movForm">
          <label>Producto</label>
          <select name="producto_id" id="productoSelect" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($productos as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
            <?php endforeach; ?>
          </select>

          <label>Sucursal</label>
          <input type="text" name="sucursal" id="sucursalInput" value="Principal">

          <div id="stockInfo">Stock actual: -</div>
          <div id="errorStock">⚠️ No hay suficiente stock para esta salida</div>

          <label>Tipo de movimiento</label>
          <select name="tipo" id="tipoSelect" required>
            <option value="entrada">Entrada (compra/proveedor)</option>
            <option value="salida">Salida (venta)</option>
            <option value="ajuste">Ajuste manual</option>
          </select>

          <label>Cantidad</label>
          <input type="number" name="cantidad" id="cantidadInput" min="1" required>

          <label>Motivo</label>
          <input type="text" name="motivo" placeholder="Ej. Compra a proveedor, venta, ajuste...">

          <div class="btn-row">
            <button type="submit" id="btnGuardar" class="btn btn-primary">Guardar</button>
            <a href="movimientos-listar.php" class="btn">Cancelar</a>
          </div>
        </form>
      </div>
    </section>
  </main>

<script>
let stockDisponible = 0;

const $producto   = document.getElementById("productoSelect");
const $sucursal   = document.getElementById("sucursalInput");
const $cantidad   = document.getElementById("cantidadInput");
const $tipo       = document.getElementById("tipoSelect");
const $btnGuardar = document.getElementById("btnGuardar");
const $stockInfo  = document.getElementById("stockInfo");
const $errorStock = document.getElementById("errorStock");

// Eventos
$producto.addEventListener("change", fetchStock);
$sucursal.addEventListener("input", fetchStock);
$cantidad.addEventListener("input", validarCantidad);
$tipo.addEventListener("change", validarCantidad);

// 🔹 Stock del producto seleccionado
function fetchStock() {
  const prodId = $producto.value;
  const suc = $sucursal.value || 'Principal';

  if (!prodId) {
    stockDisponible = 0;
    $stockInfo.textContent = "Stock actual: -";
    validarCantidad();
    return;
  }

  fetch("../inventario/stock-ajax.php?producto_id=" + encodeURIComponent(prodId) + "&sucursal=" + encodeURIComponent(suc), {
    headers: { "Accept": "application/json" }
  })
    .then(r => r.json())
    .then((data) => {
      if (!data.ok) throw new Error(data.error || "Error desconocido");
      stockDisponible = Number(data.stock || 0);
      $stockInfo.textContent = "Stock actual: " + stockDisponible;
      validarCantidad();
    })
    .catch((err) => {
      console.error("Stock AJAX error:", err);
      stockDisponible = 0;
      $stockInfo.textContent = "Stock actual: error";
      validarCantidad();
    });
}

function validarCantidad() {
  const tipo = $tipo.value;
  const cantidad = parseInt($cantidad.value, 10) || 0;

  if (tipo === "salida" && cantidad > stockDisponible) {
    $btnGuardar.disabled = true;
    $errorStock.style.display = "block";
  } else {
    $btnGuardar.disabled = false;
    $errorStock.style.display = "none";
  }
}

// Inicial
fetchStock();
</script>
</body>
</html>
