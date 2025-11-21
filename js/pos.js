(function () {
  // ======================
  // Config
  // ======================
  const ENDPOINT_VENTA = "../gestor/pos-procesar.php"; // o "../api/ventas/guardar.php"
  const OPEN_TICKET_PDF = true;
  const OPEN_TICKET_HTML = true;

  // ======================
  // Util / Constantes
  // ======================
  const MX = new Intl.NumberFormat("es-MX", {
    style: "currency",
    currency: "MXN",
    maximumFractionDigits: 0,
  });
  const IVA_RATE = 0.16;
  const d = document;

  // ======================
  // Refs UI (tolerantes)
  // ======================
  const $fecha = d.getElementById("pos-fecha");
  const $productos = d.getElementById("productos");
  const $buscador = d.getElementById("buscador");
  const $chips = d.querySelectorAll(".chip");
  const $quick = d.querySelectorAll(".quick-item");

  const $carritoLista = d.getElementById("carritoLista");
  const $cSub = d.getElementById("cSub");
  const $cIva = d.getElementById("cIva");
  const $cTot = d.getElementById("cTot");
  const $cDesc = d.getElementById("cDesc");
  const $statItems = d.getElementById("statItems");
  const $statTotal = d.getElementById("statTotal");
  const $descGlobalPct = d.getElementById("descGlobalPct");
  const $ticketNota = d.getElementById("ticketNota");
  const $pEfectivo = d.getElementById("pEfectivo");
  const $pTarjeta = d.getElementById("pTarjeta");
  const $pTransf = d.getElementById("pTransf");
  const $cCambio = d.getElementById("cCambio");
  const $btnLimpiar = d.getElementById("btnLimpiar");
  const $btnPagar = d.getElementById("btnPagar");
  const $ventasRecientes = d.getElementById("ventasRecientes");

  // ======================
  // Datos
  // ======================
  // CATALOGO_POS viene del PHP (array de productos con {id,nombre,precio,stock,categoria,img})
  const catalogo = (window.CATALOGO_POS || []).map((p) => ({
    ...p,
    // normalizamos por si viniera algo raro
    id: String(p.id),
    precio: +p.precio,
    stock: +p.stock,
    categoria: p.categoria || "General",
  }));

  const state = {
    filtro: "todos",
    texto: "",
    cart: [], // [{id,nombre,precio,qty,img}]
    descGlobalPct: 0,
    pagos: { efectivo: 0, tarjeta: 0, transferencia: 0 },
    nota: "",
  };

  // ======================
  // Fecha encabezado
  // ======================
  if ($fecha) {
    $fecha.textContent = new Date().toLocaleString("es-MX", {
      weekday: "long",
      year: "numeric",
      month: "long",
      day: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  // ======================
  // Helpers
  // ======================
  const fmt = (v) => MX.format(Math.round(v));
  const findProd = (id) => catalogo.find((p) => p && String(p.id) === String(id));

  // ======================
  // Render productos (buscador + chips)
  // ======================
  function renderProductos() {
    if (!$productos) return;
    [...$productos.querySelectorAll(".card")].forEach((card) => {
      const nombre = (card.dataset.nombre || "").toLowerCase();
      const cat = (card.dataset.categoria || "").toLowerCase();

      const matchText = state.texto === "" || nombre.includes(state.texto);
      const matchCat = state.filtro === "todos" || cat === state.filtro.toLowerCase();
      card.style.display = matchText && matchCat ? "" : "none";
    });
  }

  // ======================
  // Calcular totales
  // ======================
  function calcular() {
    let subtotal = 0,
      items = 0;
    state.cart.forEach((it) => {
      const precioDesc = it.precio * (1 - Number(it.descPct || 0) / 100);
      subtotal += precioDesc * it.qty;
      items += it.qty;
    });
    const descGlobal = subtotal * (Number(state.descGlobalPct || 0) / 100);
    const base = subtotal - descGlobal;
    const iva = base * IVA_RATE;
    const total = base + iva;

    const entregado =
      Number(state.pagos.efectivo || 0) +
      Number(state.pagos.tarjeta || 0) +
      Number(state.pagos.transferencia || 0);
    const cambio = Math.max(0, entregado - total);

    return { subtotal, descGlobal, iva, total, items, cambio };
  }

  // ======================
  // Actualizar UI de stock dinámico por tarjeta
  // ======================
  function actualizarStockUI() {
    if (!$productos) return;
    catalogo.forEach((p) => {
      const card = $productos.querySelector(`.card[data-id="${p.id}"]`);
      if (!card) return;
      const enCarrito = state.cart.find((i) => i.id === p.id)?.qty || 0;
      const stockRestante = p.stock - enCarrito;
      const stockSpan = card.querySelector(".stock");
      const btnAdd = card.querySelector(".btn.add");

      if (!stockSpan) return;

      if (stockRestante <= 0) {
        stockSpan.textContent = `Sin stock (0)`;
        stockSpan.className = "stock agotado";
        if (btnAdd) btnAdd.disabled = true;
      } else {
        const etiqueta =
          stockRestante > 20 ? "Disponible" : stockRestante > 5 ? "Poco stock" : "Stock bajo";
        stockSpan.textContent = `${etiqueta} (${stockRestante})`;
        stockSpan.className =
          "stock " + (stockRestante > 20 ? "disponible" : stockRestante > 5 ? "poco" : "bajo");
        if (btnAdd) btnAdd.disabled = false;
      }
    });
  }

  // ======================
  // Render carrito
  // ======================
  function renderCarrito() {
    if ($carritoLista) {
      $carritoLista.innerHTML = "";
      state.cart.forEach((it) => {
        const li = d.createElement("li");
        li.className = "cart-item";
        li.innerHTML = `
          <div><strong>${it.nombre}</strong> (${fmt(it.precio)})</div>
          <div>
            Cant: ${it.qty}
            <button class="btn ghost small" data-act="menos" data-id="${it.id}">-</button>
            <button class="btn ghost small" data-act="mas" data-id="${it.id}">+</button>
            <button class="btn ghost small" data-act="del" data-id="${it.id}">×</button>
          </div>`;
        $carritoLista.appendChild(li);
      });
    }

    const { subtotal, descGlobal, iva, total, items, cambio } = calcular();
    if ($cSub) $cSub.textContent = fmt(subtotal);
    if ($cDesc) $cDesc.textContent = "-" + fmt(descGlobal);
    if ($cIva) $cIva.textContent = fmt(iva);
    if ($cTot) $cTot.textContent = fmt(total);
    if ($statItems) $statItems.textContent = items + " ítems";
    if ($statTotal) $statTotal.textContent = fmt(total);
    if ($cCambio) $cCambio.textContent = fmt(cambio);

    actualizarStockUI();
  }

  // ======================
  // Agregar al carrito con control stock
  // ======================
  function addToCart(id, qty = 1) {
    const p = findProd(id);
    if (!p) return;
    const ex = state.cart.find((i) => i.id === p.id);
    const enCarrito = ex ? ex.qty : 0;

    if (enCarrito + qty > p.stock) {
      const rest = p.stock - enCarrito;
      alert(
        `⚠️ Stock insuficiente de ${p.nombre}. ` +
          (rest > 0 ? `Solo puedes agregar ${rest} más.` : `Ya alcanzaste el máximo disponible.`)
      );
      return;
    }

    if (ex) ex.qty += qty;
    else state.cart.push({ id: p.id, nombre: p.nombre, precio: +p.precio, qty, descPct: 0, img: p.img });

    renderCarrito();
  }

  // ======================
  // Eventos UI
  // ======================
  if ($productos) {
    $productos.addEventListener("click", (e) => {
      const btn = e.target.closest(".btn.add");
      if (!btn) return;
      const card = e.target.closest(".card");
      if (!card) return;
      addToCart(card.dataset.id, 1);
    });
  }

  if ($carritoLista) {
    $carritoLista.addEventListener("click", (e) => {
      const act = e.target.dataset.act;
      if (!act) return;
      const id = e.target.dataset.id;
      const item = state.cart.find((i) => i.id === id);
      if (!item) return;

      if (act === "mas") addToCart(id, 1);
      if (act === "menos") item.qty = Math.max(1, item.qty - 1);
      if (act === "del") state.cart = state.cart.filter((i) => i.id !== id);

      renderCarrito();
    });
  }

  $quick.forEach((btn) => btn.addEventListener("click", () => addToCart(btn.dataset.id)));

  $chips.forEach((ch) =>
    ch.addEventListener("click", () => {
      $chips.forEach((c) => c.classList.remove("is-active"));
      ch.classList.add("is-active");
      state.filtro = ch.dataset.filter || "todos";
      renderProductos();
    })
  );

  if ($buscador) {
    $buscador.addEventListener("input", () => {
      state.texto = $buscador.value.toLowerCase().trim();
      renderProductos();
    });
  }

  // ======================
  // Pagos
  // ======================
  function readPagos() {
    state.pagos.efectivo = +($pEfectivo?.value || 0) || 0;
    state.pagos.tarjeta = +($pTarjeta?.value || 0) || 0;
    state.pagos.transferencia = +($pTransf?.value || 0) || 0;
    renderCarrito();
  }
  [$pEfectivo, $pTarjeta, $pTransf].forEach((i) => i && i.addEventListener("input", readPagos));

  // ======================
  // Limpiar
  // ======================
  if ($btnLimpiar) {
    $btnLimpiar.addEventListener("click", () => {
      state.cart = [];
      state.descGlobalPct = 0;
      if ($descGlobalPct) $descGlobalPct.value = 0;
      if ($pEfectivo) $pEfectivo.value = 0;
      if ($pTarjeta) $pTarjeta.value = 0;
      if ($pTransf) $pTransf.value = 0;
      readPagos();
      if ($ticketNota) $ticketNota.value = "";
      state.nota = "";
      renderCarrito();
    });
  }

  // ======================
  // Procesar pago
  // ======================
  if ($btnPagar) {
    $btnPagar.addEventListener("click", async () => {
      if (state.cart.length === 0) return alert("Agrega productos.");
      const { total } = calcular();
      const pagado =
        state.pagos.efectivo + state.pagos.tarjeta + state.pagos.transferencia;
      if (pagado < total) return alert("El pago no cubre el total.");

      const payload = {
        cliente_id: null,
        usuario_id: window.USUARIO_ID || 0,
        metodo_pago: "efectivo",
        total: total, // mandamos lo calculado
        items: state.cart.map((it) => ({
          producto_id: it.id,
          cantidad: it.qty,
          precio: it.precio,
        })),
        // opcional: desc y pagos detallados si tu endpoint los guarda
        desc_global_pct: state.descGlobalPct,
        pagos: state.pagos,
        nota: $ticketNota?.value || "",
      };

      try {
        const res = await fetch(ENDPOINT_VENTA, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(payload),
        });
        const data = await res.json();
        // Aceptamos tanto {success:true, venta_id} como {ok:true, venta_id}
        if (!(data && (data.success || data.ok) && data.venta_id)) {
          throw new Error(data?.msg || "Error en servidor");
        }

        alert(`✅ Venta #${data.venta_id} registrada. Total: ${fmt(total)}`);

        // Refrescar dashboard del POS (si existe)
        cargarDashboard();

        // Tickets
        if (OPEN_TICKET_PDF) {
          window.open(`../api/ventas/ticket.php?id=${data.venta_id}`, "_blank");
        }
        if (OPEN_TICKET_HTML) {
          window.open(`../api/ventas/ticket-html.php?id=${data.venta_id}`, "_blank");
        }

        // Limpiar
        $btnLimpiar?.click();
      } catch (err) {
        console.error(err);
        alert("❌ No se pudo registrar la venta.");
      }
    });
  }

  // ======================
  // Dashboard POS (ventas recientes)
  // ======================
  async function cargarDashboard() {
    try {
      const res = await fetch("../api/ventas/dashboard.php");
      const data = await res.json();

      if ($ventasRecientes && data?.ventasRecientes) {
        $ventasRecientes.innerHTML = "";
        data.ventasRecientes.forEach((v) => {
          const li = d.createElement("li");
          li.textContent = `#${v.id} - ${v.cliente || "Cliente"} - $${Number(
            v.total
          ).toLocaleString()}`;
          $ventasRecientes.appendChild(li);
        });
      }
    } catch (e) {
      // Silencioso
      console.warn("Dashboard POS no disponible aún.");
    }
  }

  // ======================
  // Inicial
  // ======================
  renderProductos();
  renderCarrito();
  cargarDashboard();
  setInterval(cargarDashboard, 15000); // refrescar cada 15s
})();
