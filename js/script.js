/* ================== NEWSLETTER ================== */
const newsletterForm = document.querySelector('.newsletter-form');
if (newsletterForm) {
  newsletterForm.addEventListener('submit', function (e) {
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
}

/* ================== FOOTER ANIMATIONS ================== */
const footer = document.querySelector('.footer');
if (footer) {
  const footerSections = footer.querySelectorAll('.footer-section');
  footerSections.forEach(section => section.classList.add('hidden'));

  const footerObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        footerSections.forEach((section, index) => {
          setTimeout(() => section.classList.add('visible'), index * 200);
        });
        footerObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.2 });

  footerObserver.observe(footer);
}

/* ================== PAYMENT ICONS ================== */
document.querySelectorAll('.payment-icons i').forEach(icon => {
  icon.addEventListener('mouseenter', function () {
    this.style.transform = 'scale(1.2) rotateY(180deg)';
  });
  icon.addEventListener('mouseleave', function () {
    this.style.transform = 'scale(1) rotateY(0deg)';
  });
});

/* ================== COUNTERS ================== */
function animateCounter(element, target, duration = 2000) {
  let current = 0;
  const increment = target / (duration / 16);

  const timer = setInterval(() => {
    current += increment;
    if (current >= target) {
      current = target;
      clearInterval(timer);
    }
    element.textContent = Math.floor(current).toLocaleString();
  }, 16);
}

const statsSection = document.querySelector('.statistics');
if (statsSection) {
  const statsObserver = new IntersectionObserver(entries => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.querySelectorAll('.stat-number').forEach(statNumber => {
          const target = parseInt(statNumber.getAttribute('data-target')) || 0;
          animateCounter(statNumber, target, 2500);
        });

        entry.target.querySelectorAll('.stat-item').forEach((item, index) => {
          setTimeout(() => item.classList.add('visible'), index * 200);
        });

        statsObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.5 });

  statsObserver.observe(statsSection);
}

/* ================== FLOATING ICONS ================== */
document.querySelectorAll('.stat-icon').forEach((icon, index) => {
  icon.style.animation = `float 3s ease-in-out infinite ${index * 0.5}s`;
});

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
  .stat-number { animation: pulse 2s ease-in-out infinite; }
