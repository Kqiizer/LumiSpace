const posts = window.BLOG_POSTS || [];
const categories = window.BLOG_CATEGORIES || {};
const tags = window.BLOG_TAGS || {};

const postsContainer = document.getElementById('postsGrid');
const searchInput = document.getElementById('blogSearchInput');
const searchButton = document.getElementById('blogSearchBtn');
const categoryList = document.getElementById('blogCategoryList');
const tagList = document.getElementById('blogTagList');
const recommendedList = document.getElementById('recommendedList');
const paginationEl = document.getElementById('blogPagination');

let currentCategory = '';
let currentTag = '';
let currentPage = 1;
const perPage = 6;

function initBlogPage() {
    renderCategories();
    renderTags();
    renderRecommended();
    renderPosts();
    attachEvents();
}

function attachEvents() {
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            currentPage = 1;
            renderPosts();
        });
    }
    if (searchButton) {
        searchButton.addEventListener('click', () => {
            currentPage = 1;
            renderPosts();
        });
    }
}

function renderCategories() {
    if (!categoryList) return;
    const entries = Object.entries(categories);
    categoryList.innerHTML = `
        <button class="${currentCategory === '' ? 'active' : ''}" data-category="">Todas (${posts.length})</button>
        ${entries.map(([name, count]) => `
            <button class="${currentCategory === name ? 'active' : ''}" data-category="${name}">${name} (${count})</button>
        `).join('')}
    `;
    categoryList.querySelectorAll('button[data-category]').forEach(btn => {
        btn.addEventListener('click', () => {
            currentCategory = btn.dataset.category;
            currentPage = 1;
            renderCategories();
            renderPosts();
        });
    });
}

function renderTags() {
    if (!tagList) return;
    const entries = Object.entries(tags).sort((a, b) => b[1] - a[1]);
    tagList.innerHTML = entries.map(([name]) => `
        <button class="${currentTag === name ? 'active' : ''}" data-tag="${name}">#${name}</button>
    `).join('');
    tagList.querySelectorAll('button[data-tag]').forEach(btn => {
        btn.addEventListener('click', () => {
            currentTag = btn.dataset.tag === currentTag ? '' : btn.dataset.tag;
            currentPage = 1;
            renderTags();
            renderPosts();
        });
    });
}

function getFilteredPosts() {
    const term = (searchInput?.value || '').toLowerCase();
    return posts.filter(post => {
        const matchesTerm = !term || post.title.toLowerCase().includes(term) || (post.summary || '').toLowerCase().includes(term);
        const matchesCategory = !currentCategory || post.category === currentCategory;
        const matchesTag = !currentTag || (post.tags || []).includes(currentTag);
        return matchesTerm && matchesCategory && matchesTag;
    });
}

function renderPosts() {
    if (!postsContainer) return;
    const filtered = getFilteredPosts();
    const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;
    const start = (currentPage - 1) * perPage;
    const pagePosts = filtered.slice(start, start + perPage);

    if (!pagePosts.length) {
        postsContainer.innerHTML = '<div class="empty-state">No se encontraron artículos.</div>';
        updatePagination(totalPages);
        return;
    }

    postsContainer.innerHTML = pagePosts.map(post => `
        <article class="post-card">
            <img src="${post.image}" alt="${post.title}">
            <div class="post-content">
                <div class="post-meta">${formatDate(post.published_at)} • ${post.category}</div>
                <h3>${post.title}</h3>
                <p>${post.summary}</p>
                <div class="post-tags">
                    ${(post.tags || []).map(tag => `<span class="post-tag">${tag}</span>`).join('')}
                </div>
                <div class="post-related">
                    ${(post.related || []).map(rel => `<span>🔗 ${rel}</span>`).join('')}
                </div>
            </div>
        </article>
    `).join('');

    updatePagination(totalPages);
}

function updatePagination(totalPages) {
    if (!paginationEl) return;
    paginationEl.innerHTML = '';
    if (totalPages <= 1) return;

    const createBtn = (label, disabled, handler) => {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.disabled = disabled;
        btn.addEventListener('click', handler);
        return btn;
    };

    paginationEl.appendChild(createBtn('Anterior', currentPage <= 1, () => {
        if (currentPage > 1) {
            currentPage -= 1;
            renderPosts();
        }
    }));

    paginationEl.appendChild(createBtn('Siguiente', currentPage >= totalPages, () => {
        if (currentPage < totalPages) {
            currentPage += 1;
            renderPosts();
        }
    }));
}

function renderRecommended() {
    if (!recommendedList) return;
    const topPosts = [...posts].slice(0, 3);
    recommendedList.innerHTML = topPosts.map(post => `
        <div class="recommended-item">
            <img src="${post.image}" alt="${post.title}">
            <div>
                <small>${formatDate(post.published_at)}</small>
                <strong>${post.title}</strong>
            </div>
        </div>
    `).join('');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
}

initBlogPage();

