// Newsletter form functionality
document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const email = this.querySelector('.newsletter-input').value;
    const btn = this.querySelector('.newsletter-btn');
    
    if (email) {
        const originalText = btn.innerHTML;
        btn.innerHTML = 'Subscribing... <i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = 'Subscribed! <i class="fas fa-check"></i>';
            btn.style.background = 'linear-gradient(135deg, #28a745, #20c997)';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                btn.style.background = 'linear-gradient(135deg, #a0896b, #8b7355)';
                this.querySelector('.newsletter-input').value = '';
            }, 2000);
        }, 1500);
    }
});

// Footer animations
const footerObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const footerSections = entry.target.querySelectorAll('.footer-section');
            footerSections.forEach((section, index) => {
                setTimeout(() => {
                    section.style.opacity = '1';
                    section.style.transform = 'translateY(0)';
                }, index * 200);
            });
        }
    });
}, { threshold: 0.2 });

const footer = document.querySelector('.footer');
if (footer) {
    document.querySelectorAll('.footer-section').forEach(section => {
        section.style.opacity = '0';
        section.style.transform = 'translateY(30px)';
        section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    
    footerObserver.observe(footer);
}

// Payment icons hover effect
document.querySelectorAll('.payment-icons i').forEach(icon => {
    icon.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.2) rotateY(180deg)';
    });
    
    icon.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1) rotateY(0deg)';
    });
});

// Animated counter for statistics
function animateCounter(element, target, duration = 2000) {
    const start = 0;
    const increment = target / (duration / 16);
    let current = start;
    
    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            current = target;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current).toLocaleString();
    }, 16);
}

// Start counter animation when statistics section comes into view
const statsObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const statNumbers = entry.target.querySelectorAll('.stat-number');
            statNumbers.forEach(statNumber => {
                const target = parseInt(statNumber.getAttribute('data-target'));
                animateCounter(statNumber, target, 2500);
            });
            
            // Animate stat items with stagger effect
            const statItems = entry.target.querySelectorAll('.stat-item');
            statItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 200);
            });
            
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

// Initialize stats animation
const statsSection = document.querySelector('.statistics');
if (statsSection) {
    // Set initial state for stat items
    document.querySelectorAll('.stat-item').forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(50px)';
        item.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
    });
    
    statsObserver.observe(statsSection);
}

// Add floating animation to stat icons
document.querySelectorAll('.stat-icon').forEach((icon, index) => {
    icon.style.animation = `float 3s ease-in-out infinite ${index * 0.5}s`;
});

// Add floating keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.05); }
    }
    
    .stat-number {
        animation: pulse 2s ease-in-out infinite;
    }
`;
document.head.appendChild(style);

// Simple interactivity
document.querySelectorAll('.filter-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});

// Countdown timer simulation
function updateCountdown() {
    const countdownItems = document.querySelectorAll('.countdown-number');
    countdownItems.forEach(item => {
        let current = parseInt(item.textContent);
        if (current > 0) {
            current--;
            item.textContent = current.toString().padStart(2, '0');
        }
    });
}

setInterval(updateCountdown, 1000);

// Hover effects for cards
document.querySelectorAll('.product-card, .room-card, .product-category').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-8px)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
    });
});

// Navigation arrows functionality
document.querySelectorAll('.nav-arrow').forEach(arrow => {
    arrow.addEventListener('click', function() {
        // Simple animation feedback
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 100);
    });
});

// Action buttons functionality
document.querySelectorAll('.action-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // Visual feedback
        this.style.transform = 'scale(0.9)';
        this.style.backgroundColor = '#d4c4a8';
        
        setTimeout(() => {
            this.style.transform = 'scale(1)';
            this.style.backgroundColor = 'white';
        }, 150);
        
        // Handle different actions
        const icon = this.querySelector('i');
        if (icon.classList.contains('fa-heart')) {
            icon.style.color = icon.style.color === 'red' ? '#8b7355' : 'red';
        }
    });
});

// Smooth scroll for navigation
document.querySelectorAll('.nav-menu a, .btn-primary, .btn-secondary').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        
        // Simple loading animation
        if (this.classList.contains('btn-primary')) {
            const originalText = this.innerHTML;
            this.innerHTML = 'Loading... <i class="fas fa-spinner fa-spin"></i>';
            
            setTimeout(() => {
                this.innerHTML = originalText;
            }, 1500);
        }
    });
});

// Header scroll effect
let lastScrollTop = 0;
window.addEventListener('scroll', function() {
    let scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const header = document.querySelector('.header');
    
    if (scrollTop > lastScrollTop && scrollTop > 100) {
        header.style.transform = 'translateY(-100%)';
    } else {
        header.style.transform = 'translateY(0)';
    }
    
    lastScrollTop = scrollTop;
});

// Search functionality simulation
document.querySelector('.fa-search').addEventListener('click', function() {
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

// Cart animation
document.querySelector('.fa-shopping-cart').addEventListener('click', function() {
    this.style.transform = 'scale(1.2)';
    this.style.color = '#a0896b';
    
    setTimeout(() => {
        this.style.transform = 'scale(1)';
        this.style.color = '#8b7355';
    }, 200);
});

// Wishlist animation
document.querySelector('.fa-heart').addEventListener('click', function() {
    this.style.transform = 'scale(1.2)';
    this.style.color = '#d4c4a8';
    
    setTimeout(() => {
        this.style.transform = 'scale(1)';
        this.style.color = '#8b7355';
    }, 200);
});

// Mobile menu simulation
function toggleMobileMenu() {
    const navMenu = document.querySelector('.nav-menu');
    navMenu.style.display = navMenu.style.display === 'none' ? 'flex' : 'none';
}

// Add mobile menu button if screen is small
if (window.innerWidth <= 768) {
    const mobileMenuBtn = document.createElement('button');
    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    mobileMenuBtn.style.cssText = `
        background: none;
        border: none;
        font-size: 20px;
        color: #8b7355;
        cursor: pointer;
    `;
    mobileMenuBtn.addEventListener('click', toggleMobileMenu);
    document.querySelector('.header .container').insertBefore(mobileMenuBtn, document.querySelector('.header-icons'));
}

// Parallax effect for hero section
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const parallax = document.querySelector('.hero');
    const speed = scrolled * 0.5;
    
    if (parallax) {
        parallax.style.transform = `translateY(${speed}px)`;
    }
});

// Loading animation for page
window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease-in';
    
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});

// Intersection Observer for animations
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe elements for scroll animations
document.querySelectorAll('.product-card, .feature, .product-category').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
});