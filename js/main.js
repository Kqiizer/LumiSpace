// === LOGO & MENÚ ACTIVO ===
const logoLink = document.querySelector(".logo-link");
const menuLinks = document.querySelectorAll(".menu a");

// Detecta si estás en index
if (window.location.pathname.includes("index.php") || window.location.pathname === "/") {
  logoLink.classList.add("active");
}

// Links del menú
menuLinks.forEach(link => {
  link.addEventListener("click", () => {
    document.querySelector(".menu a.active")?.classList.remove("active");
    logoLink.classList.remove("active"); // al entrar a otra sección, se desmarca el logo
    link.classList.add("active");
  });
});

// Logo clicado
if (logoLink) {
  logoLink.addEventListener("click", () => {
    document.querySelector(".menu a.active")?.classList.remove("active");
    logoLink.classList.add("active");
  });
}

// === SIDEBAR ===
const menuBtn = document.getElementById("menu-btn");
const sidebar = document.getElementById("sidebar");
const overlay = document.getElementById("overlay");

if (menuBtn && sidebar && overlay) {
  menuBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    overlay.classList.toggle("active");
    menuBtn.classList.toggle("active");
  });

  overlay.addEventListener("click", () => {
    sidebar.classList.remove("active");
    overlay.classList.remove("active");
    menuBtn.classList.remove("active");
  });
}

// === TEMA OSCURO (con persistencia) ===
const themeBtn = document.getElementById("theme-toggle");
if (themeBtn) {
  const savedTheme = localStorage.getItem("theme");
  if (savedTheme === "dark") {
    document.body.classList.add("dark-mode");
  }
  themeBtn.textContent = document.body.classList.contains("dark-mode")
    ? "Modo Claro"
    : "Modo Oscuro";

  themeBtn.addEventListener("click", () => {
    document.body.classList.toggle("dark-mode");
    if (document.body.classList.contains("dark-mode")) {
      themeBtn.textContent = "Modo Claro";
      localStorage.setItem("theme", "dark");
    } else {
      themeBtn.textContent = "Modo Oscuro";
      localStorage.setItem("theme", "light");
    }
  });
}
// Cambia el header al hacer scroll
window.addEventListener("scroll", () => {
  const header = document.querySelector(".navbar");
  if (window.scrollY > 50) {
    header.classList.add("scrolled");
  } else {
    header.classList.remove("scrolled");
  }
});
