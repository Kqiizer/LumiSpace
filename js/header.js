document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("menu-btn");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  const themeToggle = document.getElementById("theme-toggle");

  // ====== BASE del sitio (definido en <body data-base="...">) ======
  const BASE = document.body.getAttribute("data-base") || "/";

  // ====== Badges (fallbacks si cambian IDs) ======
  const favBadge =
    document.getElementById("fav-badge") ||
    document.querySelector('a[href$="favoritos.php"] .badge');
  const cartBadge =
    document.getElementById("cart-badge") ||
    document.querySelector('a[href$="carrito.php"] .badge');

  // === Funciones para abrir/cerrar sidebar ===
  const openMenu = () => {
    sidebar?.classList.add("active");
    overlay?.classList.add("active");
    menuBtn?.classList.add("open");
    document.body.style.overflow = "hidden";
  };
  const closeMenu = () => {
    sidebar?.classList.remove("active");
    overlay?.classList.remove("active");
    menuBtn?.classList.remove("open");
    document.body.style.overflow = "";
  };

  // === Evento del botón hamburguesa ===
  if (menuBtn && sidebar && overlay) {
    menuBtn.addEventListener("click", () => {
      const isActive = sidebar.classList.toggle("active");
      overlay.classList.toggle("active", isActive);
      menuBtn.classList.toggle("open", isActive);
      document.body.style.overflow = isActive ? "hidden" : "";
    });

    overlay.addEventListener("click", closeMenu);

    // Cerrar al presionar ESC
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && sidebar.classList.contains("active")) closeMenu();
    });

    // Cerrar al hacer clic en enlaces dentro del sidebar
    sidebar.addEventListener("click", (e) => {
      const a = e.target.closest("a");
      if (a) closeMenu();
    });
  }

  // === 🔹 Detección de cambio de tamaño (desktop vs móvil) ===
  const DESKTOP_WIDTH = 1024;
  window.addEventListener("resize", () => {
    if (window.innerWidth >= DESKTOP_WIDTH) {
      // Si el usuario amplía la ventana, cierra el menú móvil y limpia el overlay
      closeMenu();
    }
  });

  // === 🌙 Modo Oscuro con persistencia ===
  if (themeToggle) {
    const themeIcon = themeToggle.querySelector("[data-theme-icon]");
    const themeText = themeToggle.querySelector("[data-theme-text]");
    const iconDark = themeToggle.dataset.iconDark || "";
    const iconLight = themeToggle.dataset.iconLight || "";

    const syncThemeButton = (isDark) => {
      const label = isDark ? "Modo Claro" : "Modo Oscuro";
      if (themeText) themeText.textContent = label;
      if (themeIcon) {
        const nextIcon = isDark ? iconLight : iconDark;
        if (nextIcon) themeIcon.src = nextIcon;
        themeIcon.alt = label;
      }
    };

    const setTheme = (dark) => {
      document.body.classList.toggle("dark", dark);
      syncThemeButton(dark);
      localStorage.setItem("theme", dark ? "dark" : "light");
    };

    // Cargar tema guardado
    setTheme(localStorage.getItem("theme") === "dark");

    themeToggle.addEventListener("click", () => {
      const isDark = !document.body.classList.contains("dark");
      setTheme(isDark);
    });
  }

  // === Contadores (favoritos / carrito) ===
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
    } catch {
      /* Silencioso */
    }
  }

  // Cargar al iniciar y refrescar cada 15 segundos
  refreshCounters();
  setInterval(refreshCounters, 15000);

  // Escuchar eventos globales (actualización desde otras vistas)
  window.addEventListener("cart:updated", refreshCounters);
  window.addEventListener("wishlist:updated", refreshCounters);
});
