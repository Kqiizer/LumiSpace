// Thumbnail Gallery
document.querySelectorAll('.thumbnail').forEach((thumb, index) => {
    thumb.addEventListener('click', function() {
        // Remove active class from all thumbnails
        document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
        
        // Add active class to clicked thumbnail
        this.classList.add('active');
        
        // Update main image (in a real scenario, you'd change the src)
        console.log(`Changed to image ${index + 1}`);
    });
});

// Quantity Controls
const qtyInput = document.querySelector('.qty-input');
const minusBtn = document.querySelector('.qty-btn.minus');
const plusBtn = document.querySelector('.qty-btn.plus');

minusBtn.addEventListener('click', function() {
    let currentValue = parseInt(qtyInput.value);
    if (currentValue > 1) {
        qtyInput.value = currentValue - 1;
        updatePrice();
    }
    updateQuantityButtons();
});

plusBtn.addEventListener('click', function() {
    let currentValue = parseInt(qtyInput.value);
    let maxValue = parseInt(qtyInput.getAttribute('max')) || 10;
    if (currentValue < maxValue) {
        qtyInput.value = currentValue + 1;
        updatePrice();
    }
    updateQuantityButtons();
});

qtyInput.addEventListener('input', function() {
    let value = parseInt(this.value);
    let min = parseInt(this.getAttribute('min')) || 1;
    let max = parseInt(this.getAttribute('max')) || 10;
    
    if (value < min) this.value = min;
    if (value > max) this.value = max;
    
    updateQuantityButtons();
    updatePrice();
});

function updateQuantityButtons() {
    const currentValue = parseInt(qtyInput.value);
    const minValue = parseInt(qtyInput.getAttribute('min')) || 1;
    const maxValue = parseInt(qtyInput.getAttribute('max')) || 10;
    
    minusBtn.disabled = currentValue <= minValue;
    plusBtn.disabled = currentValue >= maxValue;
}

function updatePrice() {
    const quantity = parseInt(qtyInput.value);
    const basePrice = 54.00;
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    const newPrice = (basePrice * quantity).toFixed(2);
    
    // Update button text
    const btnText = addToCartBtn.innerHTML.split('-')[0];
    addToCartBtn.innerHTML = `${btnText}- ${newPrice}`;
}

// Initialize
updateQuantityButtons();

// Color Selection
document.querySelectorAll('.color-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.color-option').forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        
        const color = this.getAttribute('data-color');
        console.log(`Selected color: ${color}`);
    });
});

// Size Selection
document.querySelectorAll('.size-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.size-option').forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        
        const size = this.getAttribute('data-size');
        console.log(`Selected size: ${size}`);
    });
});

// Material Selection
document.querySelectorAll('.material-option').forEach(option => {
    option.addEventListener('click', function() {
        document.querySelectorAll('.material-option').forEach(opt => opt.classList.remove('active'));
        this.classList.add('active');
        
        const material = this.getAttribute('data-material');
        console.log(`Selected material: ${material}`);
    });
});

// Add to Cart
document.querySelector('.add-to-cart-btn').addEventListener('click', function() {
    const originalText = this.innerHTML;
    this.innerHTML = '<i class="fas fa-check"></i> Added to Cart!';
    this.style.background = '#28a745';
    
    // Update cart badge
    const cartBadge = document.querySelector('.fa-shopping-cart .cart-badge');
    let currentCount = parseInt(cartBadge.textContent);
    cartBadge.textContent = currentCount + parseInt(qtyInput.value);
    
    // Animate cart icon
    const cartIcon = document.querySelector('.fa-shopping-cart');
    cartIcon.style.transform = 'scale(1.3)';
    setTimeout(() => {
        cartIcon.style.transform = 'scale(1)';
    }, 300);
    
    setTimeout(() => {
        this.innerHTML = originalText;
        this.style.background = '#8b7355';
    }, 2000);
});

