<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../config/functions.php";

// Configuración base
$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Furniture Collection</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/css/about-styles.css">
    <link rel="stylesheet" href="/css/header.css">
    <link rel="stylesheet" href="/css/sidebar.css">

</head>

<body>
    <?php include "../includes/header.php"; ?>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="hero-content">
            <h1 class="hero-title">Sobre Lumispace</h1>
            <p class="hero-subtitle">Tienda en linea desde el 2025</p>
            <div class="hero-breadcrumb">
                <a href="index.php">Home</a> / Sobre nosotros
            </div>
        </div>
        <div class="hero-overlay"></div>
    </section>

    <!-- Our Story Section -->
    <section class="our-story">
        <div class="container">
            <div class="story-layout">
                <div class="story-image">
                    <img src="../images/sobrenosotros.jpg" alt="imagen de LumiSpace" width="800">
                </div>
                <div class="story-content">
                    <span class="section-label">Nuestra Historia</span>
                    <h2 class="section-title">Donde la calidad se encuentra con el diseño</h2>
                    <p class="story-text">En LUMISPACE creemos que la iluminación transforma los espacios. Somos una tienda mexicana dedicada a ofrecer soluciones modernas, funcionales y estéticamente cuidadas para interiores y exteriores.
                        Desde lámparas colgantes y apliques de pared hasta faroles y proyectores LED, cada pieza está pensada para combinar diseño, calidad y calidez.</p>
                    <p class="story-text">Nuestra misión es ayudarte a crear ambientes únicos, llenos de luz y estilo.
                        Nuestra visión es convertirnos en la marca líder de iluminación decorativa en Latinoamérica, ofreciendo productos innovadores, sustentables y accesibles.</p>

                    <div class="story-features">
                        <div class="feature-box">
                            <i class="fas fa-leaf"></i>
                            <h4>DISEÑO Y FUNCIONALIDAD</h4>
                        </div>
                        <div class="feature-box">
                            <i class="fas fa-hammer"></i>
                            <h4>COMPROMISO CON LA CALIDAD</h4>
                        </div>
                        <div class="feature-box">
                            <i class="fas fa-heart"></i>
                            <h4>ATENCIÓN PERSONALIZADA</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">NUESTROS VALORES</span>
                <h2 class="section-title">Lo que representamos</h2>
                <p class="section-description">Estos valores fundamentales guían cada decisión que tomamos y cada pieza que creamos.</p>
            </div>

            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-award"></i>
                    </div>
                    <h3>CALIDAD ANTE TODO</h3>
                    <p>En LUMISPACE, cada pieza pasa por rigurosos controles para asegurar materiales duraderos, acabados impecables y un rendimiento excepcional.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-recycle"></i>
                    </div>
                    <h3>SOSTENIBILIDAD</h3>
                    <p>Creemos en iluminar sin comprometer el planeta. Utilizamos procesos responsables y materiales ecoamigables que reducen nuestro impacto ambiental.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h3>DISEÑO Y ESTILO</h3>
                    <p>Fusionamos la funcionalidad con la estética moderna. Cada diseño refleja equilibrio, elegancia y armonía para realzar cualquier espacio.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>CONFIANZA Y TRANSPARENCIA</h3>
                    <p>Construimos relaciones basadas en honestidad, atención personalizada y servicio confiable. Tu satisfacción es nuestra prioridad.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>INNOVACIÓN CONSTANTE </h3>
                    <p>Evolucionamos con las nuevas tendencias tecnológicas y de diseño para ofrecerte iluminación eficiente, moderna y con propósito.</p>
                </div>

                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>COMUNIDAD</h3>
                    <p>Apoyamos a diseñadores y artesanos locales, impulsando el talento y la economía de nuestras comunidades. Juntos creamos luz con significado.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number" data-target="2500">0</span>
                        <span class="stat-plus">+</span>
                        <p class="stat-label">Productos Disponibles</p>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-smile"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number" data-target="15000">0</span>
                        <span class="stat-plus">+</span>
                        <p class="stat-label">Clientes Satisfechos</p>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number" data-target="45">0</span>
                        <span class="stat-plus">+</span>
                        <p class="stat-label">Estados atendidos</p>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <div class="stat-content">
                        <span class="stat-number" data-target="28">0</span>
                        <span class="stat-plus">+</span>
                        <p class="stat-label">Premios ganados</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Team Section -->
    <section class="team-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Nuestro equipo</span>
                <h2 class="section-title">Conocenos</h2>
                <p class="section-description">
                    Profesionales apasionados dedicados a darle vida a nuestra visión</p>
            </div>

            <div class="team-grid">
                <div class="team-card">
                    <div class="team-image">
                        <img src="<?= $BASE ?>images/imagenes-integrantes/ximena-crv.jpeg" 
                             alt="Ximena Cuevas">
                    </div>
                    <div class="team-info">
                        <h3>Ximena Cuevas</h3>
                        <p class="team-role">programadora front-end</p>
                    </div>
                </div>

                <div class="team-card">
                    <div class="team-image">
                        <img src="<?= $BASE ?>images/imagenes-integrantes/josefina-hdz.jpeg" 
                             alt="Josefina Hernandez">
                    </div>
                    <div class="team-info">
                        <h3>Josefina Hernandez</h3>
                        <p class="team-role">diseñadora ux/ui</p>
                    </div>
                </div>

                <div class="team-card">
                    <div class="team-image">
                        <img src="<?= $BASE ?>images/imagenes-integrantes/fernando-arroyo.jpeg" 
                             alt="Fernando Arroyo">
                    </div>
                    <div class="team-info">
                        <h3>Fernando Arroyo</h3>
                        <p class="team-role">programador back-end</p>
                    </div>
                </div>

                <div class="team-card">
                    <div class="team-image">
                        <img src="<?= $BASE ?>images/imagenes-integrantes/santiago-ballesteros.jpeg" 
                             alt="Santiago Ballesteros">
                    </div>
                    <div class="team-info">
                        <h3>Santiago Ballesteros</h3>
                        <p class="team-role">programador back-end</p>
                    </div>
                </div>

                <div class="team-card">
                    <div class="team-image">
                        <img src="<?= $BASE ?>images/imagenes-integrantes/fatima-contreras.jpeg" 
                             alt="Fátima Contreras">
                    </div>
                    <div class="team-info">
                        <h3>Fátima Contreras</h3>
                        <p class="team-role">programadora front-end</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Achievements Section -->
    <section class="achievements-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Nuestros Logros</span>
                <h2 class="section-title">En LUMISPACE tambien compartimos nuestros logros y proyecciones</h2>
            </div>

            <div class="timeline">
                <div class="timeline-item left">
                    <div class="timeline-content">
                        <div class="timeline-year">2025</div>
                        <h3>El Comienzo</h3>
                        <p>Nace LUMISPACE con la misión de transformar los espacios a través de la luz. Iniciamos con un pequeño equipo apasionado por el diseño, la tecnología y la estética.</p>
                    </div>
                </div>

                <div class="timeline-item right">
                    <div class="timeline-content">
                        <div class="timeline-year">2025</div>
                        <h3>Primera Colección</h3>
                        <p>Lanzamos nuestra primera línea de luminarias decorativas, combinando elegancia y funcionalidad con materiales de alta calidad.</p>
                    </div>
                </div>

                <div class="timeline-item left">
                    <div class="timeline-content">
                        <div class="timeline-year">2025</div>
                        <h3>Presencia Digital</h3>
                        <p>Publicamos nuestra tienda en línea, creando una experiencia moderna y accesible para nuestros clientes.</p>
                    </div>
                </div>

                <div class="timeline-item left">
                    <div class="timeline-content">
                        <div class="timeline-year">2026</div>
                        <h3>Expansión en linea</h3>
                        <p>Planeamos ampliar nuestro catálogo y llegar a nuevos mercados nacionales a través de nuestra plataforma web.</p>
                    </div>
                </div>

                <div class="timeline-item right">
                    <div class="timeline-content">
                        <div class="timeline-year">2027</div>
                        <h3>Compromiso sustentable</h3>
                        <p>Iniciamos nuestro programa “Luz Responsable”, enfocado en materiales ecológicos y eficiencia energética.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section class="process-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">NUESTRO </span>
                <h2 class="section-title">Cómo Creamos Calidad</h2>
                <p class="section-description">Desde el diseño hasta la entrega, cada paso está cuidadosamente supervisado.</p>
            </div>

            <div class="process-grid">
                <div class="process-step">
                    <div class="step-number">01</div>
                    <div class="step-icon">
                        <i class="fas fa-pencil-ruler"></i>
                    </div>
                    <h3>Diseño</h3>
                    <p>Nuestros diseñadores crean conceptos innovadores que combinan estética, funcionalidad y tecnología de iluminación moderna.</p>
                </div>

                <div class="process-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>

                <div class="process-step">
                    <div class="step-number">02</div>
                    <div class="step-icon">
                        <i class="fas fa-tree"></i>
                    </div>
                    <h3>Selección de Materiales</h3>
                    <p>Elegimos materiales duraderos y sostenibles, garantizando calidad y respeto por el medio ambiente en cada luminaria.</p>
                </div>

                <div class="process-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>

                <div class="process-step">
                    <div class="step-number">03</div>
                    <div class="step-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3>Fabricación</h3>
                    <p>Cada pieza es elaborada con precisión, cuidando cada detalle para ofrecer productos que iluminan con estilo y confianza.</p>
                </div>

                <div class="process-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>

                <div class="process-step">
                    <div class="step-number">04</div>
                    <div class="step-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Control de Calidad</h3>
                    <p>Revisamos cada luminaria con pruebas técnicas y visuales para asegurar que cumpla con nuestros estándares de excelencia.
                    </p>
                </div>

                <div class="process-arrow">
                    <i class="fas fa-arrow-right"></i>
                </div>

                <div class="process-step">
                    <div class="step-number">05</div>
                    <div class="step-icon">
                        <i class="fas fa-shipping-fast"></i>
                    </div>
                    <h3>Entrega</h3>
                    <p>Empaquetamos y enviamos con seguridad para que cada producto llegue en perfectas condiciones, listo para transformar tus espacios.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Location Section -->
    <section class="location-section">
        <div class="container">
            <div class="location-layout">
                <div class="location-content">
                    <span class="section-label">VISITANOS</span>
                    <h2 class="section-title">Encuentranos aqui </h2>
                    <p class="location-description">Visita nuestra sala de exposición para descubrir nuestra colección de ilumariarias en persona. Nuestro equipo de expertos está listo para ayudarte a encontrar las piezas perfectas para tu espacio.</p>

                    <div class="location-details">
                        <div class="location-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>Dirección</h4>
                                <p>Avenida Universidad 333<br>Las Víboras, 28040 Colima</p>
                            </div>
                        </div>

                        <div class="location-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h4>Telefono</h4>
                                <p>3141495596<br>+123-456-790</p>
                            </div>
                        </div>

                        <div class="location-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Email</h4>
                                <p>info@Lumispace.com<br>support@LumiSpace.com</p>
                            </div>
                        </div>

                        <div class="location-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Horas</h4>
                                <p>Lun - Vie: 9:00 - 18:00<br>Sab: 10:00 - 16:00</p>
                            </div>
                        </div>
                    </div>

                    <button class="get-directions-btn">
                        <i class="fas fa-directions"></i>
                        Ver Direcciones
                    </button>
                </div>
                <div class="location-map">
                    <div class="map-header" style="text-align:center; margin-bottom: 10px;">
                        <i class="fas fa-map-marked-alt" style="font-size: 24px; color: #8b7355;"></i>
                    </div>

                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3766.766155750694!2d-103.70488851363028!3d19.249020535558085!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x84255a99f594dcb1%3A0x9102c91772abaa17!2sFacultad%20de%20Contabilidad%20y%20Administraci%C3%B3n%20de%20Colima%20(FCAC)!5e0!3m2!1ses!2smx!4v1759903577414!5m2!1ses!2smx"
                        width="100%"
                        height="400"
                        style="border:0; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.2);"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
            </div> <!-- 🔹 cierre de location-layout -->
        </div> <!-- 🔹 cierre de container -->
    </section> <!-- 🔹 cierre de location-section -->


    <!-- Testimonials Section -->
    <section class="testimonials-section">
        <div class="container">
            <div class="section-header">
                <span class="section-label">Testimonios</span>
                <h2 class="section-title">Opiniones de nuestros clientes</h2>
            </div>

            <div class="testimonials-slider">
                <div class="testimonial-card active">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"Muy buena calidad en lamparas!!, deberian incluir un asesor para saber que tipo de ilumación ocupa mi espacio."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">GG</div>
                        <div class="author-info">
                            <h4>Gonzalo</h4>
                            <p>Colima, México</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"Exceptional craftsmanship and customer service. We furnished our entire office with their collection and couldn't be happier with the results."</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">RT</div>
                        <div class="author-info">
                            <h4>Robert Thompson</h4>
                            <p>Comala, México</p>
                        </div>
                    </div>
                </div>

                <div class="testimonial-card">
                    <div class="testimonial-rating">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="testimonial-text">"The attention to detail is remarkable. Every piece we purchased has exceeded our expectations in both quality and design. Highly recommend!"</p>
                    <div class="testimonial-author">
                        <div class="author-avatar">MG</div>
                        <div class="author-info">
                            <h4>Maria Garcia</h4>
                            <p>Comala, México</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="testimonial-navigation">
                <button class="nav-btn prev"><i class="fas fa-chevron-left"></i></button>
                <div class="nav-dots">
                    <span class="dot active"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </div>
                <button class="nav-btn next"><i class="fas fa-chevron-right"></i></button>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Listo para iluminar tu espacio?</h2>
                <p>Explora nuestras colecciones y encuentra la iluminación perfecta</p>
                <div class="cta-buttons">
                    <a href="shop.html" class="cta-btn primary">
                        <i class="fas fa-shopping-bag"></i>
                        Comprar ahora
                    </a>
                    <a href="#" class="cta-btn secondary">
                        <i class="fas fa-phone"></i>
                        Contactanos
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . "/../includes/footer.php"; ?>

    <script src="/js/about-script.js"></script>
</body>

</html>