<?php
/**
 * Panel de Administración - Gestión de Roles
 * 
 * @package    LumiSpace
 * @subpackage Admin
 * @version    2.0.1
 */

declare(strict_types=1);

// Inicialización de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dependencias
require_once __DIR__ . "/../../config/functions.php";
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/funciones/roles.php";

// ====================================
// 🔐 AUTENTICACIÓN
// ====================================
if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? '') !== 'admin') {
    header("Location: ../login.php?error=unauthorized");
    exit();
}

// ====================================
// 📊 OBTENER DATOS
// ====================================
try {
    $conn = getDBConnection();
    
    // Obtener todos los roles
    $rolesQuery = "SELECT 
                    r.id,
                    r.nombre,
                    r.descripcion,
                    COUNT(DISTINCT u.id) as usuarios_count
                FROM roles r
                LEFT JOIN usuarios u ON u.rol = r.nombre
                GROUP BY r.id, r.nombre, r.descripcion
                ORDER BY r.id ASC";
    
    $rolesResult = $conn->query($rolesQuery);
    $roles = [];
    
    if ($rolesResult && $rolesResult->num_rows > 0) {
        while ($row = $rolesResult->fetch_assoc()) {
            $roles[] = [
                'id' => (int)$row['id'],
                'nombre' => trim($row['nombre']),
                'descripcion' => trim($row['descripcion'] ?? ''),
                'usuarios_count' => (int)$row['usuarios_count'],
                'permisos_count' => 0 // Por ahora 0, luego puedes implementar permisos
            ];
        }
    }
    
    // Contar usuarios por rol
    $statsQuery = "SELECT 
                    rol,
                    COUNT(*) as total
                FROM usuarios
                WHERE rol IS NOT NULL
                GROUP BY rol";
    
    $statsResult = $conn->query($statsQuery);
    $rolesStats = [];
    
    if ($statsResult && $statsResult->num_rows > 0) {
        while ($row = $statsResult->fetch_assoc()) {
            $rolesStats[strtolower($row['rol'])] = (int)$row['total'];
        }
    }
    
} catch (Exception $e) {
    error_log("Error al obtener roles: " . $e->getMessage());
    $roles = [];
    $rolesStats = [];
}

$totalRoles = count($roles);

