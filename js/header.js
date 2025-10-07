document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("menu-btn");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  const themeToggle = document.getElementById("theme-toggle");

  // ====== BASE para rutas del sitio (puesto en <body data-base="...">) ======
  const BASE = document.body.getAttribute("data-base") || "/";

  // ====== Badges (con fallbacks si cambian los IDs) ======
  const favBadge =
    document.getElementById("fav-badge") ||
    document.querySelector('a[href$="favoritos.php"] .badge');

  const cartBadge =
    document.getElementById("cart-badge") ||
    document.querySelector('a[href$="carrito.php"] .badge');

  // === Sidebar (hamburguesa + overlay) ===
  if (menuBtn && sidebar && overlay) {
    const openMenu = () => {
      sidebar.classList.add("active");
      overlay.classList.add("active");
      menuBtn.classList.add("open");
      document.body.style.overflow = "hidden";
    };
    const closeMenu = () => {
      sidebar.classList.remove("active");
      overlay.classList.remove("active");
      menuBtn.classList.remove("open");
      document.body.style.overflow = "";
    };

    menuBtn.addEventListener("click", () => {
      const isActive = sidebar.classList.toggle("active");
      overlay.classList.toggle("active", isActive);
      menuBtn.classList.toggle("open", isActive);
      document.body.style.overflow = isActive ? "hidden" : "";
    });

    overlay.addEventListener("click", closeMenu);

    // Cerrar menú al presionar ESC
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && sidebar.classList.contains("active")) closeMenu();
    });

    // Cerrar menú al hacer clic en enlaces del sidebar
    sidebar.addEventListener("click", (e) => {
      const a = e.target.closest("a");
      if (a) closeMenu();
    });
  }

  // === Modo Oscuro con persistencia ===
  if (themeToggle) {
    const setTheme = (dark) => {
      document.body.classList.toggle("dark", dark);
      themeToggle.innerHTML = dark ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
      localStorage.setItem("theme", dark ? "dark" : "light");
    };

    // Cargar estado guardado
    setTheme(localStorage.getItem("theme") === "dark");

    themeToggle.addEventListener("click", () => {
      const isDark = !document.body.classList.contains("dark");
      setTheme(isDark);
    });
  }

  // ====== Contadores (favoritos / carrito) ======
  function updateBadge($el, count) {
    if (!$el) return;
    const n = Number(count) || 0;
    $el.textContent = n;
    $el.style.display = n > 0 ? "" : "none";
  }

  async function refreshCounters() {
    try {
      const res = await fetch(`${BASE}api/header/counters.php`, { cache: "no-store" });
      if (!res.ok) return;
      const data = await res.json();
      if (!data || !data.ok) return;
      updateBadge(favBadge, data.favoritos);
      updateBadge(cartBadge, data.carrito);
    } catch (_) {
      // Silencioso
    }
  }

  // Cargar al iniciar y refrescar cada 15s
  refreshCounters();
  setInterval(refreshCounters, 15000);

  // Escuchar eventos globales que otras vistas pueden disparar
  window.addEventListener("cart:updated", refreshCounters);
  window.addEventListener("wishlist:updated", refreshCounters);
});
