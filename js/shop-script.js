// Filter functionality
document.querySelectorAll('.filter-item').forEach(item => {
    item.addEventListener('click', function() {
        const checkbox = this.querySelector('.filter-checkbox');
        checkbox.classList.toggle('checked');
        
        if (checkbox.classList.contains('checked')) {
            checkbox.innerHTML = '<i class="fas fa-check" style="font-size: 10px;"></i>';
        } else {
            checkbox.innerHTML = '';
        }
    });
});

// View toggle functionality
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const view = this.dataset.view;
        const gridView = document.querySelector('.products-grid');
        const listView = document.querySelector('.products-list');
        
        if (view === 'grid') {
            gridView.classList.add('active');
            listView.classList.remove('active');
        } else {
            gridView.classList.remove('active');
            listView.classList.add('active');
        }
    });
});

// Add to cart functionality
document.querySelectorAll('.add-to-cart').forEach(btn => {
    btn.addEventListener('click', function() {
        const originalText = this.textContent;
        this.textContent = 'Added!';
        this.style.background = '#28a745';
        
        // Update cart badge
        const cartBadge = document.querySelector('.fa-shopping-cart .cart-badge');
        let currentCount = parseInt(cartBadge.textContent);
        cartBadge.textContent = currentCount + 1;
        
        setTimeout(() => {
            this.textContent = originalText;
            this.style.background = '#a0896b';
        }, 2000);
    });
});

