// Animated Counter for Statistics
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
            statsObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.5 });

const statsSection = document.querySelector('.stats-section');
if (statsSection) {
    statsObserver.observe(statsSection);
}

// Testimonials Slider
let currentTestimonial = 0;
const testimonials = document.querySelectorAll('.testimonial-card');
const dots = document.querySelectorAll('.dot');

function showTestimonial(index) {
    testimonials.forEach(card => card.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    testimonials[index].classList.add('active');
    dots[index].classList.add('active');
}

document.querySelector('.nav-btn.prev').addEventListener('click', function() {
    currentTestimonial = (currentTestimonial - 1 + testimonials.length) % testimonials.length;
    showTestimonial(currentTestimonial);
});

document.querySelector('.nav-btn.next').addEventListener('click', function() {
    currentTestimonial = (currentTestimonial + 1) % testimonials.length;
    showTestimonial(currentTestimonial);
});

dots.forEach((dot, index) => {
    dot.addEventListener('click', function() {
        currentTestimonial = index;
        showTestimonial(currentTestimonial);
    });
});

// Auto-play testimonials
setInterval(() => {
    currentTestimonial = (currentTestimonial + 1) % testimonials.length;
    showTestimonial(currentTestimonial);
}, 5000);

// Scroll animations for sections
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const sectionObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.style.opacity = '1';
            entry.target.style.transform = 'translateY(0)';
        }
    });
}, observerOptions);

// Observe sections for scroll animations
document.querySelectorAll('.value-card, .team-card, .timeline-item').forEach(el => {
    el.style.opacity = '0';
    el.style.transform = 'translateY(30px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    sectionObserver.observe(el);
});

// Timeline items animation with stagger
const timelineObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const timelineItems = entry.target.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 200);
            });
            timelineObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.2 });

const timelineSection = document.querySelector('.timeline');
if (timelineSection) {
    timelineObserver.observe(timelineSection);
}

// Process steps animation
const processObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const steps = entry.target.querySelectorAll('.process-step');
            steps.forEach((step, index) => {
                setTimeout(() => {
                    step.style.opacity = '1';
                    step.style.transform = 'translateY(0)';
                }, index * 150);
            });
            processObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.3 });

const processSection = document.querySelector('.process-section');
if (processSection) {
    document.querySelectorAll('.process-step').forEach(step => {
        step.style.opacity = '0';
        step.style.transform = 'translateY(30px)';
        step.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    });
    processObserver.observe(processSection);
}

// Team card hover effects
document.querySelectorAll('.team-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-10px) scale(1.02)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) scale(1)';
    });
});

// Get Directions button
document.querySelector('.get-directions-btn')?.addEventListener('click', function() {
    // Simulate opening directions in a new tab
    const address = '123 Furniture Street, Design District, DC 12345';
    const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
    window.open(mapsUrl, '_blank');
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
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
    
    // Add shadow on scroll
    if (scrollTop > 50) {
        header.style.boxShadow = '0 5px 20px rgba(139, 115, 85, 0.15)';
    } else {
        header.style.boxShadow = '0 2px 5px rgba(139, 115, 85, 0.1)';
    }
    
    lastScrollTop = scrollTop;
});

// Add transition to header
header.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';

// Parallax effect for hero section
window.addEventListener('scroll', function() {
    const scrolled = window.pageYOffset;
    const hero = document.querySelector('.about-hero');
    
    if (hero && scrolled < hero.offsetHeight) {
        hero.style.transform = `translateY(${scrolled * 0.5}px)`;
    }
});

// Search functionality
const searchIcon = document.querySelector('.fa-search');
if (searchIcon) {
    searchIcon.addEventListener('click', function() {
        const searchOverlay = document.createElement('div');
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

// Cart and Wishlist icons animation
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

// Add CSS animation keyframes dynamically
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
        }
        to {
            opacity: 1;
        }
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);

// Page load animation
window.addEventListener('load', function() {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity 0.5s ease-in';
    
    setTimeout(() => {
        document.body.style.opacity = '1';
    }, 100);
});

// Mobile navigation toggle
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
        const headerIcons = document.querySelector('.header-icons');
        headerContainer.insertBefore(navToggle, headerIcons);
    }
}

// Initialize mobile nav
createMobileNavToggle();
window.addEventListener('resize', createMobileNavToggle);

// Value cards stagger animation
const valuesObserver = new IntersectionObserver(function(entries) {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const cards = entry.target.querySelectorAll('.value-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 150);
            });
            valuesObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.2 });

const valuesSection = document.querySelector('.values-grid');
if (valuesSection) {
    valuesObserver.observe(valuesSection);
}

// Social links hover effects
document.querySelectorAll('.social-icons a').forEach(link => {
    link.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-3px) rotate(360deg)';
    });
    
    link.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0) rotate(0deg)';
    });
});

// Add smooth transitions to all links
document.querySelectorAll('a').forEach(link => {
    link.style.transition = 'all 0.3s ease';
});

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    // ESC key to close modals
    if (e.key === 'Escape') {
        const overlays = document.querySelectorAll('[style*="position: fixed"]');
        overlays.forEach(overlay => overlay.remove());
    }
    
    // Arrow keys for testimonial navigation
    if (e.key === 'ArrowLeft') {
        document.querySelector('.nav-btn.prev')?.click();
    }
    if (e.key === 'ArrowRight') {
        document.querySelector('.nav-btn.next')?.click();
    }
});

// Accessibility improvements
document.querySelectorAll('button, a').forEach(element => {
    if (!element.getAttribute('aria-label') && !element.textContent.trim()) {
        const icon = element.querySelector('i');
        if (icon) {
            const classList = Array.from(icon.classList);
            const iconName = classList.find(c => c.startsWith('fa-'))?.replace('fa-', '');
            if (iconName) {
                element.setAttribute('aria-label', iconName.replace(/-/g, ' '));
            }
        }
    }
});

// Log page view (for analytics)
console.log('About Us page loaded successfully');

// Performance monitoring
window.addEventListener('load', function() {
    const loadTime = performance.now();
    console.log(`Page loaded in ${Math.round(loadTime)}ms`);
});Overlay.style.cssText = `
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
            <h3 style="color: #8b7355; margin-bottom: 20px;">Search</h3>
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
        
        search