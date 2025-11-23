const BASE_URL = window.BASE_URL || '/';
const API_PRODUCTS = BASE_URL + 'api/search/products.php';
const FAVORITES_ENDPOINT = (window.FAVORITES_ENDPOINT || (BASE_URL + 'api/wishlist/toggle.php'));
const USER_LOGGED = Boolean(window.USER_LOGGED);
const LOGIN_URL = window.LOGIN_URL || (BASE_URL + 'views/login.php');

const categoryListEl = document.getElementById('catalogCategories');
const productGridEl = document.getElementById('productGrid');
const resultsInfoEl = document.getElementById('resultsInfo');
const paginationEl = document.getElementById('catalogPagination');

const sortSelectEl = document.getElementById('catalogSort');
const brandSelectEl = document.getElementById('filterBrand');
const colorSelectEl = document.getElementById('filterColor');
const sizeSelectEl = document.getElementById('filterSize');
const minPriceEl = document.getElementById('filterPriceMin');
const maxPriceEl = document.getElementById('filterPriceMax');
const availabilityEl = document.getElementById('filterAvailability');
const discountOnlyEl = document.getElementById('filterDiscount');

let activeCategory = '';
let currentPage = 1;
let activeRequest = null;

function initializeCatalog() {
    if (!productGridEl) return;
    productGridEl.addEventListener('click', handleWishlistClick);
    attachFilterListeners();
    bindCategoryEvents();
    fetchProducts();
}

function attachFilterListeners() {
    if (categoryListEl) {
        categoryListEl.addEventListener('click', (e) => {
            const item = e.target.closest('[data-category]');
            if (!item) return;
            activeCategory = item.dataset.category;
            categoryListEl.querySelectorAll('.category-item').forEach(el => el.classList.remove('active'));
            item.classList.add('active');
            currentPage = 1;
            fetchProducts();
        });
    }

    [sortSelectEl, brandSelectEl, colorSelectEl, sizeSelectEl, availabilityEl].forEach(select => {
        if (!select) return;
        select.addEventListener('change', () => {
            currentPage = 1;
            fetchProducts();
        });
    });

    [minPriceEl, maxPriceEl].forEach(input => {
        if (!input) return;
        input.addEventListener('change', () => {
            currentPage = 1;
            fetchProducts();
        });
    });

    if (discountOnlyEl) {
        discountOnlyEl.addEventListener('change', () => {
            currentPage = 1;
            fetchProducts();
        });
    }
}

function bindCategoryEvents() {
    if (!categoryListEl) return;
    categoryListEl.querySelectorAll('[data-category]').forEach(item => {
        item.addEventListener('click', () => {
            activeCategory = item.dataset.category || '';
            categoryListEl.querySelectorAll('.category-item').forEach(el => el.classList.toggle('active', el === item));
            currentPage = 1;
            fetchProducts();
        });
    });
}

async function fetchProducts(page = 1) {
    const params = new URLSearchParams();
    params.set('page', page.toString());
    params.set('per_page', '12');
    params.set('sort', sortSelectEl.value);
    if (activeCategory) params.set('category', activeCategory);
    if (brandSelectEl?.value) params.set('brand', brandSelectEl.value);
    if (colorSelectEl?.value) params.set('color', colorSelectEl.value);
    if (sizeSelectEl?.value) params.set('size', sizeSelectEl.value);
    if (availabilityEl?.value) params.set('availability', availabilityEl.value);
    if (minPriceEl?.value) params.set('min_price', minPriceEl.value);
    if (maxPriceEl?.value) params.set('max_price', maxPriceEl.value);
    if (discountOnlyEl?.checked) params.set('discount_only', '1');

    setGridLoading(true);

    if (activeRequest) {
        activeRequest.abort();
    }
    const controller = new AbortController();
    activeRequest = controller;

    try {
        const resp = await fetch(`${API_PRODUCTS}?${params.toString()}`, { signal: controller.signal });
        const data = await resp.json();
        if (!data.ok) throw new Error(data.error || 'Error al cargar productos');

        renderProducts(data.results || []);
        updateFacets(data.facets || {});
        updateSummary(data.meta);
        updatePagination(data.meta);
    } catch (error) {
        if (error.name !== 'AbortError') {
            console.error(error);
            productGridEl.innerHTML = '<div class="empty-state">Ocurrió un error al cargar los productos.</div>';
        }
    }
    setGridLoading(false);
    activeRequest = null;
}

