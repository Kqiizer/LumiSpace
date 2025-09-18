(function(){
  const MX = new Intl.NumberFormat('es-MX',{style:'currency',currency:'MXN',maximumFractionDigits:0});
  const IVA_RATE = 0.16;
  const d = document;

  // UI refs
  const $fecha = d.getElementById('pos-fecha');
  const $productos = d.getElementById('productos');
  const $buscador = d.getElementById('buscador');
  const $chips = d.querySelectorAll('.chip');
  const $quick = d.querySelectorAll('.quick-item');

  const $carritoLista = d.getElementById('carritoLista');
  const $cSub = d.getElementById('cSub'),
        $cIva = d.getElementById('cIva'),
        $cTot = d.getElementById('cTot'),
        $cDesc = d.getElementById('cDesc');
  const $statItems = d.getElementById('statItems'),
        $statTotal = d.getElementById('statTotal');
  const $descGlobalPct = d.getElementById('descGlobalPct');
  const $ticketNota = d.getElementById('ticketNota');
  const $pEfectivo = d.getElementById('pEfectivo'),
        $pTarjeta = d.getElementById('pTarjeta'),
        $pTransf = d.getElementById('pTransf'),
        $cCambio = d.getElementById('cCambio');
  const $btnLimpiar = d.getElementById('btnLimpiar'),
        $btnPagar = d.getElementById('btnPagar');

  // Datos
  const catalogo = (window.CATALOGO_POS||[]).map(p=>({...p, categorias:Array.isArray(p.categoria)?p.categoria:(p.categorias||[])}));

  const state = {
    filtro:'todos',
    texto:'',
    cart:[],
    descGlobalPct:0,
    pagos:{efectivo:0, tarjeta:0, transferencia:0},
    nota:''
  };

  // Fecha
  if ($fecha) $fecha.textContent = new Date().toLocaleString('es-MX',{weekday:'long',year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'});

  // Helpers
  const fmt = v=>MX.format(Math.round(v));
  const findProd = id=>catalogo.find(p=>String(p.id)===String(id));

  // Render productos
  function renderProductos(){
    [...$productos.querySelectorAll('.card')].forEach(card=>{
      const nombre = card.dataset.nombre.toLowerCase();
      const cats = JSON.parse(card.dataset.categorias||'[]');
      const matchText = state.texto==='' || nombre.includes(state.texto);
      const matchCat = state.filtro==='todos' || cats.includes(state.filtro);
      card.style.display = (matchText && matchCat) ? '' : 'none';
    });
  }

  // Calcular totales
  function calcular(){
    let subtotal=0, items=0;
    state.cart.forEach(it=>{
      const precioDesc = it.precio * (1 - (Number(it.descPct||0)/100));
      subtotal += precioDesc * it.qty;
      items += it.qty;
    });
    const descGlobal = subtotal * (Number(state.descGlobalPct||0)/100);
    const base = subtotal - descGlobal;
    const iva = base * IVA_RATE;
    const total = base + iva;

    const entregado = Number(state.pagos.efectivo||0)+Number(state.pagos.tarjeta||0)+Number(state.pagos.transferencia||0);
    const cambio = Math.max(0, entregado - total);

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

  $quick.forEach(btn=>btn.addEventListener('click',()=> addToCart(btn.dataset.id)));

  $chips.forEach(ch=>ch.addEventListener('click',()=>{
    $chips.forEach(c=>c.classList.remove('is-active'));
    ch.classList.add('is-active');
    state.filtro=ch.dataset.filter;
    renderProductos();
  }));

  $buscador.addEventListener('input',()=>{
    state.texto=$buscador.value.toLowerCase().trim();
    renderProductos();
  });

  // Pagos
  function readPagos(){
    state.pagos.efectivo=+$pEfectivo.value||0;
    state.pagos.tarjeta=+$pTarjeta.value||0;
    state.pagos.transferencia=+$pTransf.value||0;
    renderCarrito();
  }
  [$pEfectivo,$pTarjeta,$pTransf].forEach(i=>i.addEventListener('input', readPagos));

  // Limpiar
  $btnLimpiar.addEventListener('click',()=>{
    state.cart=[]; state.descGlobalPct=0; $descGlobalPct.value=0;
    $pEfectivo.value=$pTarjeta.value=$pTransf.value=0; readPagos();
    $ticketNota.value=''; state.nota='';
    renderCarrito();
  });

  // Procesar pago → ahora apunta a guardar_venta.php
  $btnPagar.addEventListener('click', async ()=>{
    if(state.cart.length===0) return alert('Agrega productos.');
    const {total}=calcular();
    const pagado=state.pagos.efectivo+state.pagos.tarjeta+state.pagos.transferencia;
    if(pagado<total) return alert('El pago no cubre el total.');

    const payload={
      total: total,
      metodo: (state.pagos.tarjeta>0?"tarjeta": (state.pagos.transferencia>0?"transferencia":"efectivo")),
      nota: $ticketNota.value,
      detalles: state.cart
    };

    try{
      const res=await fetch('../api/guardar_venta.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(payload)
      });
      const data=await res.json();
      if(!data.ok) throw new Error(data.msg||'Error servidor');
      alert(`✅ Venta #${data.id} registrada. Total: ${fmt(total)}`);
      $btnLimpiar.click();
    }catch(err){
      console.error(err);
      alert('❌ No se pudo registrar la venta.');
    }
  });

  // Inicial
  renderProductos();
  renderCarrito();
})();
