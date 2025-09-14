// Evita inicializar dos veces
if (!window.__LS_MAIN_INIT__) {
  window.__LS_MAIN_INIT__ = true;

  // === LOGO & MENÚ ACTIVO ===
  const logoLink = document.querySelector(".logo-link");
  const menuLinks = document.querySelectorAll(".menu a");

  if (logoLink && (window.location.pathname.endsWith("index.php") || window.location.pathname === "/")) {
    logoLink.classList.add("active");
  }

  menuLinks.forEach(link => {
    link.addEventListener("click", () => {
      document.querySelector(".menu a.active")?.classList.remove("active");
      logoLink?.classList.remove("active");
      link.classList.add("active");
    });
  });

  logoLink?.addEventListener("click", () => {
    document.querySelector(".menu a.active")?.classList.remove("active");
    logoLink.classList.add("active");
  });

  // === SIDEBAR ===
  const menuBtn = document.getElementById("menu-btn");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");

  function toggleSidebar(open) {
    sidebar?.classList.toggle("active", open);
    overlay?.classList.toggle("active", open);
    menuBtn?.classList.toggle("active", open);
  }

  if (menuBtn && sidebar && overlay) {
    menuBtn.addEventListener("click", () => toggleSidebar(!sidebar.classList.contains("active")));
    overlay.addEventListener("click", () => toggleSidebar(false));
  }

  // === TEMA OSCURO (persistente + sincronizado) ===
  const themeBtn = document.getElementById("theme-toggle");

  function applyTheme() {
    const saved = localStorage.getItem("theme");
    const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;

    const isDark = saved ? (saved === "dark") : prefersDark;

    // 🔥 Aplica SIEMPRE en <html> y <body> para evitar parpadeos
    document.documentElement.classList.toggle("dark-mode", isDark);
    document.body.classList.toggle("dark-mode", isDark);

    if (themeBtn) {
      themeBtn.textContent = isDark ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
    }
  }

  // Aplica tema al cargar
  applyTheme();

  // Alternar con el botón
  themeBtn?.addEventListener("click", () => {
    const isDark = !document.documentElement.classList.contains("dark-mode");

    document.documentElement.classList.toggle("dark-mode", isDark);
    document.body.classList.toggle("dark-mode", isDark);

    localStorage.setItem("theme", isDark ? "dark" : "light");

    if (themeBtn) {
      themeBtn.textContent = isDark ? "☀️ Modo Claro" : "🌙 Modo Oscuro";
    }
  });

  // Sincroniza entre pestañas
  window.addEventListener("storage", (e) => {
    if (e.key === "theme") applyTheme();
  });

  // === HEADER SCROLL EFFECT ===
  const header = document.querySelector(".navbar");
  if (header) {
    window.addEventListener("scroll", () => {
      header.classList.toggle("scrolled", window.scrollY > 50);
    });
  }
}
