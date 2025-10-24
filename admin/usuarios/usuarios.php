<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../../config/functions.php";

// 🚨 Solo Admin
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../../views/login.php?error=unauthorized");
    exit();
}

// Obtener usuarios
$usuarios = getTodosLosUsuarios();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>👥 Gestión de Empleados - LumiSpace</title>
  <link rel="stylesheet" href="../../css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    :root {
      --primary:#a1683a;
      --primary-dark:#8f5e4b;
      --success:#28a745;
      --warning:#ffc107;
      --danger:#dc3545;
      --info:#17a2b8;
      --light:#f8f9fa;
      --dark:#2a1f15;
      --bg-glass:rgba(255,255,255,0.75);
      --shadow-sm:0 2px 8px rgba(0,0,0,0.06);
      --shadow-md:0 4px 16px rgba(0,0,0,0.1);
      --shadow-lg:0 8px 32px rgba(0,0,0,0.12);
      --radius-sm:8px;
      --radius-md:12px;
      --radius-lg:16px;
      --transition:all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
      margin:0;
      padding:0;
      box-sizing:border-box;
    }

    body {
      font-family:'Poppins',sans-serif;
      color:var(--dark);
      background:linear-gradient(135deg,#f4f1ec,#e9e4dd);
      transition:background .4s ease,color .4s ease;
      overflow-x:hidden;
    }

    body.dark {
      --bg-glass:rgba(45,43,40,0.75);
      --light:#2d2b28;
      --dark:#f5f3f0;
      background:linear-gradient(135deg,#1b1916,#25221d);
      color:#f5f3f0;
    }

    section.content {
      max-width:1400px;
      margin:0 auto;
      padding:24px;
      animation:fadeIn 0.5s ease;
    }

    /* ==== HEADER ==== */
    .page-header {
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:24px 28px;
      border-radius:var(--radius-lg);
      background:linear-gradient(135deg,var(--primary),var(--primary-dark));
      color:#fff;
      box-shadow:var(--shadow-lg);
      margin-bottom:28px;
      position:relative;
      overflow:hidden;
    }

    .page-header::before {
      content:'';
      position:absolute;
      top:-50%;
      right:-10%;
      width:300px;
      height:300px;
      background:rgba(255,255,255,0.1);
      border-radius:50%;
      animation:float 6s ease-in-out infinite;
    }

    @keyframes float {
      0%,100%{transform:translateY(0) rotate(0deg);}
      50%{transform:translateY(-20px) rotate(10deg);}
    }

    .header-left {
      display:flex;
      align-items:center;
      gap:16px;
      position:relative;
      z-index:1;
    }

    .header-icon {
      width:48px;
      height:48px;
      background:rgba(255,255,255,0.2);
      border-radius:var(--radius-md);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:1.5rem;
      backdrop-filter:blur(10px);
    }

    .header-left h2 {
      font-size:1.75rem;
      font-weight:700;
      margin:0;
    }

    .header-left p {
      font-size:0.9rem;
      opacity:0.9;
      margin:0;
    }

    .header-right {
      display:flex;
      gap:12px;
      align-items:center;
      position:relative;
      z-index:1;
    }

    .btn-add {
      background:#fff;
      color:var(--primary);
      font-weight:600;
      padding:12px 24px;
      border-radius:var(--radius-md);
      text-decoration:none;
      display:flex;
      align-items:center;
      gap:8px;
      box-shadow:var(--shadow-md);
      transition:var(--transition);
      font-size:0.95rem;
    }

    .btn-add:hover {
      background:var(--primary);
      color:#fff;
      transform:translateY(-2px);
      box-shadow:var(--shadow-lg);
    }

    .toggle-mode {
      background:rgba(255,255,255,0.2);
      border:none;
      border-radius:var(--radius-md);
      width:44px;
      height:44px;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      cursor:pointer;
      font-size:1.2rem;
      transition:var(--transition);
      backdrop-filter:blur(10px);
    }

    .toggle-mode:hover {
      background:rgba(255,255,255,0.3);
      transform:scale(1.05) rotate(10deg);
    }

    /* ==== STATS CARDS ==== */
    .stats-grid {
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
      gap:20px;
      margin-bottom:28px;
    }

    .stat-card {
      background:var(--bg-glass);
      backdrop-filter:blur(10px);
      padding:20px;
      border-radius:var(--radius-md);
      box-shadow:var(--shadow-sm);
      transition:var(--transition);
      position:relative;
      overflow:hidden;
    }

    .stat-card::before {
      content:'';
      position:absolute;
      top:0;
      left:0;
      width:4px;
      height:100%;
      background:var(--accent);
      transition:var(--transition);
    }

    .stat-card:hover {
      transform:translateY(-4px);
      box-shadow:var(--shadow-md);
    }

    .stat-card:hover::before {
      width:100%;
      opacity:0.1;
    }

    .stat-header {
      display:flex;
      justify-content:space-between;
      align-items:center;
      margin-bottom:12px;
    }

    .stat-icon {
      width:40px;
      height:40px;
      border-radius:var(--radius-sm);
      display:flex;
      align-items:center;
      justify-content:center;
      font-size:1.2rem;
      color:#fff;
    }

    .stat-value {
      font-size:2rem;
      font-weight:700;
      color:var(--dark);
    }

    .stat-label {
      font-size:0.85rem;
      color:#666;
      font-weight:500;
      margin-top:4px;
    }

    body.dark .stat-label {
      color:#999;
    }

    /* ==== FILTERS ==== */
    .filters-bar {
      background:var(--bg-glass);
      backdrop-filter:blur(10px);
      padding:20px;
      border-radius:var(--radius-md);
      box-shadow:var(--shadow-sm);
      margin-bottom:20px;
      display:flex;
      gap:16px;
      flex-wrap:wrap;
      align-items:center;
    }

    .search-box {
      flex:1;
      min-width:250px;
      position:relative;
    }

    .search-box input {
      width:100%;
      padding:10px 16px 10px 42px;
      border:2px solid #e0e0e0;
      border-radius:var(--radius-sm);
      font-size:0.95rem;
      transition:var(--transition);
      background:#fff;
    }

    body.dark .search-box input {
      background:var(--light);
      border-color:#423a32;
      color:var(--dark);
    }

    .search-box input:focus {
      outline:none;
      border-color:var(--primary);
      box-shadow:0 0 0 3px rgba(161,104,58,0.1);
    }

    .search-box i {
      position:absolute;
      left:14px;
      top:50%;
      transform:translateY(-50%);
      color:#999;
    }

    .filter-select {
      padding:10px 16px;
      border:2px solid #e0e0e0;
      border-radius:var(--radius-sm);
      font-size:0.95rem;
      background:#fff;
      cursor:pointer;
      transition:var(--transition);
      font-family:'Poppins',sans-serif;
    }

    body.dark .filter-select {
      background:var(--light);
      border-color:#423a32;
      color:var(--dark);
    }

    .filter-select:focus {
      outline:none;
      border-color:var(--primary);
    }

    /* ==== TABLE ==== */
    .table-container {
      background:var(--bg-glass);
      backdrop-filter:blur(10px);
      border-radius:var(--radius-lg);
      box-shadow:var(--shadow-md);
      overflow:hidden;
      animation:fadeIn 0.6s ease;
    }

    .table-wrapper {
      overflow-x:auto;
    }

    table {
      width:100%;
      border-collapse:collapse;
    }

    thead {
      background:linear-gradient(135deg,rgba(161,104,58,0.1),rgba(143,94,75,0.1));
      position:sticky;
      top:0;
      z-index:10;
    }

    body.dark thead {
      background:linear-gradient(135deg,rgba(161,104,58,0.2),rgba(143,94,75,0.2));
    }

    th {
      padding:16px 20px;
      text-align:left;
      font-weight:600;
      font-size:0.9rem;
      color:var(--primary);
      text-transform:uppercase;
      letter-spacing:0.5px;
      white-space:nowrap;
    }

    tbody tr {
      border-bottom:1px solid rgba(0,0,0,0.05);
      transition:var(--transition);
    }

    body.dark tbody tr {
      border-bottom:1px solid rgba(255,255,255,0.05);
    }

    tbody tr:hover {
      background:rgba(161,104,58,0.05);
      transform:scale(1.002);
    }

    td {
      padding:16px 20px;
      font-size:0.95rem;
      vertical-align:middle;
    }

    /* ==== USER AVATAR ==== */
    .user-cell {
      display:flex;
      align-items:center;
      gap:12px;
    }

    .user-avatar {
      width:48px;
      height:48px;
      border-radius:50%;
      display:flex;
      align-items:center;
      justify-content:center;
      color:#fff;
      font-weight:700;
      font-size:1.1rem;
      background:linear-gradient(135deg,var(--primary),var(--primary-dark));
      text-transform:uppercase;
      box-shadow:var(--shadow-sm);
      flex-shrink:0;
    }

    .user-info {
      display:flex;
      flex-direction:column;
      gap:2px;
    }

    .user-name {
      font-weight:600;
      color:var(--dark);
    }

    .user-email {
      font-size:0.85rem;
      color:#666;
    }

    body.dark .user-email {
      color:#999;
    }

    /* ==== BADGES ==== */
    .badge {
      padding:6px 12px;
      border-radius:20px;
      font-size:0.8rem;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:0.5px;
      display:inline-flex;
      align-items:center;
      gap:6px;
      white-space:nowrap;
    }

    .badge.admin{background:#a1683a;color:#fff;}
    .badge.gestor{background:#2ca58d;color:#fff;}
    .badge.cajero{background:#f0ad4e;color:#fff;}
    .badge.usuario{background:#17a2b8;color:#fff;}
    .badge.activo{background:#28a745;color:#fff;}
    .badge.inactivo{background:#dc3545;color:#fff;}
    .badge.suspendido{background:#ffc107;color:#333;}
    .badge.verificado{background:#007bff;color:#fff;}
    .badge.noverificado{background:#6c757d;color:#fff;}

    /* ==== ACTION BUTTONS ==== */
    .actions-cell {
      display:flex;
      gap:8px;
    }

    .btn-action {
      width:36px;
      height:36px;
      border-radius:var(--radius-sm);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      font-size:0.9rem;
      cursor:pointer;
      border:none;
      transition:var(--transition);
      text-decoration:none;
      box-shadow:var(--shadow-sm);
    }

    .btn-view {
      background:linear-gradient(135deg,#17a2b8,#138496);
      color:#fff;
    }

    .btn-edit {
      background:linear-gradient(135deg,#ffc107,#e0a800);
      color:#fff;
    }

    .btn-delete {
      background:linear-gradient(135deg,#dc3545,#c82333);
      color:#fff;
    }

    .btn-action:hover {
      transform:translateY(-2px) scale(1.05);
      box-shadow:var(--shadow-md);
    }

    .btn-action:active {
      transform:translateY(0) scale(0.98);
    }

    /* ==== EMPTY STATE ==== */
    .empty-state {
      text-align:center;
      padding:60px 20px;
    }

    .empty-state i {
      font-size:4rem;
      color:#ccc;
      margin-bottom:20px;
    }

    .empty-state h3 {
      font-size:1.5rem;
      color:var(--dark);
      margin-bottom:8px;
    }

    .empty-state p {
      color:#666;
      margin-bottom:24px;
    }

    /* ==== ALERTS ==== */
    .alert {
      padding:16px 20px;
      border-radius:var(--radius-md);
      margin-bottom:20px;
      display:flex;
      align-items:center;
      gap:12px;
      animation:slideDown 0.3s ease;
      box-shadow:var(--shadow-sm);
    }

    .alert.success {
      background:#d4edda;
      color:#155724;
      border-left:4px solid #28a745;
    }

    .alert.error {
      background:#f8d7da;
      color:#721c24;
      border-left:4px solid #dc3545;
    }

    .alert i {
      font-size:1.2rem;
    }

    @keyframes slideDown {
      from {
        opacity:0;
        transform:translateY(-20px);
      }
      to {
        opacity:1;
        transform:translateY(0);
      }
    }

    @keyframes fadeIn {
      from {
        opacity:0;
        transform:translateY(10px);
      }
      to {
        opacity:1;
        transform:translateY(0);
      }
    }

    /* ==== RESPONSIVE ==== */
    @media (max-width: 768px) {
      .page-header {
        flex-direction:column;
        gap:16px;
        text-align:center;
      }

      .stats-grid {
        grid-template-columns:1fr;
      }

      .filters-bar {
        flex-direction:column;
      }

      .search-box {
        width:100%;
      }

      table {
        font-size:0.85rem;
      }

      th, td {
        padding:12px 10px;
      }

      .user-avatar {
        width:40px;
        height:40px;
        font-size:1rem;
      }
    }
  </style>
</head>

<body>
  <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
  <main class="main">

    <section class="content">
      <!-- HEADER -->
      <div class="page-header">
        <div class="header-left">
          <div class="header-icon">
            <i class="fas fa-users"></i>
          </div>
          <div>
            <h2>Gestión de Empleados</h2>
            <p>Administra tu equipo de trabajo</p>
          </div>
        </div>
        <div class="header-right">
          <button class="toggle-mode" id="toggleMode" title="Cambiar tema">
            <i class="fas fa-moon"></i>
          </button>
          <a href="usuario-agregar.php" class="btn-add">
            <i class="fas fa-user-plus"></i>
            Nuevo Empleado
          </a>
        </div>
      </div>

      <!-- ALERTS -->
      <?php if (isset($_GET['error'])): ?>
        <div class="alert error">
          <i class="fas fa-exclamation-circle"></i>
          <span><?= htmlspecialchars($_GET['error']) ?></span>
        </div>
      <?php elseif (isset($_GET['msg'])): ?>
        <div class="alert success">
          <i class="fas fa-check-circle"></i>
          <span><?= htmlspecialchars($_GET['msg']) ?></span>
        </div>
      <?php endif; ?>

      <!-- STATS -->
      <?php
      $total = count($usuarios);
      $activos = count(array_filter($usuarios, fn($u) => $u['estado'] === 'activo'));
      $admins = count(array_filter($usuarios, fn($u) => $u['rol'] === 'admin'));
      $cajeros = count(array_filter($usuarios, fn($u) => $u['rol'] === 'cajero'));
      ?>
      <div class="stats-grid">
        <div class="stat-card" style="--accent:#17a2b8;">
          <div class="stat-header">
            <div class="stat-icon" style="background:#17a2b8;">
              <i class="fas fa-users"></i>
            </div>
          </div>
          <div class="stat-value"><?= $total ?></div>
          <div class="stat-label">Total Empleados</div>
        </div>

        <div class="stat-card" style="--accent:#28a745;">
          <div class="stat-header">
            <div class="stat-icon" style="background:#28a745;">
              <i class="fas fa-user-check"></i>
            </div>
          </div>
          <div class="stat-value"><?= $activos ?></div>
          <div class="stat-label">Activos</div>
        </div>

        <div class="stat-card" style="--accent:#a1683a;">
          <div class="stat-header">
            <div class="stat-icon" style="background:#a1683a;">
              <i class="fas fa-user-shield"></i>
            </div>
          </div>
          <div class="stat-value"><?= $admins ?></div>
          <div class="stat-label">Administradores</div>
        </div>

        <div class="stat-card" style="--accent:#f0ad4e;">
          <div class="stat-header">
            <div class="stat-icon" style="background:#f0ad4e;">
              <i class="fas fa-cash-register"></i>
            </div>
          </div>
          <div class="stat-value"><?= $cajeros ?></div>
          <div class="stat-label">Cajeros</div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="filters-bar">
        <div class="search-box">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Buscar por nombre, email o número de empleado...">
        </div>
        
        <select class="filter-select" id="filterRol">
          <option value="">Todos los roles</option>
          <option value="admin">Administrador</option>
          <option value="gestor">Gestor</option>
          <option value="cajero">Cajero</option>
          <option value="usuario">Usuario</option>
        </select>

        <select class="filter-select" id="filterEstado">
          <option value="">Todos los estados</option>
          <option value="activo">Activo</option>
          <option value="inactivo">Inactivo</option>
          <option value="suspendido">Suspendido</option>
        </select>
      </div>

      <!-- TABLE -->
      <div class="table-container">
        <div class="table-wrapper">
          <table id="usuariosTable">
            <thead>
              <tr>
                <th>ID</th>
                <th>Empleado</th>
                <th>Núm. Empleado</th>
                <th>Puesto</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Verificado</th>
                <th>Fecha Ingreso</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($usuarios)): ?>
                <?php foreach ($usuarios as $u): ?>
                  <tr data-rol="<?= strtolower($u['rol']) ?>" data-estado="<?= $u['estado'] ?>">
                    <td><strong><?= $u['id'] ?></strong></td>
                    
                    <td>
                      <div class="user-cell">
                        <div class="user-avatar"><?= strtoupper(substr($u['nombre'],0,1)) ?></div>
                        <div class="user-info">
                          <span class="user-name"><?= htmlspecialchars($u['nombre']) ?></span>
                          <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                        </div>
                      </div>
                    </td>

                    <td><strong><?= htmlspecialchars($u['num_empleado'] ?? 'N/A') ?></strong></td>
                    <td><?= htmlspecialchars(ucfirst($u['puesto'] ?? 'No asignado')) ?></td>

                    <td>
                      <span class="badge <?= strtolower($u['rol']) ?>">
                        <?php
                        $iconos = ['admin'=>'user-shield','gestor'=>'clipboard-list','cajero'=>'cash-register','usuario'=>'user'];
                        echo '<i class="fas fa-'.$iconos[$u['rol']].'"></i> ';
                        echo ucfirst($u['rol']);
                        ?>
                      </span>
                    </td>

                    <td>
                      <span class="badge <?= $u['estado'] ?>">
                        <?php
                        $iconosEstado = ['activo'=>'check-circle','inactivo'=>'times-circle','suspendido'=>'exclamation-triangle'];
                        echo '<i class="fas fa-'.($iconosEstado[$u['estado']] ?? 'circle').'"></i> ';
                        echo ucfirst($u['estado']);
                        ?>
                      </span>
                    </td>

                    <td>
                      <span class="badge <?= $u['email_verificado']?'verificado':'noverificado' ?>">
                        <i class="fas fa-<?= $u['email_verificado']?'check':'times' ?>"></i>
                        <?= $u['email_verificado']?'Sí':'No' ?>
                      </span>
                    </td>

                    <td><?= isset($u['fecha_ingreso']) ? date('d/m/Y', strtotime($u['fecha_ingreso'])) : '-' ?></td>

                    <td>
                      <div class="actions-cell">
                        <a href="usuario-editar.php?id=<?= $u['id'] ?>" class="btn-action btn-edit" title="Editar">
                          <i class="fas fa-edit"></i>
                        </a>
                        <a href="usuario-eliminar.php?id=<?= $u['id'] ?>" 
                           class="btn-action btn-delete" 
                           title="Eliminar"
                           onclick="return confirm('¿Estás seguro de eliminar a <?= htmlspecialchars($u['nombre']) ?>?\n\nEsta acción no se puede deshacer.')">
                          <i class="fas fa-trash-alt"></i>
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr>
                  <td colspan="9">
                    <div class="empty-state">
                      <i class="fas fa-users-slash"></i>
                      <h3>No hay empleados registrados</h3>
                      <p>Comienza agregando tu primer empleado al sistema</p>
                      <a href="usuario-agregar.php" class="btn-add">
                        <i class="fas fa-user-plus"></i>
                        Agregar Empleado
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </main>

<script>
// ====== MODO OSCURO ======
const toggle = document.getElementById('toggleMode');
const icon = toggle?.querySelector('i');

if (localStorage.getItem('theme') === 'dark') {
  document.body.classList.add('dark');
  if(icon) icon.classList.replace('fa-moon','fa-sun');
}

toggle?.addEventListener('click', () => {
  document.body.classList.toggle('dark');
  const darkMode = document.body.classList.contains('dark');
  
  if (darkMode) {
    if(icon) icon.classList.replace('fa-moon','fa-sun');
    localStorage.setItem('theme','dark');
  } else {
    if(icon) icon.classList.replace('fa-sun','fa-moon');
    localStorage.setItem('theme','light');
  }
});

// ====== BÚSQUEDA Y FILTROS ======
const searchInput = document.getElementById('searchInput');
const filterRol = document.getElementById('filterRol');
const filterEstado = document.getElementById('filterEstado');
const table = document.getElementById('usuariosTable');
const rows = table?.querySelectorAll('tbody tr');

function filterTable() {
  const searchTerm = searchInput?.value.toLowerCase() || '';
  const rolFilter = filterRol?.value.toLowerCase() || '';
  const estadoFilter = filterEstado?.value.toLowerCase() || '';

  rows?.forEach(row => {
    const text = row.textContent.toLowerCase();
    const rol = row.dataset.rol || '';
    const estado = row.dataset.estado || '';

    const matchSearch = text.includes(searchTerm);
    const matchRol = !rolFilter || rol === rolFilter;
    const matchEstado = !estadoFilter || estado === estadoFilter;

    if (matchSearch && matchRol && matchEstado) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

searchInput?.addEventListener('input', filterTable);
filterRol?.addEventListener('change', filterTable);
filterEstado?.addEventListener('change', filterTable);

// ====== AUTO-HIDE ALERTS ======
const alerts = document.querySelectorAll('.alert');
alerts.forEach(alert => {
  setTimeout(() => {
    alert.style.animation = 'slideDown 0.3s ease reverse';
    setTimeout(() => alert.remove(), 300);
  }, 5000);
});
</script>
</body>
</html>