// Buy Now
document.querySelector('.buy-now-btn').addEventListener('click', function() {
    // Simulate redirect to checkout
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    this.style.opacity = '0.7';
    
    setTimeout(() => {
        alert('Redirecting to checkout...');
        this.innerHTML = 'Buy Now';
        this.style.opacity = '1';
    }, 1500);
});

// Wishlist
document.querySelector('.wishlist-btn').addEventListener('click', function() {
    const icon = this.querySelector('i');
    
    if (icon.classList.contains('far')) {
        icon.classList.remove('far');
        icon.classList.add('fas');
        this.style.borderColor = '#a0896b';
        this.style.color = '#a0896b';
        
        // Update wishlist badge
        const wishlistBadge = document.querySelector('.fa-heart .cart-badge');
        let currentCount = parseInt(wishlistBadge.textContent);
        wishlistBadge.textContent = currentCount + 1;
        
        // Show feedback
        const originalText = this.innerHTML;
        this.innerHTML = '<i class="fas fa-heart"></i> Added to Wishlist';
        setTimeout(() => {
            this.innerHTML = originalText;
        }, 2000);
    } else {
        icon.classList.remove('fas');
        icon.classList.add('far');
        this.style.borderColor = '#d4c4a8';
        this.style.color = '#8b7355';
        
        // Update wishlist badge
        const wishlistBadge = document.querySelector('.fa-heart .cart-badge');
        let currentCount = parseInt(wishlistBadge.textContent);
        wishlistBadge.textContent = Math.max(0, currentCount - 1);
    }
});

// Compare Button
document.querySelector('.compare-btn').addEventListener('click', function() {
    const originalText = this.innerHTML;
    this.innerHTML = '<i class="fas fa-check"></i> Added to Compare';
    this.style.borderColor = '#a0896b';
    
    setTimeout(() => {
        this.innerHTML = originalText;
        this.style.borderColor = '#d4c4a8';
    }, 2000);
});

// Share Button
document.querySelector('.share-btn').addEventListener('click', function() {
    const shareModal = document.createElement('div');
    shareModal.style.cssText = `
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
        animation: fadeIn 0.3s ease;
    `;
    
    const shareContent = document.createElement('div');
    shareContent.style.cssText = `
        background: white;
        padding: 40px;
        border-radius: 20px;
        text-align: center;
        max-width: 400px;
    `;
    
    shareContent.innerHTML = `
        <h3 style="color: #8b7355; margin-bottom: 20px;">Share this product</h3>
        <div style="display: flex; gap: 15px; justify-content: center; margin-bottom: 20px;">
            <button style="width: 50px; height: 50px; border-radius: 50%; border: none; background: #3b5998; color: white; cursor: pointer;">
                <i class="fab fa-facebook-f"></i>
            </button>
            <button style="width: 50px; height: 50px; border-radius: 50%; border: none; background: #1da1f2; color: white; cursor: pointer;">
                <i class="fab fa-twitter"></i>
            </button>
            <button style="width: 50px; height: 50px; border-radius: 50%; border: none; background: #25d366; color: white; cursor: pointer;">
                <i class="fab fa-whatsapp"></i>
            </button>
            <button style="width: 50px; height: 50px; border-radius: 50%; border: none; background: #0077b5; color: white; cursor: pointer;">
                <i class="fab fa-linkedin-in"></i>
            </button>
        </div>
        <button onclick="this.parentElement.parentElement.remove()" style="
            background: transparent;
            color: #8b7355;
            border: 2px solid #d4c4a8;
            padding: 10px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
        ">Close</button>
    `;
    
    shareModal.appendChild(shareContent);
    document.body.appendChild(shareModal);
    
    shareModal.addEventListener('click', function(e) {
        if (e.target === shareModal) {
            shareModal.remove();
        }
    });
});

