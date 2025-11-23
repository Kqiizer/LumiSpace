<!-- Footer -->
<link rel="stylesheet" href="../css/styles/footer.css">

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
            Ilumina tu hogar con estilo ✨. Diseños modernos y funcionales que transforman cualquier espacio.
          </p>
          <div class="footer-social">
            <a href="#" class="social-link"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
            <a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
            <a href="#" class="social-link"><i class="fab fa-youtube"></i></a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-section">
          <h4 class="footer-title">Enlaces rápidos</h4>
          <ul class="footer-links">
            <li><a href="../index.php">Inicio</a></li>
            <li><a href="../views/catalogo.php">Catálogo</a></li>
            <li><a href="../views/proyectos.php">Proyectos</a></li>
            <li><a href="../views/servicios.php">Servicios</a></li>
            <li><a href="../views/contacto.php">Contacto</a></li>
          </ul>
        </div>

        <!-- Categorías -->
        <div class="footer-section">
          <h4 class="footer-title">Categorías</h4>
          <ul class="footer-links">
            <li><a href="#">Interior</a></li>
            <li><a href="#">Exterior</a></li>
            <li><a href="#">Decorativo</a></li>
            <li><a href="#">Iluminación LED</a></li>
            <li><a href="#">Smart Home</a></li>
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

  <!-- Newsletter -->
  <div class="newsletter-section">
    <div class="container">
      <div class="newsletter-content">
        <div class="newsletter-text">
          <h3>Suscríbete a nuestro boletín</h3>
          <p>Recibe ofertas exclusivas y las últimas novedades en iluminación</p>
        </div>
        <form class="newsletter-form">
          <input type="email" placeholder="Tu correo electrónico" class="newsletter-input" required>
          <button type="submit" class="newsletter-btn">
            Suscribirse <i class="fas fa-paper-plane"></i>
          </button>
        </form>
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
            <i class="fab fa-cc-paypal"></i>
            <i class="fab fa-cc-amex"></i>
          </div>
        </div>
        <div class="footer-policies">
          <a href="#">Política de Privacidad</a>
          <span>|</span>
          <a href="#">Términos de Uso</a>
          <span>|</span>
          <a href="#">Cookies</a>
        </div>
      </div>
    </div>
  </div>
</footer>
