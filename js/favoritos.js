const BASE = window.BASE_URL || '/';
const FAVORITES_ENDPOINT = window.FAVORITES_ENDPOINT || `${BASE}api/wishlist/toggle.php`;
const LOGIN_URL = window.LOGIN_URL || `${BASE}views/login.php?next=${encodeURIComponent(window.location.pathname + window.location.search)}`;
const USER_LOGGED = Boolean(window.USER_LOGGED);

const normalizeProduct = (product = {}) => {
    const price = Number(product.price ?? product.precio ?? 0);
    let originalPrice = product.originalPrice ?? product.precio_original ?? null;
    originalPrice = originalPrice !== null ? Number(originalPrice) : null;
    let discount = Number(product.discount ?? product.descuento ?? 0);
    if (!discount && originalPrice && originalPrice > price) {
        discount = Math.round(((originalPrice - price) / originalPrice) * 100);
    }
    const stock = Number(product.stock ?? product.stock_real ?? 0);
    const addedAt = product.added_at || product.agregado_en || null;

    return {
        id: Number(product.id),
        name: product.name || product.nombre || 'Producto sin nombre',
        category: product.category || product.categoria || 'Otros',
        price,
        originalPrice,
        discount,
        image: product.image || product.imagen || `${BASE}images/default.png`,
        stock,
        rating: Number(product.rating ?? 4.8),
        reviews: Number(product.reviews ?? 0),
        description: product.description || product.descripcion || '',
        addedAt,
    };
};

let products = (window.FAVORITES_DATA || []).map(normalizeProduct);
let favorites = products.map(product => product.id);
const exploreProducts = (window.EXPLORE_DATA || []).map(normalizeProduct);

let cart = [];
let searchTerm = '';
let selectedCategory = 'all';
let sortBy = 'recent';

document.addEventListener('DOMContentLoaded', () => {
    loadCartFromStorage();
    setupEventListeners();
    renderCategories();
    renderProducts();
    renderExploreProducts();
    updateUI();
});

function loadCartFromStorage() {
    const savedCart = localStorage.getItem('luminarias_cart');
    if (savedCart) {
        try {
            cart = JSON.parse(savedCart);
        } catch (error) {
            cart = [];
        }
    }
}

function saveCart() {
    localStorage.setItem('luminarias_cart', JSON.stringify(cart));
}


function setupEventListeners() {
    const searchInput = document.getElementById('searchInput');
    const sortSelect = document.getElementById('sortSelect');

    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            searchTerm = e.target.value.toLowerCase();
            renderProducts();
        });
    }

    if (sortSelect) {
        sortSelect.addEventListener('change', (e) => {
            sortBy = e.target.value;
            renderProducts();
        });
    }
}

function normalizeCategoryValue(value) {
    return (value || 'otros').toString().trim().toLowerCase();
}


function showNotification(message) {
    const notification = document.getElementById('notification');
    const messageEl = document.getElementById('notificationMessage');
    if (!notification || !messageEl) return;

    messageEl.textContent = message;
    notification.classList.remove('hidden');

    setTimeout(() => {
        notification.classList.add('hidden');
    }, 3000);
}


async function toggleFavorite(productId) {
    if (!USER_LOGGED) {
        window.location.href = LOGIN_URL;
        return;
    }

    try {
        const response = await fetch(FAVORITES_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: productId })
        });

        if (response.status === 401) {
            window.location.href = LOGIN_URL;
            return;
        }

        const data = await response.json();
        if (!data.ok) {
            throw new Error(data.msg || 'No se pudo actualizar');
        }

        const isFavorite = Boolean(data.in_wishlist);
        if (isFavorite) {
            if (!favorites.includes(productId)) {
                favorites.push(productId);
            }
            if (!products.some(p => p.id === productId)) {
                const candidate = getProductData(productId);
                if (candidate) {
                    products.push(candidate);
                }
            }
            showNotification('¡Agregado a favoritos!');
        } else {
            favorites = favorites.filter(id => id !== productId);
            products = products.filter(p => p.id !== productId);
            showNotification('Eliminado de favoritos');
        }

        renderProducts();
        renderExploreProducts();
        updateUI();
        window.dispatchEvent(new CustomEvent('wishlist:updated'));
    } catch (error) {
        console.error('Error al actualizar favoritos:', error);
        showNotification('No se pudo actualizar tus favoritos');
    }
}

