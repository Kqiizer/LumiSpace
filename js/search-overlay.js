document.addEventListener('DOMContentLoaded', () => {
  const BASE = document.body.dataset.base || '/';
  const API_SEARCH = `${BASE}api/search/products.php`;
  const API_SUGGESTIONS = `${BASE}api/search/suggestions.php`;

  const overlay = document.getElementById('globalSearch');
  const openBtn = document.getElementById('openSearchPanel');
  const closeButtons = overlay?.querySelectorAll('[data-search-close]');
  const clearBtn = overlay?.querySelector('[data-search-clear]');
  const input = document.getElementById('globalSearchInput');
  const sortSelect = document.getElementById('globalSearchSort');
  const stats = document.getElementById('globalSearchStats');
  const resultsGrid = document.getElementById('globalSearchResults');
  const trendingSuggestions = document.getElementById('globalSearchSuggestions');
  const liveSuggestionsPanel = document.getElementById('globalAutocompletePanel');
  const liveSuggestionsList = document.getElementById('globalLiveSuggestions');
  const emptyState = document.getElementById('searchEmptyState');
  const resetFiltersBtn = document.getElementById('globalResetFilters');

  const filterControls = {
    category: document.getElementById('globalFilterCategory'),
    brand: document.getElementById('globalFilterBrand'),
    color: document.getElementById('globalFilterColor'),
    size: document.getElementById('globalFilterSize'),
    priceMin: document.getElementById('globalFilterPriceMin'),
    priceMax: document.getElementById('globalFilterPriceMax'),
    availIn: document.getElementById('globalFilterAvailabilityIn'),
    availOut: document.getElementById('globalFilterAvailabilityOut'),
    discount: document.getElementById('globalFilterDiscountOnly')
  };

  let debounceId;
  let suggestionsDebounceId;
  let lastQuery = '';
  let lastResults = [];
  let isFetching = false;

  function openSearch() {
    if (!overlay) return;
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    setTimeout(() => input?.focus(), 60);
    if (!lastQuery) {
      fetchResults();
    }
  }

  function closeSearch() {
    if (!overlay) return;
    overlay.classList.remove('active');
    document.body.style.overflow = '';
  }

  openBtn?.addEventListener('click', openSearch);
  closeButtons?.forEach(btn => btn.addEventListener('click', closeSearch));
  overlay?.addEventListener('click', (e) => {
    if (e.target === overlay) closeSearch();
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && overlay?.classList.contains('active')) closeSearch();
  });

  input?.addEventListener('input', () => {
    lastQuery = input.value.trim();
    fetchResults();
    queueLiveSuggestions(lastQuery);
  });

  clearBtn?.addEventListener('click', () => {
    if (!input) return;
    input.value = '';
    lastQuery = '';
    renderLiveSuggestions([]);
    fetchResults();
  });

  sortSelect?.addEventListener('change', fetchResults);

  Object.values(filterControls).forEach(control => {
    if (!control) return;
    const eventName = control.type === 'checkbox' ? 'change' : 'input';
    control.addEventListener(eventName, () => {
      if (control === filterControls.priceMin || control === filterControls.priceMax) {
        control.value = control.value.slice(0, 7);
      }
      fetchResults();
    });
  });

  resetFiltersBtn?.addEventListener('click', () => {
    Object.values(filterControls).forEach(control => {
      if (!control) return;
      if (control.tagName === 'SELECT' || control.type === 'number') control.value = '';
      if (control.type === 'checkbox') control.checked = false;
    });
    fetchResults();
  });

  function getFilters() {
    return {
      category: filterControls.category?.value || '',
      brand: filterControls.brand?.value || '',
      color: filterControls.color?.value || '',
      size: filterControls.size?.value || '',
      min_price: filterControls.priceMin?.value || '',
      max_price: filterControls.priceMax?.value || '',
      availability: getAvailabilityValue(),
      discount_only: filterControls.discount?.checked ? '1' : ''
    };
  }

  function getAvailabilityValue() {
    const inStock = filterControls.availIn?.checked;
    const outStock = filterControls.availOut?.checked;
    if (inStock && !outStock) return 'in';
    if (!inStock && outStock) return 'out';
    return '';
  }

  function fetchResults() {
    clearTimeout(debounceId);
    debounceId = setTimeout(async () => {
      try {
        isFetching = true;
        setLoading(true);
        const params = buildQueryParams();
        const res = await fetch(`${API_SEARCH}?${params.toString()}`, { cache: 'no-store' });
        if (!res.ok) throw new Error('Error de red');
        const data = await res.json();
        lastResults = data.results || [];
        updateStats(data.meta);
        updateTrendingSuggestions(lastResults);
        renderResults(lastResults);
        updateFiltersFromFacets(data.facets || {});
      } catch (error) {
        console.error('Buscador:', error);
        showEmpty('Ocurrió un error al buscar. Intenta nuevamente.');
      } finally {
        isFetching = false;
        setLoading(false);
      }
    }, 220);
  }

  function buildQueryParams() {
    const params = new URLSearchParams();
    if (lastQuery) params.set('q', lastQuery);
    params.set('sort', sortSelect?.value || 'relevance');
    params.set('page', '1');
    params.set('per_page', '12');
    const filters = getFilters();
    Object.entries(filters).forEach(([key, value]) => {
      if (value) params.set(key, value);
    });
    return params;
  }

  function setLoading(state) {
    resultsGrid?.classList.toggle('loading', state);
    if (state && resultsGrid) {
      resultsGrid.innerHTML = `
        <div class="loading-state">
          <i class="fas fa-spinner fa-spin"></i>
          <span>Buscando mejores resultados...</span>
        </div>`;
    }
  }

  function updateStats(meta = {}) {
    if (!stats) return;
    const total = meta.total ?? 0;
    const page = meta.page ?? 1;
    stats.textContent = `${total} resultado${total === 1 ? '' : 's'} • Página ${page}`;
  }

  function updateTrendingSuggestions(results) {
    if (!trendingSuggestions) return;
    if (!lastQuery) {
      trendingSuggestions.innerHTML = '';
      return;
    }
    const ranked = [...results]
      .map(item => ({ item, score: similarity(lastQuery, item.name || '') }))
      .sort((a, b) => b.score - a.score)
      .slice(0, 6);

    trendingSuggestions.innerHTML = ranked
      .map(({ item }) => `<li data-id="${item.id}">${highlightMatch(item.name, lastQuery)}</li>`)
      .join('');

    trendingSuggestions.querySelectorAll('li').forEach(li => {
      li.addEventListener('click', () => {
        window.location.href = `${BASE}views/productos-detal.php?id=${li.dataset.id}`;
      });
    });
  }

  function renderResults(results) {
    if (!resultsGrid) return;
    if (!results.length) {
      showEmpty('No encontramos coincidencias. Ajusta los filtros o intenta con otra palabra.');
      return;
    }
    emptyState?.classList.add('hidden');
    resultsGrid.innerHTML = results.map(resultCardTemplate).join('');
  }

  function resultCardTemplate(product) {
    const detailUrl = `${BASE}views/productos-detal.php?id=${product.id}`;
    const price = product.price ? `$${Number(product.price).toLocaleString('es-MX')}` : '';
    const original = product.originalPrice && product.originalPrice > product.price
      ? `<span class="meta">Antes $${Number(product.originalPrice).toLocaleString('es-MX')}</span>`
      : '';
    const tags = [];
    if (product.category) tags.push(product.category);
    if (product.brand) tags.push(product.brand);
    if (product.discount && product.discount > 0) tags.push(`-${product.discount}%`);
    if (product.availability === false || product.stock === 0) tags.push('Agotado');

    return `
      <article class="result-card">
        <img src="${product.image}" alt="${product.name}">
        <h3>${product.name}</h3>
        <div class="price">${price}</div>
        ${original}
        <div class="meta">${product.description ? truncate(product.description, 90) : ''}</div>
        <div class="tags">
          ${tags.map(tag => `<span class="tag">${tag}</span>`).join('')}
        </div>
        <div class="cta">
          <a href="${detailUrl}">Ver detalle</a>
          <button class="catalog-wishlist-btn ${product.in_wishlist ? 'active' : ''}" data-product-id="${product.id}">
            <i class="${product.in_wishlist ? 'fas' : 'far'} fa-heart"></i>
            ${product.in_wishlist ? 'En favoritos' : 'Agregar'}
          </button>
        </div>
      </article>
    `;
  }

  resultsGrid?.addEventListener('click', (event) => {
    const btn = event.target.closest('.catalog-wishlist-btn');
    if (!btn) return;
    const id = parseInt(btn.dataset.productId || '0', 10);
    if (!id) return;
    event.preventDefault();
    toggleWishlistFromResults(btn, id);
  });

  async function toggleWishlistFromResults(button, productId) {
    button.disabled = true;
    const icon = button.querySelector('i');
    const prevIcon = icon?.className;
    if (icon) icon.className = 'fas fa-spinner fa-spin';
    try {
      const res = await fetch(`${BASE}api/wishlist/toggle.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ producto_id: productId })
      });
      if (res.status === 401) {
        window.location.href = `${BASE}views/login.php?next=${encodeURIComponent(location.pathname)}`;
        return;
      }
      const data = await res.json();
      if (!data.ok) throw new Error(data.msg || 'Error');
      updateWishlistButton(button, data.in_wishlist);
      window.dispatchEvent(new CustomEvent('wishlist:updated'));
    } catch (error) {
      console.error(error);
    } finally {
      button.disabled = false;
      if (icon && prevIcon) icon.className = prevIcon;
    }
  }

  function updateWishlistButton(button, state) {
    button.classList.toggle('active', state);
    const icon = button.querySelector('i');
    if (icon) icon.className = `${state ? 'fas' : 'far'} fa-heart`;
    button.textContent = '';
    button.insertAdjacentHTML('beforeend', `<i class="${state ? 'fas' : 'far'} fa-heart"></i>${state ? ' En favoritos' : ' Agregar'}`);
  }

  function updateFiltersFromFacets(facets) {
    populateSelect(filterControls.category, facets.categories);
    populateSelect(filterControls.brand, facets.brands);
    populateSelect(filterControls.color, facets.colors);
    populateSelect(filterControls.size, facets.sizes);
    if (facets.price) {
      if (!filterControls.priceMin.value) filterControls.priceMin.placeholder = `Desde $${Math.round(facets.price.min || 0)}`;
      if (!filterControls.priceMax.value) filterControls.priceMax.placeholder = `Hasta $${Math.round(facets.price.max || 0)}`;
    }
  }

  function populateSelect(select, data) {
    if (!select || !data) return;
    const current = select.value;
    const entries = Object.entries(data).sort((a, b) => a[0].localeCompare(b[0], 'es'));
    select.innerHTML = '<option value="">Todas</option>' + entries.map(
      ([label, count]) => `<option value="${label}">${label} (${count})</option>`
    ).join('');
    if (current) select.value = current;
  }

  function showEmpty(message) {
    if (resultsGrid) resultsGrid.innerHTML = '';
    if (emptyState) {
      emptyState.querySelector('p').textContent = message;
      emptyState.classList.remove('hidden');
    }
  }

  function queueLiveSuggestions(term) {
    clearTimeout(suggestionsDebounceId);
    if (!term || term.length < 2) {
      renderLiveSuggestions([]);
      return;
    }
    suggestionsDebounceId = setTimeout(() => fetchLiveSuggestions(term), 180);
  }

  async function fetchLiveSuggestions(term) {
    try {
      const response = await fetch(`${API_SUGGESTIONS}?q=${encodeURIComponent(term)}&limit=6`, { cache: 'no-store' });
      if (!response.ok) throw new Error('Suggest error');
      const data = await response.json();
      if (!data.ok) throw new Error('Suggest payload');
      renderLiveSuggestions(data.suggestions || []);
    } catch (error) {
      console.error('Autocomplete error', error);
      renderLiveSuggestions([]);
    }
  }

  function renderLiveSuggestions(items) {
    if (!liveSuggestionsPanel || !liveSuggestionsList) return;
    if (!items.length) {
      liveSuggestionsPanel.classList.add('is-hidden');
      liveSuggestionsList.innerHTML = '';
      return;
    }
    liveSuggestionsPanel.classList.remove('is-hidden');
    liveSuggestionsList.innerHTML = items
      .map(item => `<li data-value="${item}"><i class="fas fa-arrow-turn-down"></i>${highlightMatch(item, lastQuery)}</li>`)
      .join('');

    liveSuggestionsList.querySelectorAll('li[data-value]').forEach(li => {
      li.addEventListener('click', () => {
        input.value = li.dataset.value;
        lastQuery = li.dataset.value;
        renderLiveSuggestions([]);
        fetchResults();
      });
    });
  }

  function similarity(a, b) {
    if (!a || !b) return 0;
    a = a.toLowerCase();
    b = b.toLowerCase();
    const distance = levenshtein(a, b);
    const maxLen = Math.max(a.length, b.length);
    return maxLen ? 1 - distance / maxLen : 0;
  }

  function levenshtein(a, b) {
    const matrix = Array.from({ length: a.length + 1 }, (_, i) => [i]);
    for (let j = 0; j <= b.length; j++) matrix[0][j] = j;
    for (let i = 1; i <= a.length; i++) {
      for (let j = 1; j <= b.length; j++) {
        const cost = a[i - 1] === b[j - 1] ? 0 : 1;
        matrix[i][j] = Math.min(
          matrix[i - 1][j] + 1,
          matrix[i][j - 1] + 1,
          matrix[i - 1][j - 1] + cost
        );
      }
    }
    return matrix[a.length][b.length];
  }

  function highlightMatch(text, query) {
    if (!query) return text;
    const regex = new RegExp(`(${query})`, 'ig');
    return text.replace(regex, '<mark>$1</mark>');
  }

  function truncate(text, max) {
    if (!text) return '';
    return text.length > max ? `${text.slice(0, max)}…` : text;
  }

  // Pre-cargar resultados para sugerencias destacadas
  fetchResults();
});

