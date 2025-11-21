<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/functions.php';

/* ============================================================
   🔹 BASE dinámica: detecta el nivel de carpeta automáticamente
   ============================================================ */
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$depth = substr_count($scriptDir, '/');
$BASE = ($depth > 1) ? str_repeat('../', $depth - 1) : './';

/* Página actual */
$currentPage = basename($_SERVER['PHP_SELF']);

/* 🔹 Contadores */
$carritoCount = isset($_SESSION['carrito']) ? count($_SESSION['carrito']) : 0;

$favoritosCount = 0;
if (!empty($_SESSION['usuario_id'])) {
    $conn = getDBConnection();
    if ($stmt = $conn->prepare("SELECT COUNT(*) as c FROM favoritos WHERE usuario_id=?")) {
        $stmt->bind_param("i", $_SESSION['usuario_id']);
        $stmt->execute();
        $stmt->bind_result($c);
        if ($stmt->fetch()) $favoritosCount = (int)$c;
        $stmt->close();
    }
} else {
    $favoritosCount = isset($_SESSION['favoritos']) ? count($_SESSION['favoritos']) : 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LumiSpace</title>

  <!-- Tipografía global -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

  <!-- ✅ Estilos globales -->
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/reset.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/header.css">
  <link rel="stylesheet" href="<?= $BASE ?>css/styles/sidebar.css">
</head>

<body data-base="<?= htmlspecialchars($BASE) ?>">

<!-- 🔹 Top Bar -->
<div class="top-bar">
  <div class="container">
    <!-- Mensaje rotativo -->
    <span id="dynamic-message">
      <i class="fas fa-phone-alt"></i> +52 313 118 1746 | 
      <i class="fas fa-envelope"></i> lumispace0@gmail.com
    </span>
    
    <div class="social-icons">
      <!-- Facebook -->
      <a href="https://facebook.com/tu_pagina" target="_blank" data-social="facebook">
        <i class="fab fa-facebook-f"></i>
        <span class="tooltip">Facebook</span>
      </a>

      <!-- Twitter/X -->
      <a href="https://twitter.com/@LumiSpace_" target="_blank" data-social="twitter">
        <i class="fab fa-twitter"></i>
        <span class="tooltip">Twitter</span>
      </a>

      <!-- Instagram -->
      <a href="https://instagram.com/lumi_space0" target="_blank" data-social="instagram">
        <i class="fab fa-instagram"></i>
        <span class="tooltip">Instagram</span>
      </a>


      <!-- WhatsApp -->
      <a href="https://wa.me/3131181746" target="_blank" data-social="whatsapp">
        <i class="fab fa-whatsapp"></i>
        <span class="tooltip">WhatsApp</span>
      </a>
    </div>
  </div>
</div>

<script>
// Mensajes rotativos dinámicos
const messages = [
  '<i class="fas fa-phone-alt"></i> +52 123 456 7890 | <i class="fas fa-envelope"></i> lumi_space0@gmail.com',
  '<i class="fas fa-clock"></i> Lun - Vie: 9:00 AM - 6:00 PM',
  '<i class="fas fa-shipping-fast"></i> ¡Envíos gratis en compras mayores a $500!',
  '<i class="fas fa-star"></i> ¡Síguenos en redes sociales para promociones exclusivas!',
  '<i class="fas fa-headset"></i> Soporte 24/7 disponible'
];

let currentMessageIndex = 0;
const messageElement = document.getElementById('dynamic-message');

// Cambiar mensaje cada 4 segundos
function rotateMessages() {
  messageElement.style.opacity = '0';
  
  setTimeout(() => {
    currentMessageIndex = (currentMessageIndex + 1) % messages.length;
    messageElement.innerHTML = messages[currentMessageIndex];
    messageElement.style.opacity = '1';
  }, 500);
}

setInterval(rotateMessages, 4000);

// Hacer clic en teléfono/email cuando se muestra ese mensaje
messageElement.addEventListener('click', function() {
  if (currentMessageIndex === 0) {
    // Si está mostrando contacto, copiar email al portapapeles
    navigator.clipboard.writeText('lumispace0@gmail.com').then(() => {
      const originalMessage = messageElement.innerHTML;
      messageElement.innerHTML = '<i class="fas fa-check"></i> ¡Email copiado al portapapeles!';
      setTimeout(() => {
        messageElement.innerHTML = originalMessage;
      }, 2000);
    });
  }
});

// Contador de clics en redes sociales (analytics básico)
const socialLinks = document.querySelectorAll('[data-social]');
socialLinks.forEach(link => {
  link.addEventListener('click', function(e) {
    const social = this.getAttribute('data-social');
    console.log(`Clic en ${social} - ${new Date().toLocaleString()}`);
    
    // Opcional: Enviar a Google Analytics o tu sistema de tracking
    // gtag('event', 'social_click', { 'social_network': social });
  });
});

// Animación suave al hacer scroll
let lastScroll = 0;
const topBar = document.querySelector('.top-bar');

window.addEventListener('scroll', () => {
  const currentScroll = window.pageYOffset;
  
  if (currentScroll > lastScroll && currentScroll > 100) {
    // Scrolling down
    topBar.style.transform = 'translateY(-100%)';
  } else {
    // Scrolling up
    topBar.style.transform = 'translateY(0)';
  }
  
  lastScroll = currentScroll;
});

// Detectar si el usuario está en móvil
function isMobile() {
  return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
}

// Si es móvil, convertir el teléfono en enlace clickeable automáticamente
if (isMobile()) {
  messageElement.addEventListener('click', function() {
    if (currentMessageIndex === 0) {
      window.location.href = 'tel:+521234567890';
    }
  });
}

// Mostrar hora actual en tiempo real (opcional)
function showCurrentTime() {
  const now = new Date();
  const timeString = now.toLocaleTimeString('es-MX', { 
    hour: '2-digit', 
    minute: '2-digit',
    timeZone: 'America/Mexico_City'
  });
  
  // Reemplazar el mensaje de horario con la hora actual
  messages[1] = `<i class="fas fa-clock"></i> ${timeString} - Lun - Vie: 9:00 AM - 6:00 PM`;
}

// Actualizar hora cada minuto
showCurrentTime();
setInterval(showCurrentTime, 60000);

// Animación de entrada inicial
window.addEventListener('load', () => {
  topBar.style.transition = 'all 0.3s ease';
  messageElement.style.transition = 'opacity 0.5s ease';
});
</script>

<style>
/* Solo estilos mínimos para las nuevas funciones */
.top-bar {
  transition: transform 0.3s ease;
}

#dynamic-message {
  cursor: pointer;
  transition: opacity 0.5s ease;
  display: inline-block;
}