function removeFromFavorites(productId) {
    toggleFavorite(productId);
}

function getProductData(productId) {
    return products.find(p => p.id === productId) || exploreProducts.find(p => p.id === productId) || null;
}

function addToCart(productId) {
    const product = getProductData(productId);
    if (!product) {
        showNotification('Producto no disponible');
        return;
    }

    if (product.stock === 0) {
        showNotification('Producto agotado');
        return;
    }

    const cartItem = cart.find(item => item.id === productId);
    if (cartItem) {
        cartItem.quantity += 1;
    } else {
        cart.push({ id: productId, quantity: 1 });
    }

    saveCart();
    showNotification('¡Agregado al carrito!');
    updateUI();
}


function renderCategories() {
    const categoriesContainer = document.getElementById('categories');
    if (!categoriesContainer) return;

    const categoryMap = products.reduce((acc, product) => {
        const slug = normalizeCategoryValue(product.category);
        if (!acc[slug]) {
            acc[slug] = {
                id: slug,
                name: product.category || 'Otros',
                count: 0
            };
        }
        acc[slug].count += 1;
        return acc;
    }, {});

    const categories = [{
        id: 'all',
        name: 'Todos',
        count: products.length
    }, ...Object.values(categoryMap)];

    categoriesContainer.innerHTML = categories.map(cat => `
        <button 
            class="category-btn ${selectedCategory === cat.id ? 'active' : ''}"
            onclick="selectCategory('${cat.id}')"
        >
            ${cat.name} (${cat.count})
        </button>
    `).join('');
}

function selectCategory(category) {
    selectedCategory = category;
    renderCategories();
    renderProducts();
}

