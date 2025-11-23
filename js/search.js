const API_SEARCH = (window.BASE_URL || '/') + 'api/search/products.php';
const API_SUGGESTIONS = (window.BASE_URL || '/') + 'api/search/suggestions.php';

const searchInput = document.getElementById('searchInput');
const suggestionsList = document.getElementById('searchSuggestions');
const resultsGrid = document.getElementById('resultsGrid');
const resultsSummary = document.getElementById('resultsSummary');
const sortSelect = document.getElementById('sortSelect');
const filtersForm = document.getElementById('filtersForm');
const paginationContainer = document.getElementById('pagination');
const filterBadgesContainer = document.getElementById('filterBadges');
const searchButton = document.getElementById('searchSubmit');

const filterInputs = {
    category: document.getElementById('filterCategory'),
    brand: document.getElementById('filterBrand'),
    color: document.getElementById('filterColor'),
    size: document.getElementById('filterSize'),
    availability: document.getElementById('filterAvailability'),
    min_price: document.getElementById('filterMinPrice'),
    max_price: document.getElementById('filterMaxPrice'),
};

let currentPage = 1;
let currentQuery = new URLSearchParams(window.location.search).get('q') || '';
let suggestionTimeout;

if (searchInput) {
    searchInput.value = currentQuery;
}

function debounceSuggestions(value) {
    if (suggestionTimeout) clearTimeout(suggestionTimeout);
    suggestionTimeout = setTimeout(() => fetchSuggestions(value), 180);
}

async function fetchSuggestions(term) {
    if (!term || term.trim().length < 2) {
        suggestionsList?.classList.remove('active');
        suggestionsList.innerHTML = '';
        return;
    }
    try {
        const resp = await fetch(`${API_SUGGESTIONS}?q=${encodeURIComponent(term.trim())}`);
        const data = await resp.json();
        if (data.ok && Array.isArray(data.suggestions) && suggestionsList) {
            if (!data.suggestions.length) {
                suggestionsList.classList.remove('active');
                suggestionsList.innerHTML = '';
                return;
            }
            suggestionsList.innerHTML = data.suggestions
                .map(item => `<li data-value="${item}">${item}</li>`)
                .join('');
            suggestionsList.classList.add('active');
        }
    } catch (error) {
        console.error('Suggestions error', error);
    }
}

async function fetchResults(page = 1) {
    const params = new URLSearchParams();
    params.set('page', page.toString());
    params.set('q', searchInput.value.trim());
    params.set('sort', sortSelect.value);

    Object.entries(filterInputs).forEach(([key, input]) => {
        if (!input) return;
        const value = input.value.trim();
        if (value !== '') params.set(key, value);
    });

    try {
        const resp = await fetch(`${API_SEARCH}?${params.toString()}`);
        const data = await resp.json();
        if (data.ok) {
            renderResults(data.results || []);
            updateSummary(data.meta);
            updatePagination(data.meta);
            updateFacets(data.facets || {});
            renderBadges();
        }
    } catch (error) {
        console.error('Search error', error);
        renderResults([]);
        updateSummary({ total: 0, page: 1, per_page: 12, total_pages: 1 });
    }
}

function renderResults(list) {
    if (!resultsGrid) return;
    if (!list.length) {
        resultsGrid.innerHTML = `<p class="empty-results">No se encontraron productos.</p>`;
        return;
    }

    resultsGrid.innerHTML = list.map(product => `
        <article class="search-card-product">
            <img src="${product.image}" alt="${product.name}">
            <div class="product-meta">${product.category || 'Otros'} ${product.brand ? `• ${product.brand}` : ''}</div>
            <h3>${product.name}</h3>
            <div class="product-price">$${product.price.toLocaleString('es-MX')}</div>
            ${product.originalPrice ? `<div class="product-meta">Antes: $${product.originalPrice.toLocaleString('es-MX')}</div>` : ''}
            <div class="product-meta">${product.availability ? 'Disponible' : 'Agotado'} ${product.color ? `• ${product.color}` : ''}</div>
        </article>
    `).join('');
}

function updateSummary(meta = {}) {
    if (!resultsSummary) return;
    const total = meta.total || 0;
    const page = meta.page || 1;
    const totalPages = meta.total_pages || 1;
    resultsSummary.textContent = `${total} resultados encontrados • Página ${page} de ${totalPages}`;
}

