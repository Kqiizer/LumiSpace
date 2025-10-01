document.addEventListener("DOMContentLoaded", () => {
  const menuBtn = document.getElementById("menu-btn");
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  const themeToggle = document.getElementById("theme-toggle");

  // === Abrir / Cerrar Sidebar lateral + animación hamburguesa ===
  if (menuBtn && sidebar && overlay) {
    menuBtn.addEventListener("click", () => {
      const isActive = sidebar.classList.toggle("active");
      overlay.classList.toggle("active", isActive);
      menuBtn.classList.toggle("open", isActive);

      // 🚨 Bloquear / permitir scroll en body
      document.body.classList.toggle("no-scroll", isActive);
    });

    // Cerrar sidebar al hacer clic en overlay
    overlay.addEventListener("click", () => {
      sidebar.classList.remove("active");
      overlay.classList.remove("active");
      menuBtn.classList.remove("open");
      document.body.classList.remove("no-scroll");
    });
  }

  // === Dark mode toggle con persistencia ===
  if (themeToggle) {
    // Cargar estado guardado
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
