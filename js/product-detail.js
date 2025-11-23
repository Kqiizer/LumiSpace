// js/productos-detal.js
(() => {
  'use strict';

  /* ========== Helpers ========== */
  const $  = (sel, ctx=document) => ctx.querySelector(sel);
  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
  const fmtMoney = (n, loc='es-MX', cur='USD') =>
    new Intl.NumberFormat(loc, { style:'currency', currency:cur, minimumFractionDigits:2 }).format(n);

  const BASE = document.body?.dataset?.base || '/';
  const USER = parseInt(document.body?.dataset?.user || '0', 10) || 0;

  const info  = $('.product-info');
  if (!info) return; // no es página de detalle

  const pid   = parseInt(info.dataset.id || '0', 10);
  const pbase = parseFloat(info.dataset.price || '0');
  const stock = parseInt(info.dataset.stock || '0', 10);
  const pname = info.dataset.name || '';
  const pimg  = info.dataset.img  || '';

  /* ========== Mini-toast (imagen + nombre) ========== */
  function ensureToastStyles() {
    if (document.getElementById('ls-toast-styles')) return;
    const css = `
    .ls-toast-wrap{position:fixed;right:16px;bottom:16px;z-index:99999;display:flex;flex-direction:column;gap:10px}
    .ls-toast{display:flex;gap:12px;align-items:center;background:#fff;border:2px solid #d4c4a8;color:#8b7355;border-radius:14px;
      padding:12px 12px 12px 10px;box-shadow:0 10px 28px rgba(139,115,85,.18);max-width:360px;animation:lsToastIn .25s ease both}
    .ls-toast.dark{background:#2b2b2b;color:#f1f1f1;border-color:#555}
    .ls-toast__img{width:58px;height:58px;border-radius:10px;overflow:hidden;flex:0 0 58px;background:#f5f2ef}
    .ls-toast__img img{width:100%;height:100%;object-fit:cover;display:block}
    .ls-toast__body{flex:1;min-width:0}
    .ls-toast__title{font-weight:800;font-size:14px;margin-bottom:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ls-toast__pname{font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ls-toast__meta{font-size:12px;opacity:.8;margin-top:4px}
    .ls-toast__actions{display:flex;gap:8px;margin-left:6px}
    .ls-toast__btn{border:none;border-radius:10px;cursor:pointer;font-weight:700;padding:8px 12px}
    .ls-toast__btn--cart{background:#8b7355;color:#fff}
    .ls-toast__btn--keep{background:#fff;color:#8b7355;border:2px solid #d4c4a8}
    .ls-toast__close{background:transparent;border:none;color:inherit;font-size:18px;line-height:1;cursor:pointer;opacity:.6;margin-left:2px}
    @keyframes lsToastIn{from{transform:translateY(10px);opacity:0}to{transform:translateY(0);opacity:1}}
    @media (max-width:560px){
      .ls-toast-wrap{right:10px;left:10px;bottom:10px}
      .ls-toast{max-width:100%}
      .ls-toast__actions{display:none}
    }`;
    const style = document.createElement('style');
    style.id = 'ls-toast-styles';
    style.textContent = css;
    document.head.appendChild(style);
  }
  function showAddedToast({name, img, qty, unitPrice}) {
    ensureToastStyles();
    let wrap = document.querySelector('.ls-toast-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'ls-toast-wrap';
      document.body.appendChild(wrap);
    }

    const total = (qty > 0 && unitPrice > 0) ? qty * unitPrice : 0;

    const toast = document.createElement('div');
    toast.className = 'ls-toast';
    toast.innerHTML = `
      <div class="ls-toast__img">
        <img src="${img || ''}" alt="${(name || 'Producto').replace(/"/g,'&quot;')}">
      </div>
      <div class="ls-toast__body">
        <div class="ls-toast__title"><i class="fas fa-check-circle" style="margin-right:6px"></i>¡Agregado al carrito!</div>
        <div class="ls-toast__pname">${(name || 'Producto agregado').replace(/</g,'&lt;')}</div>
        <div class="ls-toast__meta">${qty} × ${fmtMoney(unitPrice)} = <strong>${fmtMoney(total)}</strong></div>
      </div>
      <div class="ls-toast__actions">
        <button class="ls-toast__btn ls-toast__btn--cart"><i class="fas fa-shopping-cart"></i> Ver carrito</button>
        <button class="ls-toast__btn ls-toast__btn--keep">Seguir comprando</button>
        <button class="ls-toast__close" aria-label="Cerrar">&times;</button>
      </div>
    `;
    wrap.appendChild(toast);

    // Botones
    toast.querySelector('.ls-toast__btn--cart')?.addEventListener('click', () => {
      window.location.href = `${BASE}includes/carrito.php`;
    });
    toast.querySelector('.ls-toast__btn--keep')?.addEventListener('click', () => {
      kill();
    });
    toast.querySelector('.ls-toast__close')?.addEventListener('click', () => {
      kill();
    });

    // Autocierre
    let t = setTimeout(kill, 4200);
    toast.addEventListener('mouseenter', () => { clearTimeout(t); });
    toast.addEventListener('mouseleave', () => { t = setTimeout(kill, 1500); });

    function kill(){
      toast.style.transition = 'opacity .2s ease, transform .2s ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(6px)';
      setTimeout(() => toast.remove(), 220);
    }
  }

  /* ========== Galería ========== */
  const mainImg = $('#mainImage');
  const zoomImg = $('#zoomedImage');

  $$('#thumbs .thumbnail img').forEach((img) => {
    img.addEventListener('click', () => {
      $$('#thumbs .thumbnail').forEach(t => t.classList.remove('active'));
      img.closest('.thumbnail')?.classList.add('active');
      if (mainImg) mainImg.src = img.src;
      if (zoomImg) zoomImg.src = img.src;
    });
  });

  /* ========== Zoom ========== */
  const zoomBtn   = $('#zoomBtn');
  const zoomModal = $('#zoomModal');
  const zoomClose = $('#zoomClose');

  zoomBtn?.addEventListener('click', () => {
    zoomModal?.classList.add('open');
    document.body.style.overflow = 'hidden';
  });
  zoomClose?.addEventListener('click', () => {
    zoomModal?.classList.remove('open');
    document.body.style.overflow = '';
  });
  zoomModal?.addEventListener('click', (e) => {
    if (e.target.classList?.contains('zoom-overlay')) {
      zoomModal.classList.remove('open');
      document.body.style.overflow = '';
    }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && zoomModal?.classList.contains('open')) {
      zoomModal.classList.remove('open');
      document.body.style.overflow = '';
    }
  });

  /* ========== Cantidad + CTA dinámico ========== */
  const qtyInput = $('#qtyInput');
  const qtyMinus = $('#qtyMinus');
  const qtyPlus  = $('#qtyPlus');
  const addBtn   = $('#addToCartBtn');
  const buyBtn   = $('#buyNowBtn');

  const getQty = () => {
    let v = parseInt(qtyInput?.value || '1', 10);
    if (isNaN(v) || v < 1) v = 1;
    if (stock > 0 && v > stock) v = stock;
    return v;
  };
  function updateQtyControls() {
    const v = getQty();
    if (qtyInput) qtyInput.value = String(v);
    if (qtyMinus) qtyMinus.disabled = v <= 1;
    if (qtyPlus)  qtyPlus.disabled  = stock > 0 ? v >= stock : false;
  }
  function updateAddBtnPrice() {
    if (!addBtn) return;
    const qty   = getQty();
    const total = pbase > 0 ? pbase * qty : 0;
    let label = addBtn.querySelector('span.__label');
    if (!label) {
      const iconHTML = addBtn.innerHTML.includes('</i>')
        ? addBtn.innerHTML.slice(0, addBtn.innerHTML.indexOf('</i>') + 4)
        : '';
      addBtn.innerHTML = `${iconHTML}<span class="__label"></span>`;
      label = addBtn.querySelector('span.__label');
    }
    label.textContent = `Add to Cart - ${fmtMoney(total)}`;
  }
  qtyMinus?.addEventListener('click', () => { if (qtyInput) qtyInput.value = String(Math.max(1, getQty()-1)); updateQtyControls(); updateAddBtnPrice(); });
  qtyPlus ?.addEventListener('click', () => { if (qtyInput) qtyInput.value = String(getQty()+1); updateQtyControls(); updateAddBtnPrice(); });
  qtyInput?.addEventListener('input', () => { updateQtyControls(); updateAddBtnPrice(); });
  updateQtyControls();
  updateAddBtnPrice();

  /* ========== Opciones (color/size/material) ========== */
  $$('.color-option').forEach(o => o.addEventListener('click', () => {
    $$('.color-option').forEach(x => x.classList.remove('active'));
    o.classList.add('active');
  }));
  $$('.size-option').forEach(o => o.addEventListener('click', () => {
    $$('.size-option').forEach(x => x.classList.remove('active'));
    o.classList.add('active');
  }));
  $$('.material-option').forEach(o => o.addEventListener('click', () => {
    $$('.material-option').forEach(x => x.classList.remove('active'));
    o.classList.add('active');
  }));

  /* ========== Tabs ========== */
  $$('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const target = btn.dataset.tab;
      $$('.tab-btn').forEach(b => b.classList.remove('active'));
      $$('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      if (target) document.getElementById(target)?.classList.add('active');
    });
  });

  /* ========== Badge helpers ========== */
  function bumpBadge(selector, delta=1) {
    const badge = $(selector);
    if (!badge) return;
    const n = parseInt(badge.textContent || '0', 10) || 0;
    badge.textContent = String(Math.max(0, n + delta));
  }

  /* ========== Carrito (API + fallback) ========== */
  async function addToCart(quantity=1) {
    // 1) API JSON
    try {
      const res = await fetch(`${BASE}api/cart/add.php`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ producto_id: pid, cantidad: quantity })
      });
      if (res.status === 401) {
        // sin login → no bloqueamos aquí
      } else if (res.ok) {
        // si tu API devuelve conteo, podrías leerlo aquí:
        // const data = await res.json().catch(()=>null);
        return { ok:true };
      }
    } catch {/* ignore */}

    // 2) Fallback GET al carrito (agrega y redirige)
    window.location.href = `${BASE}includes/carrito.php?add=${encodeURIComponent(pid)}&qty=${encodeURIComponent(quantity)}&r=${Date.now()}`;
    return { ok:false, redirected:true };
  }

  /* ========== Add to cart (con toast) ========== */
  addBtn?.addEventListener('click', async () => {
    if (!pid) return;
    const qty = getQty();

    addBtn.disabled = true;
    const original = addBtn.innerHTML;
    addBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Agregando...';

    const r = await addToCart(qty);

    if (!r.redirected) {
      // éxito por API sin salir de la página
      bumpBadge('.fa-shopping-cart .cart-badge', qty);
      addBtn.innerHTML = '<i class="fas fa-check"></i> ¡Agregado!';

      // 👉 Mostrar el toast con imagen + nombre
      showAddedToast({
        name: pname,
        img:  pimg || (mainImg?.src || ''),
        qty:  qty,
        unitPrice: pbase || 0
      });

      setTimeout(() => { addBtn.innerHTML = original; addBtn.disabled = false; }, 900);
    }
  });

  /* ========== Comprar ahora (redirige) ========== */
  buyBtn?.addEventListener('click', async () => {
    if (!pid) return;
    const qty = getQty();

    if (!USER) {
      const nextAfter = `${BASE}includes/carrito.php?add=${encodeURIComponent(pid)}&qty=${encodeURIComponent(qty)}`;
      window.location.href = `${BASE}views/login.php?next=${encodeURIComponent(nextAfter)}`;
      return;
    }

    buyBtn.disabled = true;
    const original = buyBtn.innerHTML;
    buyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

    const r = await addToCart(qty);
    if (!r.redirected) {
      window.location.href = `${BASE}includes/carrito.php`;
    }
  });

  /* ========== Wishlist / Relacionados / Animaciones (igual que antes) ========== */
  const wishBtn = $('#wishlistBtn');
  wishBtn?.addEventListener('click', async () => {
    if (!USER) {
      const next = location.pathname + location.search;
      location.href = `${BASE}views/login.php?next=${encodeURIComponent(next)}`;
      return;
    }
    wishBtn.disabled = true;
    try {
      const res = await fetch(`${BASE}api/wishlist/toggle.php`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ producto_id: pid })
      });
      if (res.status === 401) {
        const next = location.pathname + location.search;
        location.href = `${BASE}views/login.php?next=${encodeURIComponent(next)}`;
        return;
      }
      const data = await res.json();
      if (!data.ok) throw new Error(data.msg || 'Error');
      if (data.in_wishlist) {
        bumpBadge('.fa-heart .cart-badge', 1);
        location.href = `${BASE}index/favoritos.php`;
      } else {
        bumpBadge('.fa-heart .cart-badge', -1);
      }
    } catch {
      alert('No se pudo actualizar tu lista de favoritos.');
    } finally {
      wishBtn.disabled = false;
    }
  });

  const LS_COMP = 'ls_compare';
  const compareBtn = $('#compareBtn');
  const getSet  = (key) => new Set(JSON.parse(localStorage.getItem(key) || '[]'));
  const saveSet = (key, set) => localStorage.setItem(key, JSON.stringify([...set]));
  function toggleCompare(id) {
    const s = getSet(LS_COMP);
    const k = String(id);
    s.has(k) ? s.delete(k) : s.add(k);
    saveSet(LS_COMP, s);
  }
  compareBtn?.addEventListener('click', () => toggleCompare(pid));

  const relWrap = document.querySelector('.related-products');
  relWrap?.addEventListener('click', async (e) => {
    const w = e.target.closest('.rel-wish');
    if (!w) return;
    if (!USER) {
      const next = location.pathname + location.search;
      location.href = `${BASE}views/login.php?next=${encodeURIComponent(next)}`;
      return;
    }
    try {
      const res = await fetch(`${BASE}api/wishlist/toggle.php`, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ producto_id: parseInt(w.dataset.id || '0', 10) })
      });
      const data = await res.json();
      if (data.in_wishlist) location.href = `${BASE}index/favoritos.php`;
    } catch {/* ignore */}
  });
  relWrap?.addEventListener('click', (e) => {
    const c = e.target.closest('.rel-comp');
    if (!c) return;
    toggleCompare(c.dataset.id);
  });

  window.addEventListener('load', () => {
    document.body.style.opacity = '0';
    document.body.style.transition = 'opacity .45s ease';
    requestAnimationFrame(() => { document.body.style.opacity = '1'; });
  });

  console.log('Product detail script ready (con toast de agregado)');
})();