// ====================================
// 🎨 FUNCIONES HELPER
// ====================================
function escape(?string $value): string {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function getRoleBadgeClass(string $roleName): string {
    $roleName = strtolower($roleName);
    
    $badges = [
        'admin' => 'danger',
        'administrador' => 'danger',
        'supervisor' => 'warning',
        'vendedor' => 'info',
        'gestor' => 'info',
        'cajero' => 'primary',
        'inventario' => 'success',
        'usuario' => 'secondary'
    ];
    
    return $badges[$roleName] ?? 'secondary';
}

function getRoleIcon(string $roleName): string {
    $roleName = strtolower($roleName);
    
    $icons = [
        'admin' => 'fa-crown',
        'administrador' => 'fa-crown',
        'supervisor' => 'fa-user-tie',
        'vendedor' => 'fa-shopping-bag',
        'gestor' => 'fa-user-cog',
        'cajero' => 'fa-cash-register',
        'inventario' => 'fa-boxes',
        'usuario' => 'fa-user'
    ];
    
    return $icons[$roleName] ?? 'fa-user';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gestión de roles y permisos - LumiSpace">
    <title>Gestión de Roles - Panel Admin | LumiSpace</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <style>
        /* Variables y estilos mejorados */
        :root {
            --primary-gradient: linear-gradient(135deg, var(--act1), var(--act2));
            --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --shadow-sm: 0 2px 8px rgba(0,0,0,.08);
            --shadow-md: 0 4px 16px rgba(0,0,0,.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,.15);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        section.content.wide {
            max-width: 100%;
            width: 100%;
            margin: 0;
            padding: 0 20px;
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-radius: var(--radius-lg);
            background: var(--primary-gradient);
            color: #fff;
            box-shadow: var(--shadow-lg);
            margin-bottom: 24px;
        }

        .page-header__left {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .page-header__title {
            margin: 0;
            font-size: 1.8rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-header__subtitle {
            font-size: 0.95rem;
            opacity: 0.9;
            font-weight: 400;
        }

        .page-header__badge {
            background: rgba(255, 255, 255, 0.25);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .btn-add {
            background: #fff;
            color: var(--act1);
            font-weight: 600;
            padding: 12px 24px;
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-add:hover {
            background: var(--act1);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Estadísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--card-bg-1);
            padding: 24px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--card-bd);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .stat-card__icon {
            width: 64px;
            height: 64px;
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #fff;
            flex-shrink: 0;
        }

        .stat-card--primary .stat-card__icon {
            background: var(--primary-gradient);
        }

        .stat-card--success .stat-card__icon {
            background: var(--success-gradient);
        }

        .stat-card--warning .stat-card__icon {
            background: var(--warning-gradient);
        }

        .stat-card--info .stat-card__icon {
            background: var(--info-gradient);
        }

        .stat-card__content {
            flex: 1;
        }

        .stat-card__label {
            font-size: 0.85rem;
            color: var(--muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-card__value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text);
            margin: 4px 0;
        }

        /* Alertas */
        .alert {
            margin-bottom: 20px;
            padding: 16px 20px;
            border-radius: var(--radius-md);
            font-weight: 600;
            animation: slideDown 0.3s ease;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 0.95rem;
        }

        .alert--success {
            background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert--error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Búsqueda */
        .search-bar {
            margin-bottom: 20px;
            position: relative;
        }

        .search-bar__input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border: 2px solid var(--card-bd);
            background: var(--card-bg-1);
            color: var(--text);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: var(--transition);
        }

        .search-bar__input:focus {
            outline: none;
            border-color: var(--act1);
            box-shadow: 0 0 0 3px rgba(161, 104, 58, 0.1);
        }

        .search-bar__icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--muted);
            font-size: 1.1rem;
        }

        /* Tabla */
        .table-wrapper {
            background: var(--card-bg-1);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border: 1px solid var(--card-bd);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead {
            background: var(--card-bg-2);
        }

        .table th {
            padding: 18px 20px;
            text-align: left;
            font-weight: 700;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text);
            border-bottom: 2px solid var(--card-bd);
        }

        .table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--card-bd);
            color: var(--text);
            font-size: 0.95rem;
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: var(--card-bg-2);
        }

        .table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .role-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #fff;
        }

        .role-badge--danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            box-shadow: 0 2px 8px rgba(245, 87, 108, 0.3);
        }

        .role-badge--warning {
            background: linear-gradient(135deg, #f6d365 0%, #fda085 100%);
            box-shadow: 0 2px 8px rgba(253, 160, 133, 0.3);
        }

        .role-badge--info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            box-shadow: 0 2px 8px rgba(79, 172, 254, 0.3);
        }

        .role-badge--success {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            box-shadow: 0 2px 8px rgba(56, 239, 125, 0.3);
        }

        .role-badge--primary {
            background: linear-gradient(135deg, var(--act1), var(--act2));
            box-shadow: 0 2px 8px rgba(161, 104, 58, 0.3);
        }

        .role-badge--secondary {
            background: linear-gradient(135deg, #868f96 0%, #596164 100%);
            box-shadow: 0 2px 8px rgba(134, 143, 150, 0.3);
        }

        .role-description {
            color: var(--muted);
            font-size: 0.9rem;
            max-width: 400px;
            line-height: 1.4;
        }

        .count-badge {
            background: var(--card-bg-2);
            color: var(--text);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Acciones */
        .actions {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            text-decoration: none;
        }

        .btn-action--edit {
            background: #fff3cd;
            color: #856404;
        }

        .btn-action--edit:hover {
            background: #ffc107;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
        }

        .btn-action--delete {
            background: #f8d7da;
            color: #721c24;
        }

        .btn-action--delete:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }

        .empty-state__icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.3;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state__title {
            font-size: 1.6rem;
            font-weight: 600;
            margin-bottom: 12px;
            color: var(--text);
        }

        .empty-state__text {
            font-size: 1rem;
            margin-bottom: 24px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
    
    <main class="main">
        <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

        <section class="content wide">
            <!-- Header -->
            <div class="page-header">
                <div class="page-header__left">
                    <h2 class="page-header__title">
                        <i class="fas fa-user-shield"></i>
                        Gestión de Roles
                        <span class="page-header__badge"><?= $totalRoles ?> roles</span>
                    </h2>
                    <p class="page-header__subtitle">
                        Administra los roles y permisos del sistema
                    </p>
                </div>
                <a href="roles-agregar.php" class="btn-add">
                    <i class="fas fa-plus-circle"></i>
                    <span>Nuevo Rol</span>
                </a>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card stat-card--primary">
                    <div class="stat-card__icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <div class="stat-card__content">
                        <div class="stat-card__label">Total Roles</div>
                        <div class="stat-card__value"><?= $totalRoles ?></div>
                    </div>
                </div>

                <div class="stat-card stat-card--success">
                    <div class="stat-card__icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-card__content">
                        <div class="stat-card__label">Administradores</div>
                        <div class="stat-card__value"><?= $rolesStats['admin'] ?? 0 ?></div>
                    </div>
                </div>

                <div class="stat-card stat-card--info">
                    <div class="stat-card__icon">
                        <i class="fas fa-user-cog"></i>
                    </div>
                    <div class="stat-card__content">
                        <div class="stat-card__label">Gestores</div>
                        <div class="stat-card__value"><?= $rolesStats['gestor'] ?? 0 ?></div>
                    </div>
                </div>

                <div class="stat-card stat-card--warning">
                    <div class="stat-card__icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-card__content">
                        <div class="stat-card__label">Usuarios</div>
                        <div class="stat-card__value"><?= $rolesStats['usuario'] ?? 0 ?></div>
                    </div>
                </div>
            </div>

            <!-- Alertas -->
            <?php if (isset($_GET['success']) && isset($_GET['msg'])): ?>
                <div class="alert alert--success">
                    <i class="fas fa-check-circle"></i>
                    <?= escape($_GET['msg']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= escape($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <!-- Búsqueda -->
            <div class="search-bar">
                <i class="fas fa-search search-bar__icon"></i>
                <input 
                    type="text" 
                    id="searchInput" 
                    class="search-bar__input"
                    placeholder="Buscar roles por nombre o descripción..."
                >
            </div>

            <!-- Tabla -->
            <div class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Rol</th>
                            <th>Descripción</th>
                            <th>Usuarios</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="rolesBody">
                        <?php if (!empty($roles)): ?>
                            <?php foreach($roles as $rol): ?>
                                <tr data-id="<?= $rol['id'] ?>">
                                    <td><strong>#<?= $rol['id'] ?></strong></td>
                                    <td>
                                        <span class="role-badge role-badge--<?= getRoleBadgeClass($rol['nombre']) ?>">
                                            <i class="fas <?= getRoleIcon($rol['nombre']) ?>"></i>
                                            <?= escape($rol['nombre']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="role-description">
                                            <?= escape($rol['descripcion']) ?: 'Sin descripción' ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="count-badge">
                                            <i class="fas fa-users"></i>
                                            <?= $rol['usuarios_count'] ?> usuarios
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a 
                                                href="roles-editar.php?id=<?= $rol['id'] ?>" 
                                                class="btn-action btn-action--edit"
                                                title="Editar rol"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a 
                                                href="roles-eliminar.php?id=<?= $rol['id'] ?>" 
                                                class="btn-action btn-action--delete"
                                                title="Eliminar rol"
                                                onclick="return confirm('¿Seguro que deseas eliminar el rol \'<?= escape($rol['nombre']) ?>\'?\n\nEsta acción no se puede deshacer.')"
                                            >
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">
                                    <div class="empty-state">
                                        <div class="empty-state__icon">
                                            <i class="fas fa-user-shield"></i>
                                        </div>
                                        <h3 class="empty-state__title">No hay roles registrados</h3>
                                        <p class="empty-state__text">
                                            Comienza creando tu primer rol para gestionar los permisos del sistema
                                        </p>
                                        <a href="roles-agregar.php" class="btn-add">
                                            <i class="fas fa-plus-circle"></i>
                                            <span>Crear Primer Rol</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <script>
        'use strict';

        // Búsqueda
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            const rows = document.querySelectorAll('#rolesBody tr[data-id]');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Auto-cerrar alertas
        document.querySelectorAll('.alert').forEach(alert => {
            setTimeout(() => {
                alert.style.animation = 'slideDown 0.3s ease reverse';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        });

        // Animación de entrada
        document.querySelectorAll('#rolesBody tr[data-id]').forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                row.style.transition = 'all 0.4s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });

        console.log('✅ Sistema de gestión de roles inicializado');
    </script>
</body>
</html>