function renderProducts(products) {
    if (!products.length) {
        productGridEl.innerHTML = '<div class="empty-state">No se encontraron productos con los filtros seleccionados.</div>';
        return;
    }

    productGridEl.innerHTML = products.map(product => {
        const tags = [];
        if (product.discount && product.discount > 0) tags.push('En oferta');
        if (product.popularity && product.popularity >= 20) tags.push('Más vendido');
        if (product.created_at && isNewProduct(product.created_at)) tags.push('Nuevo');

        return `
            <article class="product-card">
                <div class="product-tags">
                    ${tags.map(tag => `<span class="product-tag">${tag}</span>`).join('')}
                </div>
                <img src="${product.image}" alt="${product.name}">
                <div class="product-name">${product.name}</div>
                <div class="product-meta">${product.brand || 'Sin marca'} • ${product.category || ''}</div>
                <div class="product-price">$${product.price.toLocaleString('es-MX')}</div>
                <div class="product-meta">${product.availability ? 'Disponible' : 'Agotado'}</div>
                <div class="product-meta">${truncateText(product.description || '', 90)}</div>
                <div class="product-cta">
                    <button
                        class="catalog-wishlist-btn ${product.in_wishlist ? 'active' : ''}"
                        data-product-id="${product.id}"
                        aria-pressed="${product.in_wishlist ? 'true' : 'false'}"
                    >
                        <i class="${product.in_wishlist ? 'fas' : 'far'} fa-heart"></i>
                        <span>${product.in_wishlist ? 'Guardado' : 'Favorito'}</span>
                    </button>
                    <a class="product-detail-btn" href="${BASE_URL + 'views/productos-detal.php?id=' + product.id}">Ver detalle</a>
                </div>
            </article>
        `;
    }).join('');
}

function updateFacets(facets) {
    updateSelectOptions(brandSelectEl, facets.brands);
    updateSelectOptions(colorSelectEl, facets.colors);
    updateSelectOptions(sizeSelectEl, facets.sizes);

    if (facets.price) {
        if (minPriceEl && !minPriceEl.value) {
            minPriceEl.placeholder = `Desde $${Math.round(facets.price.min || 0)}`;
        }
        if (maxPriceEl && !maxPriceEl.value) {
            maxPriceEl.placeholder = `Hasta $${Math.round(facets.price.max || 0)}`;
        }
    }

    if (categoryListEl && facets.categories) {
        const entries = Object.entries(facets.categories);
        categoryListEl.innerHTML = `
            <div class="category-item ${activeCategory === '' ? 'active' : ''}" data-category="">
                <span>Todas</span>
                <small>${entries.reduce((sum, [, count]) => sum + count, 0)}</small>
            </div>
            ${entries.map(([name, count]) => `
                <div class="category-item ${activeCategory === name ? 'active' : ''}" data-category="${name}">
                    <span>${name}</span>
                    <small>${count}</small>
                </div>
            `).join('')}
        `;
        bindCategoryEvents();
    }
}

function updateSelectOptions(selectEl, data) {
    if (!selectEl || !data) return;
    const current = selectEl.value;
    const defaultLabel = selectEl.dataset.placeholder || 'Todos';
    const sorted = Object.entries(data).sort((a, b) => a[0].localeCompare(b[0], 'es'));
    selectEl.innerHTML = `<option value="">${defaultLabel}</option>` +
        sorted.map(([name, count]) => `<option value="${name}">${name} (${count})</option>`).join('');
    if (current) selectEl.value = current;
}

function updateSummary(meta = {}) {
    if (!resultsInfoEl) return;
    const total = meta.total || 0;
    const page = meta.page || 1;
    const totalPages = meta.total_pages || 1;
    resultsInfoEl.textContent = `${total} productos • Página ${page} de ${totalPages}`;
}

