<?php
/**
 * Panel de Administración - Gestión de Productos
 * 
 * @package    LumiSpace
 * @subpackage Admin
 * @author     Tu Nombre
 * @version    2.0.0
 */

declare(strict_types=1);

// Configuración de errores para desarrollo (comentar en producción)
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

// Inicialización de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dependencias
require_once __DIR__ . "/../../config/functions.php";

// ====================================
// 🔐 AUTENTICACIÓN Y AUTORIZACIÓN
// ====================================
class AuthMiddleware {
    public static function requireAdmin(): void {
        if (!self::isAuthenticated() || !self::isAdmin()) {
            self::redirectToLogin();
        }
    }
    
    private static function isAuthenticated(): bool {
        return isset($_SESSION['usuario_id']);
    }
    
    private static function isAdmin(): bool {
        return ($_SESSION['usuario_rol'] ?? '') === 'admin';
    }
    
    private static function redirectToLogin(): void {
        header("Location: ../login.php?error=unauthorized");
        exit();
    }
}

// Verificar permisos
AuthMiddleware::requireAdmin();

// ====================================
// 📊 CAPA DE DATOS
// ====================================
class ProductoRepository {
    public static function getAll(): array {
        try {
            $productos = getProductos();
            return self::sanitizeProductos($productos);
        } catch (Exception $e) {
            error_log("Error al obtener productos: " . $e->getMessage());
            return [];
        }
    }
    
    private static function sanitizeProductos(array $productos): array {
        return array_map(function($p) {
            return [
                'id' => (int)$p['id'],
                'nombre' => trim($p['nombre']),
                'categoria' => trim($p['categoria'] ?? 'Sin categoría'),
                'proveedor' => trim($p['proveedor'] ?? 'N/A'),
                'precio' => (float)$p['precio'],
                'stock_real' => (int)($p['stock_real'] ?? 0),
                'imagen' => !empty($p['imagen']) ? $p['imagen'] : null,
                'creado_en' => $p['creado_en'] ?? null
            ];
        }, $productos);
    }
}

class CategoriaRepository {
    public static function getAll(): array {
        try {
            return getCategorias();
        } catch (Exception $e) {
            error_log("Error al obtener categorías: " . $e->getMessage());
            return [];
        }
    }
}

// ====================================
// 🎨 HELPERS DE PRESENTACIÓN
// ====================================
class ViewHelper {
    public static function formatPrice(float $price): string {
        return '$' . number_format($price, 2, '.', ',');
    }
    
    public static function formatDate(?string $date): string {
        if (empty($date)) return '-';
        
        try {
            return date("d/m/Y H:i", strtotime($date));
        } catch (Exception $e) {
            return '-';
        }
    }
    
    public static function getStockBadgeClass(int $stock): string {
        if ($stock <= 5) return 'low';
        if ($stock <= 20) return 'medium';
        return 'high';
    }
    
