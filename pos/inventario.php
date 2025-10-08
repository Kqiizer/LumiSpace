<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
start_pos_page('Inventario', $cajeroNombre, $cajaLabel);
?>


<!-- Filtros -->
<div class="card" style="margin-bottom:20px">
  <form id="filtrosInv" style="display:grid;grid-template-columns: 2fr 1fr 1fr 1fr auto; gap:12px; align-items:end;">
    <div>
      <label>Buscar producto</label>
      <input id="q" class="input" placeholder="Nombre del producto...">
    </div>
    <div>
      <label>Estado</label>
      <select id="estado" class="input">
        <option value="">(todos)</option>
        <option value="en_stock">En stock</option>
        <option value="bajo">Stock bajo</option>
        <option value="agotado">Agotado</option>
      </select>
    </div>
    <div>
      <label>Categoría</label>
      <input id="categoria" class="input" placeholder="Ej. LED">
    </div>
    <div>
      <label>Ordenar por</label>
      <select id="order" class="input">
        <option value="fecha_creado">Recientes</option>
        <option value="nombre">Nombre</option>
        <option value="stock">Stock</option>
        <option value="precio">Precio</option>
      </select>
    </div>
    <div>
      <label style="opacity:0">.</label>
      <button class="btn" id="btnBuscar" type="submit">Aplicar filtros</button>
    </div>
  </form>
</div>

<div class="grid" style="grid-template-columns: 2fr 1fr; gap:12px;">
  <!-- Tabla productos -->
  <div class="card">
    <div style="overflow:auto;">
      <table class="table">
        <thead>
          <tr>
            <th>ID</th><th>Producto</th><th style="text-align:right">Precio</th><th style="text-align:right">Stock</th><th>Categoría</th><th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyP">
          <tr><td colspan="6" style="text-align:center;color:#777">(pendiente)</td></tr>
        </tbody>
      </table>
    </div>

    <!-- Paginación -->
    <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:8px;">
      <button id="pPrev" class="btn" disabled>Anterior</button>
      <div id="pLbl" style="display:grid;place-items:center;padding:0 8px;color:#555">1 / 1</div>
      <button id="pNext" class="btn" disabled>Siguiente</button>
    </div>
  </div>

  <!-- Panel edición / ajuste -->
  <div class="card">
    <h3 style="margin-top:0;">Edición rápida</h3>
    <div id="selInfo" style="font-size:13px;color:#777;margin-bottom:8px;">(selecciona un producto)</div>

    <form id="frmEdit" style="display:grid;gap:8px;">
      <input type="hidden" id="eId">
      <div>
        <label style="font-size:12px;color:#777">Nombre</label>
        <input id="eNombre" class="input" placeholder="Nombre" disabled>
      </div>
      <div>
        <label style="font-size:12px;color:#777">Precio</label>
        <input id="ePrecio" class="input" inputmode="decimal" placeholder="0.00" disabled>
      </div>
      <div style="display:flex;gap:8px;">
        <button id="btnGuardarInfo" class="btn" type="button" disabled>Guardar cambios</button>
      </div>
    </form>

    <hr style="margin:12px 0">

    <h3 style="margin-top:0;">Ajuste de stock</h3>
    <form id="frmAjuste" style="display:grid;gap:8px;">
      <div>
        <label style="font-size:12px;color:#777">Cantidad (+ entrada / - salida)</label>
        <input id="aCantidad" class="input" inputmode="numeric" value="0" disabled>
      </div>
      <div>
        <label style="font-size:12px;color:#777">Motivo</label>
        <input id="aMotivo" class="input" placeholder="Ajuste / corrección / merma..." disabled>
      </div>
      <div style="display:flex;gap:8px;">
        <button id="btnAjustar" class="btn" type="button" disabled>Guardar ajuste</button>
      </div>
    </form>
  </div>
</div>