`;
document.head.appendChild(style);

/* ================== SCROLL ANIMATIONS ================== */
const scrollObserver = new IntersectionObserver(entries => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('visible');
      scrollObserver.unobserve(entry.target);
    }
  });
}, { threshold: 0.2 });

document.querySelectorAll('.product-card, .feature, .product-category').forEach(el => {
  el.classList.add('hidden');
  scrollObserver.observe(el);
});

/* ================== COUNTDOWN SIMULATION ================== */
function updateCountdown() {
  document.querySelectorAll('.countdown-number').forEach(item => {
    let current = parseInt(item.textContent);
    if (!isNaN(current) && current > 0) {
      current--;
      item.textContent = current.toString().padStart(2, '0');
    }
  });
}
setInterval(updateCountdown, 1000);

/* ================== HEADER & SIDEBAR ================== */
document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("menu-btn");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  const themeToggle = document.getElementById("theme-toggle");

  // Sidebar toggle
  if (menuBtn && sidebar && overlay) {
    menuBtn.addEventListener("click", () => {
      const isActive = sidebar.classList.toggle("active");
      overlay.classList.toggle("active", isActive);
      menuBtn.classList.toggle("open", isActive);
    });

    overlay.addEventListener("click", () => {
      sidebar.classList.remove("active");
      overlay.classList.remove("active");
      menuBtn.classList.remove("open");
    });
  }

  // Dark mode con localStorage
  if (themeToggle) {
    if (localStorage.getItem("theme") === "dark") {
      document.body.classList.add("dark");
      themeToggle.textContent = "☀️ Modo Claro";
    }

    themeToggle.addEventListener("click", () => {
      const isDark = document.body.classList.toggle("dark");
      themeToggle.textContent = isDark ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
      localStorage.setItem("theme", isDark ? "dark" : "light");
    });
  }
});

/* ================== HERO PARALLAX ================== */
window.addEventListener('scroll', () => {
  const parallax = document.querySelector('.hero');
  if (parallax) {
    const scrolled = window.pageYOffset;
    parallax.style.transform = `translateY(${scrolled * 0.3}px)`;
  }
});

/* ================== PAGE LOAD FADE ================== */
window.addEventListener('load', () => {
  document.body.style.opacity = '0';
  document.body.style.transition = 'opacity 0.5s ease-in';
  setTimeout(() => { document.body.style.opacity = '1'; }, 100);
});

/* ================== POS (solo si existe en el DOM) ================== */
const $productos = document.getElementById('productos');
if ($productos) {
  const d=document,
    $carritoLista=d.getElementById('carrito-lista'),
    $cSub=d.getElementById('c-subtotal'),
    $cDesc=d.getElementById('c-descuento'),
    $cIva=d.getElementById('c-iva'),
    $cTot=d.getElementById('c-total'),
    $statItems=d.getElementById('stat-items'),
    $statTotal=d.getElementById('stat-total'),
    $cCambio=d.getElementById('c-cambio'),
    $quick=d.querySelectorAll('.quick-add'),
    $chips=d.querySelectorAll('.chip');
  
  const state={ products: [], cart: [] };   

  fetch('data/products.json')
    .then(res=>res.json())
    .then(data=>{ state.products=data; renderProductos(); })
    .catch(err=>console.error('Error cargando productos:', err));

  function fmt(num){ return num.toLocaleString('es-ES',{style:'currency',currency:'EUR'}); }

  function renderProductos(){
    $productos.innerHTML='';
    state.products.forEach(p=>{
      const card=d.createElement('div');
      card.className='card';
      card.dataset.id=p.id;
      card.innerHTML=`
        <img src="${p.img}" alt="${p.nombre}">
        <h3>${p.nombre}</h3>
        <p class="price">${fmt(p.precio)}</p>
        <div class="qty-container">
          <input type="number" class="qty-input" value="1" min="1">
        </div>
        <button class="btn add">Añadir al carrito</button>`;
      $productos.appendChild(card);
    });
  }

  function findProd(id){ return state.products.find(p=>String(p.id)===String(id)); }

  function calcular(){
    let subtotal=0, descGlobal=0, iva=0, total=0, items=0, cambio=0;
    state.cart.forEach(it=>{
      const prod=findProd(it.id);
      if(!prod) return;
      let precioUnit=it.precio;
      let descPct=it.descPct||0;
      let descItem=precioUnit*it.qty*(descPct/100);
      let precioFinal=(precioUnit*it.qty)-descItem;
      subtotal+=precioUnit*it.qty;
      descGlobal+=descItem;
      items+=it.qty;
    });
    iva=(subtotal-descGlobal)*0.21; // IVA
    total=subtotal-descGlobal+iva;
    return {subtotal, descGlobal, iva, total, items, cambio};
  }

  function renderCarrito(){
    $carritoLista.innerHTML='';
    state.cart.forEach(it=>{
      const li = d.createElement('li');
      li.className='cart-item';
      li.innerHTML=`
        <div><strong>${it.nombre}</strong> (${fmt(it.precio)})</div>
        <div>
          Cant: ${it.qty}
          <button class="btn ghost small" data-act="menos" data-id="${it.id}">-</button>
          <button class="btn ghost small" data-act="mas" data-id="${it.id}">+</button>
          <button class="btn ghost small" data-act="del" data-id="${it.id}">×</button>
        </div>`;
      $carritoLista.appendChild(li);
    });
    const {subtotal, descGlobal, iva, total, items, cambio} = calcular();
    $cSub.textContent = fmt(subtotal);
    $cDesc.textContent = '-'+fmt(descGlobal);
    $cIva.textContent = fmt(iva);
    $cTot.textContent = fmt(total);
    $statItems.textContent = items+' ítems';
    $statTotal.textContent = fmt(total);
    $cCambio.textContent = fmt(cambio);
  }

  function addToCart(id, qty=1){
    const p=findProd(id); if(!p) return;
    const ex=state.cart.find(i=>i.id===p.id);
    if(ex) ex.qty+=qty;
    else state.cart.push({id:p.id,nombre:p.nombre,precio:+p.precio,qty,descPct:0,img:p.img});
    renderCarrito();
  }

  // Eventos UI
  $productos.addEventListener('click', e=>{
    const card=e.target.closest('.card'); if(!card) return;
    if(e.target.classList.contains('add')){
      const qty=Math.max(1,Number(card.querySelector('.qty-input')?.value||1));
      addToCart(card.dataset.id, qty);
    }
  });

  $carritoLista.addEventListener("click", e=>{
    if(!e.target.dataset.act) return;
    const id=e.target.dataset.id;
    const item=state.cart.find(i=>String(i.id)===String(id));
    if(!item) return;
    if(e.target.dataset.act==="mas") item.qty++;
    if(e.target.dataset.act==="menos") item.qty=Math.max(1,item.qty-1);
    if(e.target.dataset.act==="del") state.cart=state.cart.filter(i=>String(i.id)!==String(id));
    renderCarrito();
  });

  $quick.forEach(btn=>btn.addEventListener('click',()=> addToCart(btn.dataset.id)));

  $chips.forEach(chip=>{
    chip.addEventListener('click',()=>{
      const cat=chip.dataset.cat;
      document.querySelectorAll('.card').forEach(card=>{
        if(cat==='all' || card.dataset.id===cat || findProd(card.dataset.id)?.categoria===cat){
          card.style.display='block';
        } else {
          card.style.display='none';
        }
      });
      $chips.forEach(c=>c.classList.remove('active'));
      chip.classList.add('active');
    });
  });
}
