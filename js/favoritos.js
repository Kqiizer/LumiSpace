/**
 * ========================================
 * FAVORITOS - LumiSpace
 * ========================================
 * JavaScript de élite con UX profesional
 */

(function() {
    'use strict';

    // ========================================
    // CONFIGURACIÓN
    // ========================================
    const BASE = window.BASE_URL || document.body.dataset.base || '/';
    const USER_ID = window.USER_ID || parseInt(document.body.dataset.userId || '0', 10);
    const FAVORITES_ENDPOINT = `${BASE}api/wishlist/toggle.php`;
    const CART_ENDPOINT = `${BASE}api/carrito/add.php`;
    const CART_COUNT_ENDPOINT = `${BASE}api/carrito/count.php`;

    // Estado global
    let favoritesData = Array.isArray(window.FAVORITES_DATA) ? [...window.FAVORITES_DATA] : [];
    let currentView = 'grid';
    let currentCategory = 'all';
    let currentSort = 'recent';
    let searchTerm = '';
    let isProcessing = false;

    // ========================================
    // INICIALIZACIÓN
    // ========================================
    document.addEventListener('DOMContentLoaded', function() {
        initializeApp();
    });

    function initializeApp() {
        setupEventListeners();
        renderCategories();
        renderProducts();
        updateFavoritesCount();
        refreshCartCount();
        
        // Animar cards al cargar
        animateCards();
        
        // Escuchar cuando se agreguen favoritos desde otras páginas
        listenForFavoritesUpdates();
    }

    // Escuchar actualizaciones de favoritos desde otras páginas
    function listenForFavoritesUpdates() {
        // Solo verificar si estamos en estado vacío
        if (favoritesData.length === 0) {
            // Verificar periódicamente si se agregaron favoritos desde otras páginas
            const checkInterval = setInterval(async () => {
                try {
                    const response = await fetch(`${BASE}api/wishlist/count.php`, {
                        cache: 'no-store'
                    });
                    const data = await response.json();
                    const count = parseInt(data.count || 0, 10);
                    
                    // Si hay favoritos ahora, recargar la página
                    if (count > 0) {
                        clearInterval(checkInterval);
                        const emptyContainer = document.getElementById('emptyStateContainer');
                        if (emptyContainer) {
                            emptyContainer.style.opacity = '0';
                            emptyContainer.style.transition = 'opacity 0.3s ease';
                            setTimeout(() => {
                                window.location.reload();
                            }, 300);
                        } else {
                            window.location.reload();
                        }
                    }
                } catch (error) {
                    console.warn('Error verificando favoritos:', error);
                }
            }, 1500); // Verificar cada 1.5 segundos
            
            // Limpiar intervalo después de 5 minutos para no consumir recursos
            setTimeout(() => {
                clearInterval(checkInterval);
            }, 300000);
        }
    }

    // ========================================
    // EVENT LISTENERS
    // ========================================
    function setupEventListeners() {
        // Búsqueda
        const searchInput = document.getElementById('favorites-search');
        if (searchInput) {
            searchInput.addEventListener('input', debounce(handleSearch, 300));
        }

        const searchClear = document.getElementById('searchClear');
        if (searchClear) {
            searchClear.addEventListener('click', clearSearch);
        }

        // Ordenamiento
        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.addEventListener('change', handleSort);
        }

        // Vista (grid/list)
        const viewButtons = document.querySelectorAll('.view-btn');
        viewButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;
                if (view) {
                    switchView(view);
                }
            });
        });

        // Limpiar todos
        const clearAllBtn = document.getElementById('clearAllFavorites');
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', handleClearAll);
        }

        // Limpiar filtros
        const clearFiltersBtn = document.getElementById('clearFilters');
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', clearAllFilters);
        }

        // Botones de acción en cards
        document.addEventListener('click', handleCardAction);

        // Modal
        setupModalListeners();
    }

    function handleCardAction(e) {
        const target = e.target.closest('[data-product-id]');
        if (!target) return;

        const productId = parseInt(target.dataset.productId, 10);
        if (!productId) return;

        if (target.classList.contains('action-remove') || target.classList.contains('card-remove')) {
            e.preventDefault();
            removeFavorite(productId);
        } else if (target.classList.contains('action-cart') || target.classList.contains('card-add-btn')) {
            e.preventDefault();
            if (!target.disabled && !target.classList.contains('disabled')) {
                addToCart(productId, target);
            }
        }
    }

    function setupModalListeners() {
        const modal = document.getElementById('confirmModal');
        const closeBtn = document.getElementById('modalClose');
        const cancelBtn = document.getElementById('modalCancel');
        const confirmBtn = document.getElementById('modalConfirm');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        if (cancelBtn) {
            cancelBtn.addEventListener('click', closeModal);
        }

        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        // confirmBtn se configura dinámicamente en showModal
    }

    // ========================================
    // BÚSQUEDA Y FILTROS
    // ========================================
    function handleSearch(e) {
        searchTerm = e.target.value.toLowerCase().trim();
        const searchClear = document.getElementById('searchClear');
        
        if (searchClear) {
            searchClear.style.display = searchTerm ? 'flex' : 'none';
        }

        renderProducts();
    }

    function clearSearch() {
        const searchInput = document.getElementById('favorites-search');
        if (searchInput) {
            searchInput.value = '';
            searchTerm = '';
        }
        const searchClear = document.getElementById('searchClear');
        if (searchClear) {
            searchClear.style.display = 'none';
        }
        renderProducts();
    }

    function clearAllFilters() {
        searchTerm = '';
        currentCategory = 'all';
        currentSort = 'recent';

        const searchInput = document.getElementById('favorites-search');
        if (searchInput) {
            searchInput.value = '';
        }

        const sortSelect = document.getElementById('sortSelect');
        if (sortSelect) {
            sortSelect.value = 'recent';
        }

        const searchClear = document.getElementById('searchClear');
        if (searchClear) {
            searchClear.style.display = 'none';
        }

        renderCategories();
        renderProducts();
    }

    function handleSort(e) {
        currentSort = e.target.value;
        renderProducts();
    }

    function switchView(view) {
        if (view === currentView) return;

        currentView = view;
        const grid = document.getElementById('favoritesGrid');
        if (grid) {
            grid.dataset.view = view;
        }

        // Actualizar botones
        document.querySelectorAll('.view-btn').forEach(btn => {
            if (btn.dataset.view === view) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // ========================================
    // CATEGORÍAS
    // ========================================
    function renderCategories() {
        const container = document.getElementById('categoriesFilter');
        if (!container) return;

        // Obtener categorías únicas
        const categories = {};
        favoritesData.forEach(product => {
            const cat = (product.categoria || 'Sin categoría').toLowerCase();
            if (!categories[cat]) {
                categories[cat] = {
                    name: product.categoria || 'Sin categoría',
                    count: 0
                };
            }
            categories[cat].count++;
        });

        const categoriesArray = [
            { id: 'all', name: 'Todos', count: favoritesData.length },
            ...Object.entries(categories).map(([id, data]) => ({
                id,
                name: data.name,
                count: data.count
            }))
        ];

        container.innerHTML = categoriesArray.map(cat => `
            <button 
                class="category-chip ${currentCategory === cat.id ? 'active' : ''}"
                data-category="${cat.id}"
            >
                <span>${escapeHtml(cat.name)}</span>
                <span class="category-chip-count">${cat.count}</span>
            </button>
        `).join('');

        // Event listeners
        container.querySelectorAll('.category-chip').forEach(chip => {
            chip.addEventListener('click', () => {
                currentCategory = chip.dataset.category;
                renderCategories();
                renderProducts();
            });
        });
    }

    // ========================================
    // RENDERIZADO DE PRODUCTOS
    // ========================================
    function renderProducts() {
        const grid = document.getElementById('favoritesGrid');
        const emptySearch = document.getElementById('emptySearchState');
        
        if (!grid) return;

        // Filtrar productos
        let filtered = [...favoritesData];

        // Filtro por categoría
        if (currentCategory !== 'all') {
            filtered = filtered.filter(p => {
                const cat = (p.categoria || 'Sin categoría').toLowerCase();
                return cat === currentCategory;
            });
        }

        // Filtro por búsqueda
        if (searchTerm) {
            filtered = filtered.filter(p => {
                const name = (p.nombre || '').toLowerCase();
                const category = (p.categoria || '').toLowerCase();
                return name.includes(searchTerm) || category.includes(searchTerm);
            });
        }

        // Ordenar
        filtered = sortProducts(filtered, currentSort);

        // Mostrar/ocultar estados vacíos
        if (filtered.length === 0) {
            grid.style.display = 'none';
            if (emptySearch) {
                emptySearch.style.display = 'block';
            }
            return;
        }

        grid.style.display = 'grid';
        if (emptySearch) {
            emptySearch.style.display = 'none';
        }

        // Renderizar cards
        grid.innerHTML = filtered.map(product => createProductCard(product)).join('');

        // Animar cards
        animateCards();
    }

    function createProductCard(product) {
        const id = parseInt(product.id || product.producto_id || 0, 10);
        if (!id) return '';

        const imagen = product.imagen || `${BASE}images/default.png`;
        const nombre = escapeHtml(product.nombre || 'Producto sin nombre');
        const categoria = escapeHtml(product.categoria || 'Sin categoría');
        const precio = parseFloat(product.precio || 0);
        const precioOriginal = product.precio_original ? parseFloat(product.precio_original) : null;
        const stock = parseInt(product.stock || product.stock_real || 0, 10);
        const descuento = calcularDescuento(precio, precioOriginal);

        const isOutOfStock = stock <= 0;
        const isLowStock = stock > 0 && stock < 10;

        return `
            <article class="favorite-card" 
                data-id="${id}" 
                data-category="${categoria.toLowerCase()}" 
                data-name="${nombre.toLowerCase()}" 
                data-price="${precio}" 
                data-stock="${stock}">
                <div class="card-image-container">
                    <a href="${BASE}views/productos-detal.php?id=${id}" class="card-image-link">
                        <div class="card-image" style="background-image: url('${escapeHtml(imagen)}');">
                            <div class="image-overlay"></div>
                        </div>
                    </a>
                    
                    <div class="card-badges">
                        ${descuento > 0 ? `
                            <span class="badge badge-discount">
                                <i class="fas fa-tag"></i>
                                <span>-${descuento}%</span>
                            </span>
                        ` : ''}
                        ${isOutOfStock ? `
                            <span class="badge badge-out">
                                <i class="fas fa-times-circle"></i>
                                <span>Agotado</span>
                            </span>
                        ` : isLowStock ? `
                            <span class="badge badge-low-stock">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Últimos ${stock}</span>
                            </span>
                        ` : ''}
                    </div>

                    <div class="card-actions">
                        <button 
                            class="action-btn action-remove" 
                            data-product-id="${id}"
                            title="Eliminar de favoritos"
                            aria-label="Eliminar de favoritos"
                        >
                            <i class="fas fa-heart-broken"></i>
                        </button>
                        <button 
                            class="action-btn action-cart ${isOutOfStock ? 'disabled' : ''}" 
                            data-product-id="${id}"
                            title="Agregar al carrito"
                            aria-label="Agregar al carrito"
                            ${isOutOfStock ? 'disabled' : ''}
                        >
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                        <a 
                            href="${BASE}views/productos-detal.php?id=${id}" 
                            class="action-btn action-view"
                            title="Ver detalles"
                            aria-label="Ver detalles del producto"
                        >
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </div>
                
                <div class="card-content">
                    <div class="card-meta">
                        <span class="card-category">${categoria}</span>
                        <span class="card-stock ${isOutOfStock ? 'out-stock' : 'in-stock'}">
                            <i class="fas fa-${isOutOfStock ? 'times-circle' : 'check-circle'}"></i>
                            <span>${isOutOfStock ? 'Agotado' : 'Disponible'}</span>
                        </span>
                    </div>
                    
                    <h3 class="card-title">
                        <a href="${BASE}views/productos-detal.php?id=${id}">
                            ${nombre}
                        </a>
                    </h3>
                    
                    <div class="card-footer">
                        <div class="card-price">
                            <span class="price-current">$${precio.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            ${precioOriginal && precioOriginal > precio ? `
                                <span class="price-original">$${precioOriginal.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span>
                            ` : ''}
                        </div>
                        <button 
                            class="card-add-btn ${isOutOfStock ? 'disabled' : ''}" 
                            data-product-id="${id}"
                            ${isOutOfStock ? 'disabled' : ''}
                        >
                            <i class="fas fa-shopping-cart"></i>
                            <span>Agregar</span>
                        </button>
                    </div>
                </div>
            </article>
        `;
    }

    function sortProducts(products, sortBy) {
        const sorted = [...products];
        
        switch (sortBy) {
            case 'price-low':
                return sorted.sort((a, b) => {
                    const priceA = parseFloat(a.precio || 0);
                    const priceB = parseFloat(b.precio || 0);
                    return priceA - priceB;
                });
            
            case 'price-high':
                return sorted.sort((a, b) => {
                    const priceA = parseFloat(a.precio || 0);
                    const priceB = parseFloat(b.precio || 0);
                    return priceB - priceA;
                });
            
            case 'name-asc':
                return sorted.sort((a, b) => {
                    const nameA = (a.nombre || '').toLowerCase();
                    const nameB = (b.nombre || '').toLowerCase();
                    return nameA.localeCompare(nameB);
                });
            
            case 'name-desc':
                return sorted.sort((a, b) => {
                    const nameA = (a.nombre || '').toLowerCase();
                    const nameB = (b.nombre || '').toLowerCase();
                    return nameB.localeCompare(nameA);
                });
            
            case 'oldest':
                return sorted.reverse();
            
            case 'recent':
            default:
                return sorted;
        }
    }

    function calcularDescuento(precio, precioOriginal) {
        if (!precioOriginal || precioOriginal <= precio) return 0;
        return Math.round(((precioOriginal - precio) / precioOriginal) * 100);
    }

    // ========================================
    // FAVORITOS
    // ========================================
    async function removeFavorite(productId) {
        if (isProcessing) return;
        if (!USER_ID || USER_ID <= 0) {
            showToast('Debes iniciar sesión para gestionar favoritos', 'warning');
            setTimeout(() => {
                window.location.href = `${BASE}views/login.php?next=${encodeURIComponent(window.location.pathname)}`;
            }, 1500);
            return;
        }

        isProcessing = true;

        try {
            const response = await fetch(FAVORITES_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ producto_id: productId })
            });

            if (response.status === 401) {
                showToast('Debes iniciar sesión para gestionar favoritos', 'warning');
                setTimeout(() => {
                    window.location.href = `${BASE}views/login.php?next=${encodeURIComponent(window.location.pathname)}`;
                }, 1500);
                return;
            }

            const data = await response.json();

            if (!data.ok) {
                throw new Error(data.msg || 'Error al eliminar de favoritos');
            }

            // Remover del array local
            favoritesData = favoritesData.filter(p => {
                const id = parseInt(p.id || p.producto_id || 0, 10);
                return id !== productId;
            });

            // Animar eliminación
            const card = document.querySelector(`.favorite-card[data-id="${productId}"]`);
            if (card) {
                card.style.transition = 'all 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
                card.style.transform = 'scale(0.8) translateY(20px)';
                card.style.opacity = '0';
                
                setTimeout(() => {
                    renderCategories();
                    renderProducts();
                    updateFavoritesCount(data.count);
                }, 400);
            } else {
                renderCategories();
                renderProducts();
                updateFavoritesCount(data.count);
            }

            showToast('Producto eliminado de favoritos', 'success');

            // Si no quedan favoritos, recargar página
            if (favoritesData.length === 0) {
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }

        } catch (error) {
            console.error('Error al eliminar favorito:', error);
            showToast(error.message || 'Error al eliminar de favoritos', 'error');
        } finally {
            isProcessing = false;
        }
    }

    async function handleClearAll() {
        if (favoritesData.length === 0) return;

        showModal(
            'Eliminar todos los favoritos',
            `¿Estás seguro de que deseas eliminar los ${favoritesData.length} productos de tus favoritos? Esta acción no se puede deshacer.`,
            async () => {
                isProcessing = true;
                const total = favoritesData.length;
                let removed = 0;

                for (const product of [...favoritesData]) {
                    const id = parseInt(product.id || product.producto_id || 0, 10);
                    if (id > 0) {
                        try {
                            await removeFavorite(id);
                            removed++;
                        } catch (e) {
                            console.error('Error eliminando favorito:', e);
                        }
                    }
                }

                if (removed === total) {
                    showToast('Todos los favoritos han sido eliminados', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast(`Se eliminaron ${removed} de ${total} favoritos`, 'warning');
                }

                isProcessing = false;
            }
        );
    }

    // ========================================
    // CARRITO
    // ========================================
    async function addToCart(productId, button) {
        if (isProcessing) return;

        const product = favoritesData.find(p => {
            const id = parseInt(p.id || p.producto_id || 0, 10);
            return id === productId;
        });

        if (!product) {
            showToast('Producto no encontrado', 'error');
            return;
        }

        const stock = parseInt(product.stock || product.stock_real || 0, 10);
        if (stock <= 0) {
            showToast('Producto agotado', 'warning');
            return;
        }

        isProcessing = true;

        // Feedback visual
        if (button) {
            const originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.classList.add('loading');
        }

        try {
            const response = await fetch(CART_ENDPOINT, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    producto_id: productId,
                    cantidad: 1
                })
            });

            const data = await response.json();

            if (!response.ok || !data.ok) {
                throw new Error(data.msg || 'Error al agregar al carrito');
            }

            // Feedback de éxito
            if (button) {
                button.classList.remove('loading');
                button.classList.add('success');
                button.innerHTML = '<i class="fas fa-check"></i> <span>Agregado</span>';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.classList.remove('success');
                    button.disabled = false;
                }, 2000);
            }

            showToast('Producto agregado al carrito', 'success');
            refreshCartCount();

            // Evento personalizado
            window.dispatchEvent(new CustomEvent('cart:updated', {
                detail: { productId, count: data.count }
            }));

        } catch (error) {
            console.error('Error al agregar al carrito:', error);
            showToast(error.message || 'Error al agregar al carrito', 'error');
            
            if (button) {
                button.classList.remove('loading');
                button.disabled = false;
                button.innerHTML = button.innerHTML.includes('Agregar') 
                    ? '<i class="fas fa-shopping-cart"></i> <span>Agregar</span>'
                    : '<i class="fas fa-shopping-cart"></i>';
            }
        } finally {
            isProcessing = false;
        }
    }

    async function refreshCartCount() {
        try {
            const response = await fetch(CART_COUNT_ENDPOINT, {
                cache: 'no-store'
            });
            const data = await response.json();
            updateCartBadge(parseInt(data.count || 0, 10));
        } catch (error) {
            console.warn('No se pudo actualizar el contador del carrito:', error);
        }
    }

    function updateCartBadge(count) {
        const badge = document.getElementById('cart-badge') || document.getElementById('fav-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    // ========================================
    // UI HELPERS
    // ========================================
    function updateFavoritesCount(count) {
        if (typeof count === 'undefined') {
            count = favoritesData.length;
        }

        const countElements = document.querySelectorAll('.toolbar-count, .stat-number, #favorites-count');
        countElements.forEach(el => {
            if (el) {
                el.textContent = count;
            }
        });

        // Actualizar badge en header si existe
        const headerBadge = document.getElementById('fav-badge');
        if (headerBadge) {
            headerBadge.textContent = count;
            headerBadge.style.display = count > 0 ? 'flex' : 'none';
        }
    }

    function animateCards() {
        const cards = document.querySelectorAll('.favorite-card:not(.visible)');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.classList.add('visible');
            }, index * 50);
        });
    }

    // ========================================
    // TOAST NOTIFICATIONS
    // ========================================
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle'
        };

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="fas ${icons[type] || icons.success}"></i>
            </div>
            <div class="toast-message">${escapeHtml(message)}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(toast);

        // Auto-remove después de 4 segundos
        setTimeout(() => {
            toast.style.animation = 'toastSlideOut 0.3s ease-out forwards';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }, 4000);
    }

    // ========================================
    // MODAL
    // ========================================
    function showModal(title, message, onConfirm) {
        const modal = document.getElementById('confirmModal');
        const titleEl = document.getElementById('modalTitle');
        const messageEl = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('modalConfirm');

        if (!modal || !titleEl || !messageEl || !confirmBtn) return;

        titleEl.textContent = title;
        messageEl.textContent = message;
        modal.style.display = 'flex';

        // Remover listeners anteriores y agregar nuevo
        const newConfirmBtn = confirmBtn.cloneNode(true);
        confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);

        newConfirmBtn.addEventListener('click', () => {
            closeModal();
            if (onConfirm) {
                onConfirm();
            }
        });
    }

    function closeModal() {
        const modal = document.getElementById('confirmModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // ========================================
    // UTILITIES
    // ========================================
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // ========================================
    // EXPORTAR FUNCIONES GLOBALES (para compatibilidad)
    // ========================================
    window.toggleFavorito = function(productId, button, removeCard) {
        removeFavorite(productId);
    };

    window.agregarAlCarrito = function(productId) {
        const button = document.querySelector(`[data-product-id="${productId}"].action-cart, [data-product-id="${productId}"].card-add-btn`);
        addToCart(productId, button);
    };

})();
