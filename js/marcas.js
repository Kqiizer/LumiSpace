const API_SEARCH_PRODUCTS = (window.BASE_URL || '/') + 'api/search/products.php';

const brandGridEl = document.getElementById('brandGrid');
const featuredWrapper = document.getElementById('featuredBrands');
const brandTitle = document.getElementById('brandProductsTitle');
const brandDescription = document.getElementById('brandProductsDescription');
const brandProductsGrid = document.getElementById('brandProductsGrid');
const sortSelect = document.getElementById('brandSort');
const minPriceInput = document.getElementById('brandMinPrice');
const maxPriceInput = document.getElementById('brandMaxPrice');
const availabilitySelect = document.getElementById('brandAvailability');

const brandsData = window.BRANDS_DATA || [];
let activeBrand = null;

function initBrandsPage() {
    if (!brandGridEl) return;
    renderFeaturedBrands();
    renderBrandGrid();
    if (brandsData.length) {
        selectBrand(brandsData[0]);
    } else {
        showEmptyState('No hay marcas registradas todavía.');
    }
}

function renderFeaturedBrands() {
    if (!featuredWrapper) return;
    const featured = brandsData.filter(b => b.featured).slice(0, 3);
    if (!featured.length) {
        featuredWrapper.innerHTML = '<p>No hay marcas destacadas en este momento.</p>';
        return;
    }
    featuredWrapper.innerHTML = featured.map(brand => `
        <article class="featured-card" data-tag="${brand.campaign || 'Destacada'}">
            <img src="${brand.logo}" alt="${brand.name}">
            <h3>${brand.name}</h3>
            <p>${brand.tagline || brand.description || 'Descubre los mejores productos de esta marca.'}</p>
            <small>${brand.products} productos disponibles</small>
        </article>
    `).join('');
}

function renderBrandGrid() {
    brandGridEl.innerHTML = brandsData.map(brand => `
        <article class="brand-card" data-brand="${brand.name}">
            <img src="${brand.logo}" alt="${brand.name}">
            <div class="brand-name">${brand.name}</div>
            <div class="brand-meta">${brand.products} productos • Popularidad ${brand.popularity}</div>
        </article>
    `).join('');

    brandGridEl.querySelectorAll('.brand-card').forEach(card => {
        card.addEventListener('click', () => {
            const name = card.dataset.brand;
            const brand = brandsData.find(b => b.name === name);
            if (brand) selectBrand(brand);
        });
    });
}

function selectBrand(brand) {
    activeBrand = brand;
    brandGridEl.querySelectorAll('.brand-card').forEach(card => {
        card.classList.toggle('active', card.dataset.brand === brand.name);
    });

    if (brandTitle) {
        brandTitle.textContent = `Productos de ${brand.name}`;
    }
    if (brandDescription) {
        brandDescription.textContent = brand.description || brand.tagline || '';
    }
    fetchBrandProducts();
}

async function fetchBrandProducts() {
    if (!activeBrand) return;
    const params = new URLSearchParams();
    params.set('brand', activeBrand.name);
    params.set('sort', sortSelect.value);

    if (minPriceInput.value) params.set('min_price', minPriceInput.value);
    if (maxPriceInput.value) params.set('max_price', maxPriceInput.value);
    if (availabilitySelect.value) params.set('availability', availabilitySelect.value);

    brandProductsGrid.innerHTML = '<p>Cargando productos...</p>';

    try {
        const resp = await fetch(`${API_SEARCH_PRODUCTS}?${params.toString()}`);
        const data = await resp.json();
        if (data.ok) {
            renderBrandProducts(data.results || []);
        } else {
            showEmptyState(data.error || 'No se pudieron obtener los productos.');
        }
    } catch (error) {
        console.error('Brand products error', error);
        showEmptyState('Ocurrió un error al cargar los productos.');
    }
}

function renderBrandProducts(products) {
    if (!products.length) {
        showEmptyState('No hay productos para esta marca en este momento.');
        return;
    }

    brandProductsGrid.innerHTML = products.map(product => `
        <article class="brand-product-card">
            <img src="${product.image}" alt="${product.name}">
            <h3>${product.name}</h3>
            <div class="brand-product-price">$${product.price.toLocaleString('es-MX')}</div>
            <div class="brand-product-meta">
                ${product.availability ? 'En stock' : 'Agotado'}
                ${product.rating ? `• ⭐ ${product.rating}` : ''}
            </div>
            <div class="brand-product-meta">${product.category || ''}</div>
        </article>
    `).join('');
}

function showEmptyState(message) {
    brandProductsGrid.innerHTML = `<div class="brand-empty-state">${message}</div>`;
}

[sortSelect, minPriceInput, maxPriceInput, availabilitySelect].forEach(input => {
    if (!input) return;
    input.addEventListener('change', () => {
        fetchBrandProducts();
    });
});

initBrandsPage();