function updatePagination(meta = {}) {
    if (!paginationEl) return;
    const totalPages = meta.total_pages || 1;
    const page = meta.page || 1;
    paginationEl.innerHTML = '';

    if (totalPages <= 1) return;

    const createBtn = (label, disabled, handler) => {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.disabled = disabled;
        btn.addEventListener('click', handler);
        return btn;
    };

    paginationEl.appendChild(createBtn('Anterior', page <= 1, () => {
        if (page > 1) {
            currentPage = page - 1;
            fetchProducts(currentPage);
        }
    }));

    paginationEl.appendChild(createBtn('Siguiente', page >= totalPages, () => {
        if (page < totalPages) {
            currentPage = page + 1;
            fetchProducts(currentPage);
        }
    }));
}

function isNewProduct(dateStr) {
    const created = new Date(dateStr);
    if (Number.isNaN(created)) return false;
    const diff = Date.now() - created.getTime();
    return diff < 1000 * 60 * 60 * 24 * 30; // 30 días
}

function truncateText(text, maxLength) {
    if (!text) return '';
    return text.length > maxLength ? `${text.slice(0, maxLength)}…` : text;
}

function setGridLoading(state) {
    if (!productGridEl) return;
    productGridEl.classList.toggle('loading', state);
}

function handleWishlistClick(event) {
    const btn = event.target.closest('.catalog-wishlist-btn');
    if (!btn) return;
    event.preventDefault();
    event.stopPropagation();

    if (!USER_LOGGED) {
        showCatalogToast('Debes iniciar sesión para guardar favoritos', 'warning');
        const next = encodeURIComponent(window.location.pathname + window.location.search);
        setTimeout(() => {
            window.location.href = `${LOGIN_URL}?next=${next}`;
        }, 1000);
        return;
    }

    if (btn.dataset.loading === '1') return;
    const productId = parseInt(btn.dataset.productId, 10);
    if (!productId) return;

    toggleWishlist(btn, productId);
}

async function toggleWishlist(button, productId) {
    const icon = button.querySelector('i');
    const label = button.querySelector('span');
    const previousIcon = icon.className;
    button.dataset.loading = '1';
    button.disabled = true;
    icon.className = 'fas fa-spinner fa-spin';

    try {
        const response = await fetch(FAVORITES_ENDPOINT, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ producto_id: productId })
        });

        if (response.status === 401) {
            showCatalogToast('Tu sesión expiró, inicia sesión nuevamente', 'warning');
            setTimeout(() => {
                const next = encodeURIComponent(window.location.pathname + window.location.search);
                window.location.href = `${LOGIN_URL}?next=${next}`;
            }, 1000);
            return;
        }

        const data = await response.json();
        if (!data.ok) {
            throw new Error(data.msg || 'No se pudo actualizar favoritos');
        }

        updateWishlistButton(button, data.in_wishlist);
        showCatalogToast(data.in_wishlist ? 'Agregado a favoritos' : 'Eliminado de favoritos', 'success');
        window.dispatchEvent(new CustomEvent('wishlist:updated'));
    } catch (error) {
        console.error(error);
        icon.className = previousIcon;
        showCatalogToast('Error al actualizar favoritos', 'error');
    } finally {
        button.disabled = false;
        button.dataset.loading = '0';
    }
}

function updateWishlistButton(button, isActive) {
    button.classList.toggle('active', isActive);
    button.setAttribute('aria-pressed', String(isActive));
    const icon = button.querySelector('i');
    const label = button.querySelector('span');
    if (icon && label) {
        icon.className = `${isActive ? 'fas' : 'far'} fa-heart`;
        label.textContent = isActive ? 'Guardado' : 'Favorito';
    }
}

function showCatalogToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `catalog-toast ${type}`;
    toast.innerHTML = `<i class="fas fa-heart"></i><span>${message}</span>`;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 300);
    }, 2200);
}

initializeCatalog();