// Quick shop functionality
document.querySelectorAll('.quick-shop').forEach(btn => {
    btn.addEventListener('click', function() {
        // Create modal overlay
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(139, 115, 85, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.style.cssText = `
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            text-align: center;
        `;
        
        modalContent.innerHTML = `
            <h3 style="color: #8b7355; margin-bottom: 20px;">Quick Shop</h3>
            <p style="color: #a0896b; margin-bottom: 30px;">Select size and quantity for quick purchase</p>
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 10px; color: #8b7355;">Size:</label>
                <select style="width: 100%; padding: 10px; border: 2px solid #d4c4a8; border-radius: 8px;">
                    <option>Small</option>
                    <option>Medium</option>
                    <option>Large</option>
                </select>
            </div>
            <div style="margin-bottom: 30px;">
                <label style="display: block; margin-bottom: 10px; color: #8b7355;">Quantity:</label>
                <input type="number" value="1" min="1" style="width: 100%; padding: 10px; border: 2px solid #d4c4a8; border-radius: 8px;">
            </div>
            <div style="display: flex; gap: 15px; justify-content: center;">
                <button onclick="this.parentElement.parentElement.parentElement.remove()" style="
                    background: #a0896b;
                    color: white;
                    border: none;
                    padding: 12px 30px;
                    border-radius: 25px;
                    cursor: pointer;
                    font-weight: 600;
                ">Add to Cart</button>
                <button onclick="this.parentElement.parentElement.parentElement.remove()" style="
                    background: transparent;
                    color: #8b7355;
                    border: 2px solid #d4c4a8;
                    padding: 10px 30px;
                    border-radius: 25px;
                    cursor: pointer;
                ">Cancel</button>
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Close on overlay click
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    });
});

// Wishlist functionality
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        const icon = this.querySelector('i');
        if (icon.classList.contains('fa-heart')) {
            if (this.classList.contains('liked')) {
                this.classList.remove('liked');
                this.style.color = '#8b7355';
                
                // Update wishlist badge
                const wishlistBadge = document.querySelector('.fa-heart .cart-badge');
                let currentCount = parseInt(wishlistBadge.textContent);
                wishlistBadge.textContent = Math.max(0, currentCount - 1);
            } else {
                this.classList.add('liked');
                this.style.color = 'red';
                
                // Update wishlist badge
                const wishlistBadge = document.querySelector('.fa-heart .cart-badge');
                let currentCount = parseInt(wishlistBadge.textContent);
                wishlistBadge.textContent = currentCount + 1;
            }
        }
        
        // Visual feedback
        this.style.transform = 'scale(0.9)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });
});

// Remove filter tags
document.querySelectorAll('.filter-tag .remove').forEach(btn => {
    btn.addEventListener('click', function() {
        this.parentElement.remove();
    });
});

// Clear all filters
document.querySelector('.clear-all').addEventListener('click', function() {
    document.querySelectorAll('.filter-tag').forEach(tag => tag.remove());
    document.querySelectorAll('.filter-checkbox.checked').forEach(checkbox => {
        checkbox.classList.remove('checked');
        checkbox.innerHTML = '';
    });
});

// Price range slider
document.querySelector('.price-slider').addEventListener('input', function() {
    const value = this.value;
    const max = this.max;
    const percentage = (value / max) * 100;
    this.style.background = `linear-gradient(to right, #a0896b 0%, #a0896b ${percentage}%, #d4c4a8 ${percentage}%, #d4c4a8 100%)`;
});

// Sort dropdown
document.querySelector('.sort-dropdown').addEventListener('change', function() {
    // Add sorting functionality here
    console.log('Sorting by:', this.value);
});

// Mobile filter functionality
const mobileFilterToggle = document.querySelector('.mobile-filter-toggle');
const filtersSidebar = document.querySelector('.filters-sidebar');
const filterOverlay = document.querySelector('.filter-overlay');
const filterClose = document.querySelector('.filter-close');

// Open filters
mobileFilterToggle.addEventListener('click', function() {
    filtersSidebar.classList.add('active');
    filterOverlay.classList.add('active');
    document.body.style.overflow = 'hidden';
});

// Close filters
function closeFilters() {
    filtersSidebar.classList.remove('active');
    filterOverlay.classList.remove('active');
    document.body.style.overflow = '';
}

filterClose.addEventListener('click', closeFilters);
filterOverlay.addEventListener('click', closeFilters);

// Close on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeFilters();
    }
});

// Search functionality simulation for header
const searchIcon = document.querySelector('.fa-search');
if (searchIcon) {
    searchIcon.addEventListener('click', function() {
        const searchOverlay = document.createElement('div');
        searchOverlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(139, 115, 85, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        `;
        
        const searchBox = document.createElement('div');
        searchBox.style.cssText = `
            background: white;
            padding: 40px;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            text-align: center;
        `;
        
        searchBox.innerHTML = `
            <h3 style="color: #8b7355; margin-bottom: 20px;">Search Products</h3>
            <input type="text" placeholder="What are you looking for?" style="
                width: 100%;
                padding: 15px;
                border: 2px solid #d4c4a8;
                border-radius: 10px;
                font-size: 16px;
                margin-bottom: 20px;
                outline: none;
            ">
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: #a0896b;
                color: white;
                border: none;
                padding: 12px 30px;
                border-radius: 25px;
                cursor: pointer;
                margin-right: 10px;
            ">Search</button>
            <button onclick="this.parentElement.parentElement.remove()" style="
                background: transparent;
                color: #8b7355;
                border: 2px solid #d4c4a8;
                padding: 10px 30px;
                border-radius: 25px;
                cursor: pointer;
            ">Cancel</button>
        `;
        
        searchOverlay.appendChild(searchBox);
        document.body.appendChild(searchOverlay);
        
        // Focus on input
        setTimeout(() => {
            searchBox.querySelector('input').focus();
        }, 100);
        
        // Close on overlay click
        searchOverlay.addEventListener('click', function(e) {
            if (e.target === searchOverlay) {
                searchOverlay.remove();
            }
        });
    });
}

// Cart animation
const cartIcon = document.querySelector('.fa-shopping-cart');
if (cartIcon) {
    cartIcon.addEventListener('click', function() {
        this.style.transform = 'scale(1.2)';
        this.style.color = '#a0896b';
        
        setTimeout(() => {
            this.style.transform = 'scale(1)';
            this.style.color = '#8b7355';
        }, 200);
    });
}

// Wishlist animation in header
const heartIcon = document.querySelector('.header-icons .fa-heart');
if (heartIcon) {
    heartIcon.addEventListener('click', function() {
        this.style.transform = 'scale(1.2)';
        this.style.color = '#d4c4a8';
        
        setTimeout(() => {
            this.style.transform = 'scale(1)';
            this.style.color = '#8b7355';
        }, 200);
    });
}

// Rating filter functionality
document.querySelectorAll('.rating-item').forEach(item => {
    item.addEventListener('click', function() {
        const checkbox = this.querySelector('.filter-checkbox');
        checkbox.classList.toggle('checked');
        
        if (checkbox.classList.contains('checked')) {
            checkbox.innerHTML = '<i class="fas fa-check" style="font-size: 10px;"></i>';
        } else {
            checkbox.innerHTML = '';
        }
    });
});

// Pagination functionality
document.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all buttons
        document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
        
        // Add active class to clicked button (if it's not a navigation arrow)
        if (!this.querySelector('i')) {
            this.classList.add('active');
        }
        
        // Simulate loading
        const resultsInfo = document.querySelector('.results-info');
        const originalText = resultsInfo.textContent;
        resultsInfo.textContent = 'Loading...';
        
        setTimeout(() => {
            resultsInfo.textContent = originalText;
        }, 1000);
    });
});

// Price input synchronization
document.querySelectorAll('.price-input').forEach(input => {
    input.addEventListener('input', function() {
        const slider = document.querySelector('.price-slider');
        const minInput = document.querySelector('.price-input[placeholder="Min"]');
        const maxInput = document.querySelector('.price-input[placeholder="Max"]');
        
        // Update slider based on inputs
        if (this.placeholder === 'Min') {
            slider.min = this.value || 10;
        } else if (this.placeholder === 'Max') {
            slider.max = this.value || 1000;
        }
        
        // Ensure min is not greater than max
        if (parseInt(minInput.value) > parseInt(maxInput.value)) {
            if (this.placeholder === 'Min') {
                maxInput.value = this.value;
            } else {
                minInput.value = this.value;
            }
        }
    });
});

// Initialize price slider background
window.addEventListener('load', function() {
    const slider = document.querySelector('.price-slider');
    const value = slider.value;
    const max = slider.max;
    const percentage = (value / max) * 100;
    slider.style.background = `linear-gradient(to right, #a0896b 0%, #a0896b ${percentage}%, #d4c4a8 ${percentage}%, #d4c4a8 100%)`;
});

// Smooth animations for product cards
document.addEventListener('DOMContentLoaded', function() {
    // Staggered animation for product cards on load
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Mobile navigation menu toggle (if needed)
function createMobileNavToggle() {
    if (window.innerWidth <= 768 && !document.querySelector('.mobile-nav-toggle')) {
        const navToggle = document.createElement('button');
        navToggle.className = 'mobile-nav-toggle';
        navToggle.innerHTML = '<i class="fas fa-bars"></i>';
        navToggle.style.cssText = `
            background: none;
            border: none;
            font-size: 20px;
            color: #8b7355;
            cursor: pointer;
            display: block;
        `;
        
        navToggle.addEventListener('click', function() {
            const navMenu = document.querySelector('.nav-menu');
            if (navMenu.style.display === 'none' || !navMenu.style.display) {
                navMenu.style.display = 'flex';
                navMenu.style.position = 'absolute';
                navMenu.style.top = '100%';
                navMenu.style.left = '0';
                navMenu.style.right = '0';
                navMenu.style.background = 'white';
                navMenu.style.flexDirection = 'column';
                navMenu.style.padding = '20px';
                navMenu.style.boxShadow = '0 5px 15px rgba(139, 115, 85, 0.1)';
                navMenu.style.zIndex = '1000';
            } else {
                navMenu.style.display = 'none';
            }
        });
        
        const headerContainer = document.querySelector('.header .container');
        headerContainer.insertBefore(navToggle, document.querySelector('.header-icons'));
    }
}

// Initialize mobile nav and update on resize
createMobileNavToggle();
window.addEventListener('resize', createMobileNavToggle);

// Keyboard navigation for filters
document.addEventListener('keydown', function(e) {
    if (e.target.closest('.filter-item') && (e.key === 'Enter' || e.key === ' ')) {
        e.preventDefault();
        e.target.closest('.filter-item').click();
    }
});

// Accessibility improvements
document.querySelectorAll('.filter-item, .rating-item').forEach(item => {
    item.setAttribute('tabindex', '0');
    item.setAttribute('role', 'checkbox');
    item.setAttribute('aria-checked', 'false');
    
    item.addEventListener('click', function() {
        const isChecked = this.querySelector('.filter-checkbox').classList.contains('checked');
        this.setAttribute('aria-checked', isChecked);
    });
});

// Loading states for better UX
function showLoadingState() {
    const productsGrid = document.querySelector('.products-grid');
    const productsList = document.querySelector('.products-list');
    
    productsGrid.style.opacity = '0.6';
    productsList.style.opacity = '0.6';
    
    setTimeout(() => {
        productsGrid.style.opacity = '1';
        productsList.style.opacity = '1';
    }, 800);
}

// Trigger loading state when filters change
document.querySelectorAll('.filter-item, .rating-item').forEach(item => {
    item.addEventListener('click', showLoadingState);
});

document.querySelector('.sort-dropdown').addEventListener('change', showLoadingState);