#dynamic-message:hover {
  opacity: 0.8;
}

.tooltip {
  visibility: hidden;
  position: absolute;
  background: rgba(0,0,0,0.8);
  color: white;
  padding: 5px 10px;
  border-radius: 4px;
  font-size: 12px;
  bottom: -30px;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  opacity: 0;
  transition: opacity 0.3s;
  pointer-events: none;
}

.social-icons a {
  position: relative;
}

.social-icons a:hover .tooltip {
  visibility: visible;
  opacity: 1;
}

.menu-icon {
  width: 24px;
  height: 24px;
  object-fit: contain;
  vertical-align: middle;
  margin-right: 10px;
}

</style>

<!-- 🔹 Header principal -->
<header class="header">
  <div class="container">

    <!-- Logo -->
    <a href="<?= $BASE ?>index.php" class="logo">
      <div class="logo-icon"><i class="fas fa-lightbulb"></i></div>
      <span>LumiSpace</span>
    </a>

    <!-- 🔹 Menú de escritorio -->
    <ul class="nav-menu">
      <li><a href="<?= $BASE ?>index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Inicio</a></li>
      <li><a href="<?= $BASE ?>views/categorias.php" class="<?= $currentPage === 'categorias.php' ? 'active' : '' ?>">Categorías</a></li>
      <li><a href="<?= $BASE ?>views/marcas.php" class="<?= $currentPage === 'marcas.php' ? 'active' : '' ?>">Marcas</a></li>
      <li><a href="<?= $BASE ?>views/catalogo.php" class="<?= $currentPage === 'catalogo.php' ? 'active' : '' ?>">Catálogo</a></li>
      <li><a href="<?= $BASE ?>views/blog.php" class="<?= $currentPage === 'blog.php' ? 'active' : '' ?>">Blog</a></li>
      <li><a href="<?= $BASE ?>views/contacto.php" class="<?= $currentPage === 'contacto.php' ? 'active' : '' ?>">Contacto</a></li>

    </ul>

    <!-- 🔹 Íconos (funcionales en escritorio y móvil) -->
    <div class="header-icons">
      <a href="<?= $BASE ?>views/search.php" class="icon-btn <?= $currentPage === 'search.php' ? 'active' : '' ?>">
        <i class="fas fa-search"></i>
      </a>

      <a href="<?= $BASE ?>index/favoritos.html" class="icon-btn <?= $currentPage === 'favoritos.html' ? 'active' : '' ?>">
        <i class="fas fa-heart"></i>
        <span class="badge" id="fav-badge" style="<?= $favoritosCount ? '' : 'display:none;' ?>"><?= $favoritosCount ?></span>
      </a>

      <a href="<?= $BASE ?>includes/carrito.php" class="icon-btn <?= $currentPage === 'carrito.php' ? 'active' : '' ?>">
        <i class="fas fa-shopping-cart"></i>
        <span class="badge" id="cart-badge" style="<?= $carritoCount ? '' : 'display:none;' ?>"><?= $carritoCount ?></span>
      </a>

      <!-- 🔹 Botón hamburguesa -->
      <button class="menu-toggle" id="menu-btn" aria-label="Abrir menú lateral" aria-expanded="false">
        <span class="top"></span>
        <span class="middle"></span>
        <span class="bottom"></span>
      </button>
    </div>
  </div>
