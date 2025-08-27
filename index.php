<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IluminaShop</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: #f5f5f5;
      color: #333;
    }

    header {
      background: #222;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    header h1 {
      margin: 0;
      font-size: 1.5rem;
    }

    nav a {
      color: white;
      margin-left: 20px;
      text-decoration: none;
      font-weight: bold;
    }

    nav a:hover {
      color: #ffd700;
    }

    .hero {
      background: url("https://images.unsplash.com/photo-1505691938895-1758d7feb511?auto=format&fit=crop&w=1200&q=80") center/cover no-repeat;
      height: 60vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
    }

    .hero h2 {
      font-size: 3rem;
      background: rgba(0,0,0,0.6);
      padding: 20px;
      border-radius: 10px;
    }

    .productos {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
      padding: 40px;
    }

    .producto {
      background: white;
      border-radius: 15px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      overflow: hidden;
      text-align: center;
      transition: transform 0.3s ease;
    }

    .producto:hover {
      transform: translateY(-5px);
    }

    .producto img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .producto h3 {
      margin: 15px 0 5px;
    }

    .producto p {
      font-size: 0.9rem;
      color: #666;
    }

    .precio {
      font-size: 1.2rem;
      color: #e67e22;
      margin: 10px 0;
    }

    .btn {
      display: inline-block;
      margin: 10px 0 20px;
      padding: 10px 20px;
      background: #222;
      color: white;
      border-radius: 8px;
      text-decoration: none;
      transition: background 0.3s ease;
    }

    .btn:hover {
      background: #e67e22;
    }

    footer {
      background: #222;
      color: white;
      text-align: center;
      padding: 20px;
      margin-top: 20px;
    }
  </style>
</head>
<body>

  <header>
    <h1>IluminaShop</h1>
    <nav>
      <a href="#">Inicio</a>
      <a href="#">Catálogo</a>
      <a href="#">Ofertas</a>
      <a href="#">Contacto</a>
    </nav>
  </header>

  <section class="hero">
    <h2>Ilumina tu espacio con estilo ✨</h2>
  </section>

  <section class="productos">
    <div class="producto">
      <img src="https://images.unsplash.com/photo-1519710164239-da123dc03ef4?auto=format&fit=crop&w=600&q=80" alt="Lámpara Moderna">
      <h3>Lámpara Moderna</h3>
      <p>Perfecta para sala o comedor</p>
      <div class="precio">$1,299 MXN</div>
      <a href="#" class="btn">Comprar</a>
    </div>

    <div class="producto">
      <img src="https://images.unsplash.com/photo-1604014237744-6e9e8d0d19cc?auto=format&fit=crop&w=600&q=80" alt="Foco LED RGB">
      <h3>Foco LED RGB</h3>
      <p>Cambia de color con control remoto</p>
      <div class="precio">$299 MXN</div>
      <a href="#" class="btn">Comprar</a>
    </div>

    <div class="producto">
      <img src="https://images.unsplash.com/photo-1598300042247-1a76f224fcd0?auto=format&fit=crop&w=600&q=80" alt="Lámpara Vintage">
      <h3>Lámpara Vintage</h3>
      <p>Estilo retro para tu habitación</p>
      <div class="precio">$899 MXN</div>
      <a href="#" class="btn">Comprar</a>
    </div>
  </section>

  <footer>
    <p>&copy; 2025 IluminaShop - Todos los derechos reservados</p>
  </footer>

</body>
</html>