    public static function escape(?string $value): string {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

class AlertHelper {
    public static function render(): ?string {
        if (isset($_GET['msg'])) {
            return self::successAlert(ViewHelper::escape($_GET['msg']));
        }
        
        if (isset($_GET['error'])) {
            return self::errorAlert(ViewHelper::escape($_GET['error']));
        }
        
        return null;
    }
    
    private static function successAlert(string $message): string {
        return sprintf('<div class="alert alert--success">%s</div>', $message);
    }
    
    private static function errorAlert(string $message): string {
        return sprintf('<div class="alert alert--error">%s</div>', $message);
    }
}

// ====================================
// 🎯 OBTENER DATOS
// ====================================
$productos = ProductoRepository::getAll();
$categorias = CategoriaRepository::getAll();
$totalProductos = count($productos);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Panel de administración de productos - LumiSpace">
    <title>Productos - Panel Admin | LumiSpace</title>
    
    <!-- Estilos -->
    <link rel="stylesheet" href="../../css/dashboard.css">
    <style>
        /* ====================================
           🎨 VARIABLES Y RESET
           ==================================== */
        :root {
            --primary-gradient: linear-gradient(135deg, var(--act1, #667eea), var(--act2, #764ba2));
            --shadow-sm: 0 2px 8px rgba(0,0,0,.08);
            --shadow-md: 0 4px 14px rgba(0,0,0,.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,.15);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 14px;
            --transition-base: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* ====================================
           📐 LAYOUT
           ==================================== */
        section.content.wide {
            max-width: 100%;
            width: 100%;
            margin: 0;
            padding: 0 20px;
        }

        /* ====================================
           🎯 HEADER
           ==================================== */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 24px;
            border-radius: var(--radius-lg);
            background: var(--primary-gradient);
            color: #fff;
            box-shadow: var(--shadow-lg);
            margin-bottom: 24px;
        }

        .page-header__title {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-header__badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            backdrop-filter: blur(10px);
        }

        .btn-add {
            background: #fff;
            color: var(--act1, #667eea);
            font-weight: 600;
            padding: 10px 20px;
            border-radius: var(--radius-md);
            text-decoration: none;
            transition: var(--transition-base);
            box-shadow: var(--shadow-sm);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: var(--act1, #667eea);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* ====================================
           ⚠️ ALERTAS
           ==================================== */
        .alert {
            margin-bottom: 20px;
            padding: 14px 18px;
            border-radius: var(--radius-md);
            font-weight: 600;
            animation: slideDown 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert--success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert--error {
            background: #f8d7da;
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

        /* ====================================
           🔍 FILTROS
           ==================================== */
        .filters {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 12px;
            margin-bottom: 20px;
        }

        .filters__search {
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            transition: var(--transition-base);
            background: #fff;
        }

        .filters__search:focus {
            outline: none;
            border-color: var(--act1, #667eea);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filters__select {
            padding: 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            background: #fff;
            cursor: pointer;
            transition: var(--transition-base);
            min-width: 220px;
        }

        .filters__select:focus {
            outline: none;
            border-color: var(--act1, #667eea);
        }

        /* ====================================
           📊 TABLA
           ==================================== */
        .table-wrapper {
            overflow-x: auto;
            background: #fff;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead {
            background: linear-gradient(to bottom, #f8f9fa, #e9ecef);
        }

        .table th {
            padding: 16px;
            text-align: left;
            font-weight: 700;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            cursor: pointer;
            user-select: none;
            transition: var(--transition-base);
            position: relative;
        }

        .table th:hover {
            background: #dee2e6;
        }

        .table th::after {
            content: '↕';
            margin-left: 6px;
            opacity: 0.4;
            font-size: 0.8rem;
        }

        .table td {
            padding: 14px 16px;
            border-bottom: 1px solid #f0f0f0;
            color: #333;
            font-size: 0.95rem;
        }

        .table tbody tr {
            transition: var(--transition-base);
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
        }

        /* Imagen de producto */
        .prod-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-md);
            border: 2px solid #e0e0e0;
            box-shadow: var(--shadow-sm);
            transition: var(--transition-base);
        }

        .prod-img:hover {
            transform: scale(1.5);
            box-shadow: var(--shadow-md);
            z-index: 10;
        }

        .no-image {
            color: #aaa;
            font-size: 0.85rem;
            font-style: italic;
        }

        /* Badges de stock */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-block;
            text-align: center;
            min-width: 50px;
        }

        .badge--low {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: #fff;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
        }

        .badge--medium {
            background: linear-gradient(135deg, #ffd93d, #f6b93b);
            color: #664d03;
            box-shadow: 0 2px 8px rgba(255, 217, 61, 0.3);
        }

        .badge--high {
            background: linear-gradient(135deg, #51cf66, #37b24d);
            color: #fff;
            box-shadow: 0 2px 8px rgba(81, 207, 102, 0.3);
        }

        /* Botones de acción */
        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 14px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition-base);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .btn--edit {
            background: #e3f2fd;
            color: #1976d2;
        }

        .btn--edit:hover {
            background: #1976d2;
            color: #fff;
            transform: translateY(-2px);
        }

        .btn--delete {
            background: #ffebee;
            color: #c62828;
        }

        .btn--delete:hover {
            background: #c62828;
            color: #fff;
            transform: translateY(-2px);
        }

        /* ====================================
           📄 PAGINACIÓN
           ==================================== */
        .pagination {
            margin-top: 24px;
            display: flex;
            gap: 8px;
            justify-content: center;
            padding: 20px 0;
        }

        .pagination__btn {
            padding: 10px 16px;
            border: 2px solid #e0e0e0;
            background: #fff;
            cursor: pointer;
            border-radius: var(--radius-sm);
            font-weight: 600;
            transition: var(--transition-base);
            min-width: 44px;
        }

        .pagination__btn:hover {
            border-color: var(--act1, #667eea);
            color: var(--act1, #667eea);
            transform: translateY(-2px);
        }

        .pagination__btn--active {
            background: var(--act1, #667eea);
            color: #fff;
            border-color: var(--act1, #667eea);
        }

        /* ====================================
           📱 RESPONSIVE
           ==================================== */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                gap: 12px;
                text-align: center;
            }

            .filters {
                grid-template-columns: 1fr;
            }

            .table {
                font-size: 0.85rem;
            }

            .table th,
            .table td {
                padding: 10px;
            }

            .prod-img {
                width: 45px;
                height: 45px;
            }
        }

        /* ====================================
           🎭 ESTADO VACÍO
           ==================================== */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }

        .empty-state__icon {
            font-size: 4rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-state__title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .empty-state__text {
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <?php include(__DIR__ . "/../../includes/sidebar-admin.php"); ?>
    
    <main class="main">
        <?php include(__DIR__ . "/../../includes/header-admin.php"); ?>

        <section class="content wide">
            <!-- ====================================
                 🎯 HEADER CON CONTADOR
                 ==================================== -->
            <div class="page-header">
                <h2 class="page-header__title">
                    📦 Gestión de Productos
                    <span class="page-header__badge"><?= $totalProductos ?> productos</span>
                </h2>
                <a href="producto-nuevo.php" class="btn-add" aria-label="Agregar nuevo producto">
                    <span>➕</span>
                    <span>Nuevo Producto</span>
                </a>
            </div>

            <!-- ====================================
                 ⚠️ ALERTAS DINÁMICAS
                 ==================================== -->
            <?php if ($alert = AlertHelper::render()): ?>
                <?= $alert ?>
            <?php endif; ?>

            <!-- ====================================
                 🔍 FILTROS Y BÚSQUEDA
                 ==================================== -->
            <div class="filters">
                <input 
                    type="text" 
                    id="searchInput" 
                    class="filters__search"
                    placeholder="🔍 Buscar por nombre, categoría o proveedor..."
                    aria-label="Buscar productos"
                >
                <select 
                    id="filterCategoria" 
                    class="filters__select"
                    aria-label="Filtrar por categoría"
                >
                    <option value="">📁 Todas las categorías</option>
                    <?php foreach($categorias as $categoria): ?>
                        <option value="<?= ViewHelper::escape($categoria['nombre']) ?>">
                            <?= ViewHelper::escape($categoria['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- ====================================
                 📊 TABLA DE PRODUCTOS
                 ==================================== -->
            <div class="card p-0 table-wrapper">
                <table class="table" id="productosTable">
                    <thead>
                        <tr>
                            <th data-sort="id">ID</th>
                            <th>Imagen</th>
                            <th data-sort="nombre">Nombre</th>
                            <th data-sort="categoria">Categoría</th>
                            <th data-sort="proveedor">Proveedor</th>
                            <th data-sort="precio">Precio</th>
                            <th data-sort="stock">Stock</th>
                            <th data-sort="fecha">Creado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productosBody">
                        <?php if (!empty($productos)): ?>
                            <?php foreach($productos as $producto): ?>
                                <tr data-id="<?= $producto['id'] ?>">
                                    <td><?= $producto['id'] ?></td>
                                    <td>
                                        <?php if ($producto['imagen']): ?>
                                            <img 
                                                src="<?= ViewHelper::escape($producto['imagen']) ?>" 
                                                alt="<?= ViewHelper::escape($producto['nombre']) ?>" 
                                                class="prod-img"
                                                loading="lazy"
                                            >
                                        <?php else: ?>
                                            <span class="no-image">Sin imagen</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= ViewHelper::escape($producto['nombre']) ?></strong>
                                    </td>
                                    <td><?= ViewHelper::escape($producto['categoria']) ?></td>
                                    <td><?= ViewHelper::escape($producto['proveedor']) ?></td>
                                    <td><strong><?= ViewHelper::formatPrice($producto['precio']) ?></strong></td>
                                    <td>
                                        <span class="badge badge--<?= ViewHelper::getStockBadgeClass($producto['stock_real']) ?>">
                                            <?= $producto['stock_real'] ?>
                                        </span>
                                    </td>
                                    <td><?= ViewHelper::formatDate($producto['creado_en']) ?></td>
                                    <td>
                                        <div class="actions">
                                            <a 
                                                href="producto-editar.php?id=<?= $producto['id'] ?>" 
                                                class="btn btn--edit"
                                                aria-label="Editar <?= ViewHelper::escape($producto['nombre']) ?>"
                                            >
                                                ✏️
                                            </a>
                                            <a 
                                                href="producto-eliminar.php?id=<?= $producto['id'] ?>" 
                                                class="btn btn--delete"
                                                onclick="return confirm('¿Estás seguro de eliminar este producto?')"
                                                aria-label="Eliminar <?= ViewHelper::escape($producto['nombre']) ?>"
                                            >
                                                🗑️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <div class="empty-state__icon">📦</div>
                                        <h3 class="empty-state__title">No hay productos registrados</h3>
                                        <p class="empty-state__text">Comienza agregando tu primer producto</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Paginación -->
                <div class="pagination" id="pagination"></div>
            </div>
        </section>
    </main>

    <!-- ====================================
         📜 JAVASCRIPT
         ==================================== -->
    <script>
        'use strict';

        /**
         * Sistema de filtrado y búsqueda
         */
        class ProductFilter {
            constructor() {
                this.searchInput = document.getElementById('searchInput');
                this.categorySelect = document.getElementById('filterCategoria');
                this.tableBody = document.getElementById('productosBody');
                this.rows = Array.from(this.tableBody.querySelectorAll('tr'));
                
                this.init();
            }

            init() {
                this.searchInput.addEventListener('input', () => this.filter());
                this.categorySelect.addEventListener('change', () => this.filter());
            }

            filter() {
                const searchTerm = this.searchInput.value.toLowerCase().trim();
                const selectedCategory = this.categorySelect.value.toLowerCase();

                this.rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const category = row.children[3]?.textContent.toLowerCase() || '';
                    
                    const matchesSearch = !searchTerm || text.includes(searchTerm);
                    const matchesCategory = !selectedCategory || category === selectedCategory;
                    
                    row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
                });

                // Reiniciar paginación después de filtrar
                if (window.pagination) {
                    window.pagination.reset();
                }
            }
        }

        /**
         * Sistema de paginación
         */
        class Pagination {
            constructor(rowsPerPage = 10) {
                this.rowsPerPage = rowsPerPage;
                this.container = document.getElementById('pagination');
                this.tableBody = document.getElementById('productosBody');
                this.currentPage = 1;
                
                this.init();
            }

            init() {
                this.rows = Array.from(this.tableBody.querySelectorAll('tr')).filter(row => {
                    return row.style.display !== 'none';
                });
                
                this.totalPages = Math.ceil(this.rows.length / this.rowsPerPage);
                
                if (this.totalPages > 1) {
                    this.render();
                    this.showPage(1);
                }
            }

            render() {
                this.container.innerHTML = '';
                
                for (let i = 1; i <= this.totalPages; i++) {
                    const button = document.createElement('button');
                    button.textContent = i;
                    button.className = 'pagination__btn';
                    button.setAttribute('aria-label', `Página ${i}`);
                    
                    button.addEventListener('click', () => this.showPage(i));
                    this.container.appendChild(button);
                }
            }

            showPage(page) {
                this.currentPage = page;
                const start = (page - 1) * this.rowsPerPage;
                const end = start + this.rowsPerPage;

                this.rows.forEach((row, index) => {
                    row.style.display = (index >= start && index < end) ? '' : 'none';
                });

                // Actualizar botones activos
                const buttons = this.container.querySelectorAll('.pagination__btn');
                buttons.forEach((btn, index) => {
                    btn.classList.toggle('pagination__btn--active', index + 1 === page);
                });

                // Scroll suave al inicio de la tabla
                document.querySelector('.table-wrapper').scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
            }

            reset() {
                this.init();
            }
        }

        /**
         * Sistema de ordenamiento de tabla
         */
        class TableSorter {
            constructor() {
                this.table = document.getElementById('productosTable');
                this.headers = this.table.querySelectorAll('th[data-sort]');
                this.tbody = document.getElementById('productosBody');
                this.sortDirection = {};
                
                this.init();
            }

            init() {
                this.headers.forEach(header => {
                    const sortKey = header.dataset.sort;
                    this.sortDirection[sortKey] = 'asc';
                    
                    header.addEventListener('click', () => this.sort(sortKey, header));
                });
            }

            sort(key, header) {
                const direction = this.sortDirection[key];
                const rows = Array.from(this.tbody.querySelectorAll('tr'));
                
                const columnIndex = Array.from(header.parentNode.children).indexOf(header);
                
                rows.sort((a, b) => {
                    let aVal = a.children[columnIndex]?.textContent.trim() || '';
                    let bVal = b.children[columnIndex]?.textContent.trim() || '';
                    
                    // Convertir a número si es posible
                    const aNum = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
                    const bNum = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
                    
                    if (!isNaN(aNum) && !isNaN(bNum)) {
                        return direction === 'asc' ? aNum - bNum : bNum - aNum;
                    }
                    
                    // Comparación de strings
                    return direction === 'asc' 
                        ? aVal.localeCompare(bVal)
                        : bVal.localeCompare(aVal);
                });
                
                // Reordenar filas
                rows.forEach(row => this.tbody.appendChild(row));
                
                // Cambiar dirección
                this.sortDirection[key] = direction === 'asc' ? 'desc' : 'asc';
                
                // Actualizar indicador visual
                this.updateSortIndicator(header, this.sortDirection[key]);
            }

            updateSortIndicator(header, direction) {
                this.headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
                header.classList.add(`sorted-${direction}`);
            }
        }

        // ====================================
        // 🚀 INICIALIZACIÓN
        // ====================================
        document.addEventListener('DOMContentLoaded', () => {
            // Inicializar componentes
            const productFilter = new ProductFilter();
            window.pagination = new Pagination(10);
            const tableSorter = new TableSorter();

            // Auto-cerrar alertas después de 5 segundos
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.animation = 'slideDown 0.3s ease reverse';
                    setTimeout(() => alert.remove(), 300);
                }, 5000);
            });

            console.log('✅ Sistema de gestión de productos inicializado');
        });
    </script>
</body>
</html>