</header>

<!-- 🔹 Overlay para fondo oscuro al abrir sidebar -->
<div class="overlay" id="overlay"></div>

<!-- 🔹 Sidebar (modo móvil y también accesible en escritorio pequeño) -->
<aside class="sidebar" id="sidebar">
  <button
    id="theme-toggle"
    class="btn"
    data-icon-light="<?= $BASE ?>images/iconos-menu/icons8-sol-48.png"
    data-icon-dark="<?= $BASE ?>images/iconos-menu/claro%20o%20obscuro.png"
  >
    <img
      src="<?= $BASE ?>images/iconos-menu/icons8-sol-48.png"
      alt="Tema claro u oscuro"
      class="menu-icon theme-icon"
    >
    <span
      class="t theme-text"
      data-i18n="sidebar.theme"
      data-i18n-es="Modo Oscuro"
    >Modo Oscuro</span>
  </button>

  <a href="<?= $BASE ?>index.php" class="btn <?= $currentPage === 'index.php' ? 'active' : '' ?>">
    <img src="<?= $BASE ?>images/iconos-menu/icons8-home-50.png" alt="Inicio" class="menu-icon">
    <span class="t" data-i18n="nav.home" data-i18n-es="Inicio">Inicio</span>
  </a>
  
  <a href="<?= $BASE ?>views/categorias.php" class="btn <?= $currentPage === 'categorias.php' ? 'active' : '' ?>">
    <img src="<?= $BASE ?>images/iconos-menu/icons8-men%C3%BA-32.png" alt="Categorías" class="menu-icon">
    <span class="t" data-i18n="nav.categories" data-i18n-es="Categorías">Categorías</span>
  </a>
  
  <a href="<?= $BASE ?>views/marcas.php" class="btn <?= $currentPage === 'marcas.php' ? 'active' : '' ?>">
    <img src="<?= $BASE ?>images/iconos-menu/icons8-lamp-30.png" alt="Marcas" class="menu-icon">
    <span class="t" data-i18n="nav.brands" data-i18n-es="Marcas">Marcas</span>
  </a>
  
  <a href="<?= $BASE ?>views/catalogo.php" class="btn <?= $currentPage === 'catalogo.php' ? 'active' : '' ?>">
    <img src="<?= $BASE ?>images/iconos-menu/icons8-price-tag-26.png" alt="Catálogo" class="menu-icon">
    <span class="t" data-i18n="nav.catalog" data-i18n-es="Catálogo">Catálogo</span>
  </a>
  
  <a href="<?= $BASE ?>views/blog.php" class="btn <?= $currentPage === 'blog.php' ? 'active' : '' ?>">
    <img src="<?= $BASE ?>images/iconos-menu/icons8-lectura-de-libros-48.png" alt="Blog" class="menu-icon">
    <span class="t" data-i18n="nav.blog" data-i18n-es="Blog">Blog</span>
  </a>
  
  <a href="<?= $BASE ?>views/contacto.php" class="btn <?= $currentPage === 'contacto.php' ? 'active' : '' ?>">
    <img src="<?= $BASE ?>images/iconos-menu/icons8-phone-48.png" alt="Contacto" class="menu-icon">
    <span class="t" data-i18n="nav.contact" data-i18n-es="Contacto">Contacto</span>
  </a>
  
  <a href="<?= $BASE ?>index/configuracion.html" class="btn <?= $currentPage === 'configuracion.html' ? 'active' : '' ?>">
    <img src="<?= $BASE ?>images/iconos-menu/icons8-ajustes-48.png" alt="Ajustes" class="menu-icon">
    <span class="t" data-i18n="nav.settings" data-i18n-es="Ajustes">Ajustes</span>
  </a>

  <!-- Botón traductor debajo de Ajustes -->
  <button
    id="lang-toggle"
    class="btn"
    data-flag-es="<?= $BASE ?>images/iconos-menu/bandera%20espa%C3%B1a.png"
    data-flag-en="<?= $BASE ?>images/iconos-menu/bandera%20inglaterra.png"
  >
    <img
      id="lang-flag"
      src="<?= $BASE ?>images/iconos-menu/bandera%20inglaterra.png"
      alt="Bandera de Inglaterra"
      class="menu-icon"
    >
    <span
      id="lang-label"
      class="t lang-text"
      data-i18n="sidebar.lang"
      data-i18n-es="English"
    >English</span>
  </button>

  <hr>

  <?php if (!empty($_SESSION['usuario_id'])): ?>
    <p style="margin:10px 0; font-weight:bold;">👋 Hola, <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario') ?></p>
    <a href="<?= $BASE ?>logout.php" class="btn">🚪 Cerrar Sesión</a>
  <?php else: ?>
    <a href="<?= $BASE ?>views/login.php" class="btn">
      <img src="<?= $BASE ?>images/iconos-menu/icons8-entrar-32.png" alt="Iniciar Sesión" class="menu-icon">
      <span class="t" data-i18n="nav.login" data-i18n-es="Iniciar Sesión">Iniciar Sesión</span>
    </a>
    <a href="<?= $BASE ?>views/register.php" class="btn">
      <img src="<?= $BASE ?>images/iconos-menu/icons8-registro-50.png" alt="Registrarse" class="menu-icon">
      <span class="t" data-i18n="nav.register" data-i18n-es="Registrarse">Registrarse</span>
    </a>
  <?php endif; ?>
</aside>

<!-- ✅ Script (controla menú, overlay y animaciones) -->
<script src="<?= $BASE ?>js/header.js" defer></script>
<script src="<?= $BASE ?>js/translator.js" defer></script>
</body>
</html>