<script>
(function InvPage(){
  const $  = (s,ctx=document)=>ctx.querySelector(s);
  const $$ = (s,ctx=document)=>Array.from(ctx.querySelectorAll(s));
  const fmt = n => '$'+Number(n||0).toFixed(2);

  // filtros/paginación
  const q = $('#q'), estado=$('#estado'), categoria=$('#categoria'), order=$('#order');
  const btnBuscar = $('#btnBuscar'), frmFiltros = $('#filtrosInv');
  const tb = $('#tbodyP'), pPrev=$('#pPrev'), pNext=$('#pNext'), pLbl=$('#pLbl');
  let st = { page:1, per_page:20, total:0, low:5 };

  // panel
  const eId=$('#eId'), eNombre=$('#eNombre'), ePrecio=$('#ePrecio'), btnGuardarInfo=$('#btnGuardarInfo');
  const aCantidad=$('#aCantidad'), aMotivo=$('#aMotivo'), btnAjustar=$('#btnAjustar');
  const selInfo=$('#selInfo');

  function enablePanel(enable){
    [eNombre,ePrecio,btnGuardarInfo,aCantidad,aMotivo,btnAjustar].forEach(el=>el.disabled=!enable);
  }

  async function api(body){
    const r = await fetch('api.php',{method:'POST', body:new URLSearchParams(body)});
    return r.json();
  }

  async function cargar(){
    const r = await api({
      action:'productos_list',
      q:q.value.trim(),
      estado:estado.value,
      categoria:categoria.value.trim(),
      order:order.value,
      dir:'asc',
      low_threshold: st.low,
      page:st.page,
      per_page:st.per_page
    });
    if(!r.ok){ alert(r.error||'Error'); return; }

    tb.innerHTML='';
    if(!r.data || !r.data.length){
      tb.innerHTML='<tr><td colspan="6" style="text-align:center;color:#777">(sin datos)</td></tr>';
    }else{
      r.data.forEach(p=>{
        const tr=document.createElement('tr');
        tr.innerHTML=`
          <td>${p.id}</td>
          <td>${p.nombre}</td>
          <td style="text-align:right">${fmt(p.precio)}</td>
          <td style="text-align:right">${p.stock}</td>
          <td>${p.categoria||'-'}</td>
          <td><button class="btn btn-sm" data-editar='${JSON.stringify(p)}'>Editar</button></td>
        `;
        tb.appendChild(tr);
      });
    }

    // paginación
    st.total = r.total; st.page = r.page; st.per_page = r.per_page;
    const pages = Math.max(1, Math.ceil(st.total/st.per_page));
    pLbl.textContent = `${st.page} / ${pages}`;
    pPrev.disabled = st.page<=1;
    pNext.disabled = st.page>=pages;
  }

  frmFiltros.addEventListener('submit', ev=>{ ev.preventDefault(); st.page=1; cargar(); });
  pPrev.addEventListener('click', ()=>{ if(st.page>1){ st.page--; cargar(); }});
  pNext.addEventListener('click', ()=>{ const pages=Math.max(1,Math.ceil(st.total/st.per_page)); if(st.page<pages){ st.page++; cargar(); }});

  // seleccionar producto
  tb.addEventListener('click', ev=>{
    const btn = ev.target.closest('[data-editar]');
    if(!btn) return;
    const p = JSON.parse(btn.getAttribute('data-editar'));
    eId.value = p.id;
    eNombre.value = p.nombre;
    ePrecio.value = Number(p.precio||0).toFixed(2);
    aCantidad.value = 0;
    aMotivo.value = '';
    selInfo.textContent = `Seleccionado: #${p.id} — ${p.nombre}`;
    enablePanel(true);
  });

  // guardar info rápida
  btnGuardarInfo.addEventListener('click', async ()=>{
    if(!eId.value) return;
    const r = await api({action:'productos_update_quick', id:eId.value, nombre:eNombre.value, precio:ePrecio.value});
    if(!r.ok){ alert(r.error||'No se pudo guardar'); return; }
    alert('Guardado');
    cargar();
  });

  // ajuste de stock
  btnAjustar.addEventListener('click', async ()=>{
    if(!eId.value) return;
    const cantidad = parseInt(aCantidad.value,10)||0;
    if(!cantidad){ alert('Cantidad inválida'); return; }

    // cajero desde turno actual (caja en localStorage)
    let caja = 'Caja 1';
    try{ if (typeof getCajaLS==='function') caja = getCajaLS(); }catch(e){}
    const t = await api({action:'turno_actual', caja_id:caja});
    const uid = (t.ok && t.turno && t.turno.cajero_id) ? t.turno.cajero_id : 0;

    const r = await api({
      action:'inventario_ajuste',
      producto_id: eId.value,
      cantidad: cantidad, // +entrada / -salida
      motivo: aMotivo.value || 'Ajuste',
      usuario_id: uid
    });
    if(!r.ok){ alert(r.error||'No se pudo ajustar'); return; }
    alert('Ajuste aplicado');
    cargar();
  });

  // inicio
  enablePanel(false);
  cargar();
})();
</script>

<?php end_pos_page(); ?>