// Tabs Functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const targetTab = this.getAttribute('data-tab');
        
        // Remove active class from all tabs and buttons
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        
        // Add active class to clicked button and corresponding tab
        this.classList.add('active');
        document.getElementById(targetTab).classList.add('active');
    });
});

// Zoom Functionality
const zoomBtn = document.getElementById('zoomBtn');
const zoomModal = document.getElementById('zoomModal');
const zoomClose = document.querySelector('.zoom-close');

zoomBtn.addEventListener('click', function() {
    zoomModal.classList.add('active');
    document.body.style.overflow = 'hidden';
});

zoomClose.addEventListener('click', function() {
    zoomModal.classList.remove('active');
    document.body.style.overflow = '';
});

zoomModal.addEventListener('click', function(e) {
    if (e.target === zoomModal || e.target.classList.contains('zoom-overlay')) {
        zoomModal.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close zoom with ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && zoomModal.classList.contains('active')) {
        zoomModal.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Load More Reviews
document.querySelector('.load-more-reviews')?.addEventListener('click', function() {
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    setTimeout(() => {
        this.innerHTML = 'Load More Reviews';
        alert('More reviews loaded!');
    }, 1000);
});

// Scroll animations for related products
const productObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, { threshold: 0.1 });

document.querySelectorAll('.product-card').forEach((card, index) => {
    card.style.opacity = '0';
    card.style.transform = 'translateY(30px)';
    card.style.transition = `opacity 0.6s ease ${index * 0.1}s, transform 0.6s ease ${index * 0.1}s`;
    productObserver.observe(card);
});

// Related Products - Action Buttons
document.querySelectorAll('.related-products .action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        const icon = this.querySelector('i');
        if (icon.classList.contains('fa-heart')) {
            if (this.classList.contains('liked')) {
                this.classList.remove('liked');
                icon.style.color = '#8b7355';
            } else {
                this.classList.add('liked');
                icon.style.color = 'red';
            }
        }
        
        this.style.transform = 'scale(0.9)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 150);
    });
});

// Related Products - Click to navigate
document.querySelectorAll('.related-products .product-card').forEach(card => {
    card.addEventListener('click', function() {
        // Simulate navigation to product detail
        window.scrollTo({ top: 0, behavior: 'smooth' });
        console.log('Navigate to product detail');
    });
});

// Header scroll effect
let lastScrollTop = 0;
const header = document.querySelector('.header');

window.addEventListener('scroll', function() {
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    
    if (scrollTop > lastScrollTop && scrollTop > 100) {
        header.style.transform = 'translateY(-100%)';
    } else {
        header.style.transform = 'translateY(0)';
    }
    
    lastScrollTop = scrollTop;
});

header.style.transition = 'transform 0.3s ease';

// Search functionality
document.querySelector('.fa-search')?.addEventListener('click', function() {
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
            font-weight: 600;
        ">Search</button>
        <button onclick="this.parentElement.parentElement.remove()" style="
            background: transparent;
            color: #8b7355;
            border: 2px solid #d4c4a8;
            padding: 10px 30px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
        ">Cancel</button>
    `;
    
    searchOverlay.appendChild(searchBox);
    document.body.appendChild(searchOverlay);
    
    setTimeout(() => {
        searchBox.querySelector('input').focus();
    }, 100);
    
    searchOverlay.addEventListener('click', function(e) {
        if (e.target === searchOverlay) {
            searchOverlay.remove();
        }
    });
});

// Animate rating bars on scroll
const reviewsObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const fills = entry.target.querySelectorAll('.fill');
            fills.forEach(fill => {
                const width = fill.style.width;
                fill.style.width = '0';
                setTimeout(() => {
                    fill.style.width = width;
                }, 100);
            });
            reviewsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const ratingBreakdown = document.querySelector('.rating-breakdown');
if (ratingBreakdown) {
    reviewsObserver.observe(ratingBreakdown);
}

// Page load animation
window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease';
    
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});

console.log('Product detail page loaded successfully');