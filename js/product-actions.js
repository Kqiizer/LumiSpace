/**
 * ========================================
 * PRODUCT ACTIONS - LumiSpace
 * ========================================
 * Handles add to cart and favorites for product cards
 */

(function () {
    'use strict';

    const BASE = window.BASE_URL || document.body.dataset.base || '/';
    const FAVORITE_MESSAGES = {
        added: '✔ Producto agregado a tus favoritos.',
        removed: '❌ Producto eliminado de favoritos.',
        login: 'Debes iniciar sesión para guardar productos en favoritos.'
    };

    // Toast notification
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span class="toast-message">${message}</span>
        `;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease forwards';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    // Add to cart function
    async function addToCart(productId, quantity = 1) {
        try {
            const response = await fetch(BASE + 'api/carrito/add.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    producto_id: productId,
                    product_id: productId,
                    cantidad: quantity,
                    qty: quantity
                })
            });

            const data = await response.json();

            if (response.ok && data.ok !== false) {
                showToast('✓ Producto agregado al carrito', 'success');

                // Update cart badge if exists
                updateCartBadge();
                
                // Si estamos en la página del carrito, recargar inmediatamente para mostrar el nuevo producto
                const currentPath = window.location.pathname.toLowerCase();
                const currentHref = window.location.href.toLowerCase();
                const isCartPage = currentPath.includes('carrito') || 
                                  currentHref.includes('carrito') ||
                                  currentPath.includes('includes/carrito');
                
                if (isCartPage) {
                    // Recargar inmediatamente sin delay para mostrar el nuevo producto
                    setTimeout(() => {
                        window.location.reload();
                    }, 300);
                }
                
                return true;
            } else {
                throw new Error(data.msg || 'Error al agregar al carrito');
            }
        } catch (error) {
            console.error('Cart error:', error);
            showToast('Error al agregar al carrito', 'error');
            return false;
        }
    }

    // Toggle favorites function
    async function toggleFavorite(productId, button) {
        try {
            const response = await fetch(BASE + 'api/favoritos/toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ producto_id: productId })
            });

            if (response.status === 401) {
                showToast(FAVORITE_MESSAGES.login, 'warning');
                setTimeout(() => {
                    const next = encodeURIComponent(window.location.pathname + window.location.search);
                    window.location.href = `${BASE}views/login.php?next=${next}`;
                }, 1500);
                return;
            }

            const data = await response.json();

            if (data.ok) {
                if (button) {
                    button.classList.toggle('active', data.in_wishlist);
                    const icon = button.querySelector('i');
                    if (icon) {
                        icon.className = data.in_wishlist ? 'fas fa-heart' : 'far fa-heart';
                    }
                    button.setAttribute('aria-pressed', data.in_wishlist ? 'true' : 'false');
                    button.title = data.in_wishlist ? 'Quitar de favoritos' : 'Agregar a favoritos';
                }

                showToast(
                    data.in_wishlist ? FAVORITE_MESSAGES.added : FAVORITE_MESSAGES.removed,
                    data.in_wishlist ? 'success' : 'warning'
                );

                // Update favorites badge if exists
                updateFavoritesBadge(data.count);
                
                // Si estamos en la página de favoritos y se agregó un favorito, recargar después de un delay
                const isFavoritesPage = window.location.pathname.includes('favoritos.php') || 
                                       window.location.pathname.includes('favoritos') ||
                                       window.location.href.includes('favoritos');
                
                if (isFavoritesPage && data.in_wishlist) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } else {
                throw new Error(data.msg || 'Error al actualizar favoritos');
            }
        } catch (error) {
            console.error('Favorites error:', error);
            showToast('Error al actualizar favoritos', 'error');
        }
    }

    // Update cart badge
    function updateCartBadge() {
        fetch(BASE + 'api/carrito/count.php')
            .then(res => res.json())
            .then(data => {
                const badge = document.querySelector('#cart-badge, .cart-badge');
                if (badge && data.count !== undefined) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? '' : 'none';
                }
            })
            .catch(err => console.error('Error updating cart badge:', err));
    }

    // Update favorites badge
    function updateFavoritesBadge(nextCount) {
        const badge = document.querySelector('#fav-badge, .fav-badge');
        if (badge && typeof nextCount === 'number') {
            badge.textContent = nextCount;
            badge.style.display = nextCount > 0 ? '' : 'none';
            return;
        }

        // Intentar usar el endpoint de favoritos primero, luego el de wishlist como fallback
        fetch(BASE + 'api/favoritos/count.php')
            .then(res => res.ok ? res.json() : fetch(BASE + 'api/wishlist/count.php').then(r => r.json()))
            .then(data => {
                if (badge && data.count !== undefined) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? '' : 'none';
                }
                
                // Si estamos en la página de favoritos, recargar para mostrar el nuevo producto
                const isFavoritesPage = window.location.pathname.includes('favoritos.php') || 
                                       window.location.pathname.includes('favoritos') ||
                                       window.location.href.includes('favoritos');
                
                if (isFavoritesPage && data.count > 0) {
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            })
            .catch(err => console.error('Error updating favorites badge:', err));
    }

    // Event delegation for all product action buttons
    document.addEventListener('click', async function (e) {
        // Handle add to cart buttons
        const cartBtn = e.target.closest('.js-cart');
        if (cartBtn) {
            e.preventDefault();
            e.stopPropagation();

            if (cartBtn.disabled) return;

            const card = cartBtn.closest('.product-card');
            const productId = parseInt(card?.dataset.id || cartBtn.dataset.id || '0', 10);

            if (!productId) {
                showToast('Producto no válido', 'error');
                return;
            }

            // Show loading state
            const originalHTML = cartBtn.innerHTML;
            cartBtn.disabled = true;
            cartBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

            const success = await addToCart(productId, 1);

            if (success) {
                cartBtn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    cartBtn.innerHTML = originalHTML;
                    cartBtn.disabled = false;
                }, 1000);
            } else {
                cartBtn.innerHTML = originalHTML;
                cartBtn.disabled = false;
            }
        }

        // Handle favorites buttons
        const wishBtn = e.target.closest('.js-wish');
        if (wishBtn) {
            e.preventDefault();
            e.stopPropagation();

            if (wishBtn.disabled) return;

            const card = wishBtn.closest('.product-card');
            const productId = parseInt(card?.dataset.id || wishBtn.dataset.id || '0', 10);

            if (!productId) {
                showToast('Producto no válido', 'error');
                return;
            }

            // Show loading state
            const icon = wishBtn.querySelector('i');
            const originalClass = icon?.className || '';
            wishBtn.disabled = true;
            if (icon) icon.className = 'fas fa-spinner fa-spin';

            await toggleFavorite(productId, wishBtn);

            if (icon && !icon.className.includes('fa-heart')) {
                icon.className = originalClass;
            }
            wishBtn.disabled = false;
        }
    });

    // Add toast styles if not already present
    if (!document.querySelector('#product-actions-styles')) {
        const style = document.createElement('style');
        style.id = 'product-actions-styles';
        style.textContent = `
            .toast {
                position: fixed;
                bottom: 30px;
                right: 30px;
                background: white;
                color: #333;
                padding: 16px 24px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 10000;
                animation: slideIn 0.3s ease;
                max-width: 350px;
                border-left: 4px solid #8b7355;
            }
            .toast.success { border-left-color: #22c55e; }
            .toast.error { border-left-color: #ef4444; }
            .toast.warning { border-left-color: #f59e0b; }
            .toast i { font-size: 1.2rem; color: #8b7355; }
            .toast.success i { color: #22c55e; }
            .toast.error i { color: #ef4444; }
            .toast.warning i { color: #f59e0b; }
            @keyframes slideIn {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }
            @keyframes slideOut {
                from { opacity: 1; transform: translateY(0); }
                to { opacity: 0; transform: translateY(20px); }
            }
            body.dark .toast {
                background: #262018;
                color: #f6f1e8;
            }
        `;
        document.head.appendChild(style);
    }

    console.log('✓ Product actions initialized');
})();
