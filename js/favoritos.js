const products = [
    {
        id: 1,
        name: "Lámpara Colgante Moderna Oslo",
        category: "colgantes",
        price: 2499,
        originalPrice: 3299,
        image: "",
        stock: 15,
        discount: 24,
        rating: 4.8,
        reviews: 234
    },
    {
        id: 2,
        name: "Lámpara de Mesa Escandinava",
        category: "mesa",
        price: 1299,
        originalPrice: 1599,
        image: "",
        stock: 8,
        discount: 19,
        rating: 4.9,
        reviews: 456
    },
    {
        id: 3,
        name: "Lámpara Colgante",
        category: "Lámpara Led",
        price: 899,
        originalPrice: 1199,
        image: "https://i.pinimg.com/1200x/ad/88/d4/ad88d4d70d66f18d89c39efea80eb8bb.jpg",
        stock: 0,
        discount: 25,
        rating: 4.6,
        reviews: 189
    },
    {
        id: 4,
        name: "Lámpara de Techo Colgante",
        category: "exterior",
        price: 3199,
        originalPrice: 3199,
        image: "https://i.pinimg.com/736x/d0/3d/07/d03d078576def96d170dbc43572adcb1.jpg",
        stock: 23,
        discount: 0,
        rating: 4.7,
        reviews: 312
    },
    {
        id: 5,
        name: "Lámpara Colgante Cristal Bohemia",
        category: "colgantes",
        price: 4599,
        originalPrice: 5999,
        image: "",
        stock: 3,
        discount: 23,
        rating: 5.0,
        reviews: 567
    },
    {
        id: 6,
        name: "Lámpara Mesa Smart RGB",
        category: "mesa",
        price: 1899,
        originalPrice: 2299,
        image: "",
        stock: 42,
        discount: 17,
        rating: 4.5,
        reviews: 891
    }
];

let favorites = [];
let cart = [];
let searchTerm = '';
let selectedCategory = 'all';
let sortBy = 'recent';


document.addEventListener('DOMContentLoaded', function() {
    loadFromLocalStorage();
    setupEventListeners();
    renderCategories();
    renderProducts();
    renderExploreProducts();
    updateUI();
});

function loadFromLocalStorage() {
    const savedFavorites = localStorage.getItem('luminarias_favorites');
    const savedCart = localStorage.getItem('luminarias_cart');
    
    if (savedFavorites) {
        favorites = JSON.parse(savedFavorites);
    }
    
    if (savedCart) {
        cart = JSON.parse(savedCart);
    }
}

function saveFavorites() {
    localStorage.setItem('luminarias_favorites', JSON.stringify(favorites));
}

function saveCart() {
    localStorage.setItem('luminarias_cart', JSON.stringify(cart));
}


function setupEventListeners() {
    
    document.getElementById('searchInput').addEventListener('input', function(e) {
        searchTerm = e.target.value.toLowerCase();
        renderProducts();
    });

    
    document.getElementById('sortSelect').addEventListener('change', function(e) {
        sortBy = e.target.value;
        renderProducts();
    });
}


function showNotification(message) {
    const notification = document.getElementById('notification');
    const messageEl = document.getElementById('notificationMessage');
    
    messageEl.textContent = message;
    notification.classList.remove('hidden');
    
    setTimeout(() => {
        notification.classList.add('hidden');
    }, 3000);
}


function openShareModal() {
    document.getElementById('shareModal').classList.remove('hidden');
}

function closeShareModal() {
    document.getElementById('shareModal').classList.add('hidden');
}


function toggleFavorite(productId) {
    const index = favorites.indexOf(productId);
    
    if (index > -1) {
        favorites.splice(index, 1);
        showNotification('Eliminado de favoritos');
    } else {
        favorites.push(productId);
        showNotification('¡Agregado a favoritos!');
    }
    
    saveFavorites();
    renderProducts();
    renderExploreProducts();
    updateUI();
}

function removeFromFavorites(productId) {
    favorites = favorites.filter(id => id !== productId);
    saveFavorites();
    showNotification('Eliminado de favoritos');
    renderProducts();
    renderExploreProducts();
    updateUI();
}


function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    
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
    const favoriteProducts = products.filter(p => favorites.includes(p.id));
    
    const categories = [
        { id: 'all', name: 'Todos', count: favoriteProducts.length },
        { id: 'colgantes', name: 'Colgantes', count: favoriteProducts.filter(p => p.category === 'colgantes').length },
        { id: 'mesa', name: 'Mesa', count: favoriteProducts.filter(p => p.category === 'mesa').length },
        { id: 'pared', name: 'Pared', count: favoriteProducts.filter(p => p.category === 'pared').length },
        { id: 'exterior', name: 'Exterior', count: favoriteProducts.filter(p => p.category === 'exterior').length }
    ];
    
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
    
    let favoriteProducts = products.filter(p => favorites.includes(p.id));
    
    
    if (selectedCategory !== 'all') {
        favoriteProducts = favoriteProducts.filter(p => p.category === selectedCategory);
    }
    
    if (searchTerm) {
        favoriteProducts = favoriteProducts.filter(p => 
            p.name.toLowerCase().includes(searchTerm)
        );
    }
    
    
    favoriteProducts.sort((a, b) => {
        if (sortBy === 'price-low') return a.price - b.price;
        if (sortBy === 'price-high') return b.price - a.price;
        if (sortBy === 'discount') return b.discount - a.discount;
        return 0;
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
                    ${product.discount > 0 ? `
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
                    ${product.discount > 0 ? `
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
    
    exploreGrid.innerHTML = products.map(product => `
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
    favoritesCount.textContent = favorites.length;
    
    
    const cartCount = document.getElementById('cartCount');
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    
    if (totalItems > 0) {
        cartCount.textContent = totalItems;
        cartCount.classList.remove('hidden');
    } else {
        cartCount.classList.add('hidden');
    }
    
    renderCategories();
}