function updatePagination(meta = {}) {
    if (!paginationContainer) return;
    const totalPages = meta.total_pages || 1;
    const current = meta.page || 1;
    paginationContainer.innerHTML = '';

    if (totalPages <= 1) return;

    const prev = document.createElement('button');
    prev.textContent = 'Anterior';
    prev.disabled = current <= 1;
    prev.addEventListener('click', () => {
        if (current > 1) {
            currentPage = current - 1;
            fetchResults(currentPage);
        }
    });

    const next = document.createElement('button');
    next.textContent = 'Siguiente';
    next.disabled = current >= totalPages;
    next.addEventListener('click', () => {
        if (current < totalPages) {
            currentPage = current + 1;
            fetchResults(currentPage);
        }
    });

    paginationContainer.appendChild(prev);
    paginationContainer.appendChild(next);
}

function updateFacets(facets = {}) {
    updateSelectOptions(filterInputs.category, facets.categories);
    updateSelectOptions(filterInputs.brand, facets.brands);
    updateSelectOptions(filterInputs.color, facets.colors);
    updateSelectOptions(filterInputs.size, facets.sizes);

    if (facets.price) {
        if (filterInputs.min_price && !filterInputs.min_price.value) {
            filterInputs.min_price.placeholder = `Desde $${Math.round(facets.price.min || 0)}`;
        }
        if (filterInputs.max_price && !filterInputs.max_price.value) {
            filterInputs.max_price.placeholder = `Hasta $${Math.round(facets.price.max || 0)}`;
        }
    }
}

function updateSelectOptions(selectElement, data) {
    if (!selectElement || !data) return;
    const currentValue = selectElement.value;
    const options = ['<option value="">Todos</option>'];
    Object.entries(data).forEach(([label, count]) => {
        options.push(`<option value="${label}">${label} (${count})</option>`);
    });
    selectElement.innerHTML = options.join('');
    if (currentValue) {
        selectElement.value = currentValue;
    }
}

function renderBadges() {
    if (!filterBadgesContainer) return;
    const activeFilters = [];
    Object.entries(filterInputs).forEach(([key, input]) => {
        if (!input) return;
        const value = input.value.trim();
        if (value) {
            activeFilters.push({ key, label: `${key.replace('_', ' ')}: ${value}` });
        }
    });

    if (!activeFilters.length) {
        filterBadgesContainer.innerHTML = '<span class="filter-badge">Sin filtros activos</span>';
        return;
    }

    filterBadgesContainer.innerHTML = activeFilters.map(filter => `
        <span class="filter-badge" data-key="${filter.key}">
            ${filter.label}
            <button type="button" aria-label="Quitar filtro" data-remove="${filter.key}">×</button>
        </span>
    `).join('');

    filterBadgesContainer.querySelectorAll('button[data-remove]').forEach(btn => {
        btn.addEventListener('click', () => {
            const key = btn.getAttribute('data-remove');
            if (filterInputs[key]) {
                filterInputs[key].value = '';
                fetchResults(1);
            }
        });
    });
}

if (filtersForm) {
    filtersForm.addEventListener('change', () => {
        currentPage = 1;
        fetchResults(currentPage);
    });
    filtersForm.addEventListener('submit', (e) => {
        e.preventDefault();
        currentPage = 1;
        fetchResults(currentPage);
    });
}

if (sortSelect) {
    sortSelect.addEventListener('change', () => {
        currentPage = 1;
        fetchResults(currentPage);
    });
}

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        debounceSuggestions(e.target.value);
    });
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            suggestionsList?.classList.remove('active');
            currentPage = 1;
            fetchResults(currentPage);
        }
    });
}

if (searchButton) {
    searchButton.addEventListener('click', () => {
        suggestionsList?.classList.remove('active');
        currentPage = 1;
        fetchResults(currentPage);
    });
}

if (suggestionsList) {
    suggestionsList.addEventListener('click', (e) => {
        const target = e.target.closest('li[data-value]');
        if (!target) return;
        searchInput.value = target.dataset.value;
        suggestionsList.classList.remove('active');
        currentPage = 1;
        fetchResults(currentPage);
    });
}

document.addEventListener('click', (e) => {
    if (suggestionsList && !suggestionsList.contains(e.target) && e.target !== searchInput) {
        suggestionsList.classList.remove('active');
    }
});

// Iniciar
fetchResults(currentPage);

