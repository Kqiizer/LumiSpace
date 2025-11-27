<!-- Footer -->
<?php
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';

// Obtener categorías para los enlaces del footer
$categorias_footer = [];
if (function_exists('getCategorias')) {
    try {
        $categorias_footer = getCategorias();
        // Limitar a las primeras 3 categorías para el footer
        $categorias_footer = array_slice($categorias_footer, 0, 3);
    } catch (Exception $e) {
        // Si hay error, dejar vacío
        $categorias_footer = [];
    }
}
?>
<link rel="stylesheet" href="<?= $BASE ?>css/styles/footer.css">

<footer class="footer">
  <div class="footer-content">
    <div class="container">
      <div class="footer-grid">
        <!-- Company Info -->
        <div class="footer-section">
          <div class="footer-logo">
            <div class="logo-icon">
              <i class="fas fa-lightbulb"></i>
            </div>
            <span>LumiSpace</span>
          </div>
          <p class="footer-description">
            Ilumina tu hogar con estilo
            <img src="<?= $BASE ?>imagenes/estrellas.png" alt="Decoración de estrellas" class="footer-stars">
            Diseños modernos y funcionales que transforman cualquier espacio.
          </p>
          <div class="footer-social">
            <a href="https://www.facebook.com/profile.php?id=61582646287277&sk=about_contact_and_basic_info" class="social-link" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com/LumiSapce_" class="social-link" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
            <a href="https://www.instagram.com/lumi_space0/?utm_source=ig_web_button_share_sheet" class="social-link" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
            <a href="https://www.youtube.com/@LumiSpace0" class="social-link" target="_blank" rel="noopener"><i class="fab fa-youtube"></i></a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-section">
          <h4 class="footer-title">Enlaces rápidos</h4>
          <ul class="footer-links">
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="../views/catalogo.php">Catálogo</a></li>
            <li><a href="../views/contacto.php">Contacto</a></li>
          </ul>
        </div>

        <!-- Categorías -->
        <div class="footer-section">
          <h4 class="footer-title">Categorías</h4>
          <ul class="footer-links">
            <?php if (!empty($categorias_footer)): ?>
              <?php foreach ($categorias_footer as $cat): ?>
                <li><a href="<?= $BASE ?>views/catalogo.php?categoria=<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['nombre']) ?></a></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><a href="<?= $BASE ?>views/catalogo.php">Ver catálogo</a></li>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Atención al cliente -->
        <div class="footer-section">
          <h4 class="footer-title">Atención al cliente</h4>
          <ul class="footer-links">
            <li><a href="#">Preguntas frecuentes</a></li>
            <li><a href="#">Envíos</a></li>
            <li><a href="#">Devoluciones</a></li>
            <li><a href="#">Soporte</a></li>
          </ul>
        </div>

        <!-- Contact Info -->
        <div class="footer-section">
          <h4 class="footer-title">Contacto</h4>
          <div class="contact-info">
            <div class="contact-item">
              <i class="fas fa-map-marker-alt"></i>
              <span>Av. Luminarias 123<br>CDMX, México</span>
            </div>
            <div class="contact-item">
              <i class="fas fa-phone"></i>
              <span>+52 123 456 7890</span>
            </div>
            <div class="contact-item">
              <i class="fas fa-envelope"></i>
              <span>contacto@lumispace.com</span>
            </div>
            <div class="contact-item">
              <i class="fas fa-clock"></i>
              <span>Lun - Vie: 9:00 - 18:00</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom -->
  <div class="footer-bottom">
    <div class="container">
      <div class="footer-bottom-content">
        <div class="footer-copyright">
          <p>&copy; <?= date("Y") ?> LumiSpace. Todos los derechos reservados.</p>
        </div>
        <div class="footer-payment">
          <span>Aceptamos:</span>
          <div class="payment-icons">
            <i class="fab fa-cc-visa"></i>
            <i class="fab fa-cc-mastercard"></i>
          </div>
        </div>
        <div class="footer-policies">
          <a href="<?= $BASE ?>docs/politica-privacidad.html" target="_blank" rel="noopener">Política de Privacidad</a>
          <span>|</span>
          <a href="<?= $BASE ?>docs/terminos-condiciones.html" target="_blank" rel="noopener">Términos de Uso</a>
          <span>|</span>
          <a href="<?= $BASE ?>docs/politica-privacidad.html#cookies" target="_blank" rel="noopener">Cookies</a>
        </div>
      </div>
    </div>
  </div>
</footer>