function renderProducts() {
    const productsGrid = document.getElementById('productsGrid');
    const emptyState = document.getElementById('emptyState');
    if (!productsGrid || !emptyState) return;

    let favoriteProducts = [...products];

    if (selectedCategory !== 'all') {
        favoriteProducts = favoriteProducts.filter(p => normalizeCategoryValue(p.category) === selectedCategory);
    }

    if (searchTerm) {
        favoriteProducts = favoriteProducts.filter(p =>
            p.name.toLowerCase().includes(searchTerm) ||
            (p.description && p.description.toLowerCase().includes(searchTerm))
        );
    }

    favoriteProducts.sort((a, b) => {
        if (sortBy === 'price-low') return a.price - b.price;
        if (sortBy === 'price-high') return b.price - a.price;
        if (sortBy === 'discount') return b.discount - a.discount;
        const getRecentValue = (product) => {
            if (product.addedAt) {
                const parsed = Date.parse(product.addedAt);
                if (!Number.isNaN(parsed)) return parsed;
            }
            return product.id;
        };
        return getRecentValue(b) - getRecentValue(a);
    });

    if (favoriteProducts.length === 0) {
        productsGrid.classList.add('hidden');
        emptyState.classList.remove('hidden');
        if (favorites.length === 0) {
            emptyState.querySelector('.empty-title').textContent = 'Aún no tienes favoritos';
            emptyState.querySelector('.empty-text').textContent = 'Comienza a explorar y guarda tus luminarias favoritas';
        } else {
            emptyState.querySelector('.empty-title').textContent = 'No se encontraron productos';
            emptyState.querySelector('.empty-text').textContent = 'Intenta con otros filtros de búsqueda';
        }
        return;
    }

    productsGrid.classList.remove('hidden');
    emptyState.classList.add('hidden');

    productsGrid.innerHTML = favoriteProducts.map(product => `
        <div class="product-card">
            <div class="product-image">
                <img src="${product.image}" alt="${product.name}">
                
                <div class="badges">
                    ${(product.discount || (product.originalPrice && product.originalPrice > product.price))
                        ? `
                        <span class="badge badge-discount">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <polyline points="19 12 12 19 5 12"></polyline>
                            </svg>
                            -${product.discount}%
                        </span>
                    ` : ''}
                    ${product.stock > 0 && product.stock < 10 ? `
                        <span class="badge badge-stock">¡Últimos ${product.stock}!</span>
                    ` : ''}
                    ${product.stock === 0 ? `
                        <span class="badge badge-out">Agotado</span>
                    ` : ''}
                </div>
                
                <button class="favorite-btn" onclick="removeFromFavorites(${product.id})">
                    <svg viewBox="0 0 24 24" fill="currentColor">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </button>
            </div>
            
            <div class="product-info">
                <span class="product-category">${product.category}</span>
                <h3 class="product-name">${product.name}</h3>
                
                <div class="product-rating">
                    <div class="stars">
                        ${generateStars(product.rating)}
                    </div>
                    <span class="rating-text">${product.rating} (${product.reviews})</span>
                </div>
                
                <div class="product-price">
                    <span class="price-current">$${product.price.toLocaleString()}</span>
                    ${(product.originalPrice && product.originalPrice > product.price) ? `
                        <span class="price-original">$${product.originalPrice.toLocaleString()}</span>
                    ` : ''}
                </div>
                
                <div class="product-actions">
                    <button 
                        class="btn-add-cart" 
                        onclick="addToCart(${product.id})"
                        ${product.stock === 0 ? 'disabled' : ''}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        ${product.stock === 0 ? 'Agotado' : 'Agregar'}
                    </button>
                    <button class="btn-remove" onclick="removeFromFavorites(${product.id})">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function renderExploreProducts() {
    const exploreGrid = document.getElementById('exploreGrid');
    if (!exploreGrid) return;

    if (!exploreProducts.length) {
        exploreGrid.innerHTML = '<p class="text-center">Pronto añadiremos más recomendaciones.</p>';
        return;
    }

    exploreGrid.innerHTML = exploreProducts.map(product => `
        <div class="explore-item">
            <div class="explore-image">
                <img src="${product.image}" alt="${product.name}">
                <button 
                    class="explore-fav-btn ${favorites.includes(product.id) ? 'active' : ''}"
                    onclick="toggleFavorite(${product.id})"
                >
                    <svg viewBox="0 0 24 24" fill="${favorites.includes(product.id) ? 'currentColor' : 'none'}" stroke="currentColor" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                    </svg>
                </button>
            </div>
            <p class="explore-name">${product.name}</p>
            <p class="explore-price">$${product.price.toLocaleString()}</p>
        </div>
    `).join('');
}

function generateStars(rating) {
    const fullStars = Math.floor(rating);
    const emptyStars = 5 - fullStars;
    
    let starsHTML = '';
    
    for (let i = 0; i < fullStars; i++) {
        starsHTML += `
            <svg viewBox="0 0 24 24" fill="currentColor">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        `;
    }
    
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += `
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
            </svg>
        `;
    }
    
    return starsHTML;
}


function updateUI() {
    const favoritesCount = document.getElementById('favoritesCount');
    if (favoritesCount) {
        favoritesCount.textContent = favorites.length;
    }

    const cartCount = document.getElementById('cartCount');
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        if (totalItems > 0) {
            cartCount.textContent = totalItems;
            cartCount.classList.remove('hidden');
        } else {
            cartCount.classList.add('hidden');
        }
    }

    renderCategories();
}

function goBack() {
    const fallbackUrl = (typeof window.BASE_URL === 'string' ? window.BASE_URL : '/LumiSpace/') + 'index.php';
    showConfirmModal(
        '¿Volver a la pantalla anterior?',
        'Se cerrará la vista de favoritos y regresarás a la pantalla anterior.',
        () => {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = fallbackUrl;
            }
        }
    );
}

// Modal de Confirmación
function showConfirmModal(title, message, onConfirm) {
    const modal = document.getElementById('confirmModal');
    const modalTitle = document.getElementById('confirmModalTitle');
    const modalMessage = document.getElementById('confirmModalMessage');
    const confirmBtn = document.getElementById('confirmModalConfirm');
    const cancelBtn = document.getElementById('confirmModalCancel');
    
    modalTitle.textContent = title;
    modalMessage.textContent = message;
    
    // Remover listeners anteriores
    const newConfirmBtn = confirmBtn.cloneNode(true);
    const newCancelBtn = cancelBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
    cancelBtn.parentNode.replaceChild(newCancelBtn, cancelBtn);
    
    // Agregar nuevos listeners
    newConfirmBtn.addEventListener('click', () => {
        closeConfirmModal();
        if (onConfirm) onConfirm();
    });
    
    newCancelBtn.addEventListener('click', closeConfirmModal);
    
    // Mostrar modal
    modal.classList.add('active');
    
    // Cerrar al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeConfirmModal();
        }
    });
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.classList.remove('active');
}