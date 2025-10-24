<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . "/../config/functions.php";

$BASE = defined('BASE_URL') ? rtrim(BASE_URL, '/') . '/' : '/';
$conn = getDBConnection();

// Obtener estadísticas reales desde la base de datos
$stats = [
  'productos' => 0,
  'clientes' => 0,
  'pedidos' => 0,
  'experiencia' => 15
];

// Contar productos activos
$check_activo = $conn->query("SHOW COLUMNS FROM productos LIKE 'activo'");
$has_activo = $check_activo && $check_activo->num_rows > 0;

if ($has_activo) {
  $result = $conn->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
} else {
  $result = $conn->query("SELECT COUNT(*) as total FROM productos");
}
if ($result) {
  $row = $result->fetch_assoc();
  $stats['productos'] = (int)$row['total'];
}

// Contar clientes (usuarios)
$result = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol != 'admin'");
if ($result) {
  $row = $result->fetch_assoc();
  $stats['clientes'] = (int)$row['total'];
}

// Contar pedidos entregados
$check_pedidos = $conn->query("SHOW TABLES LIKE 'pedidos'");
if ($check_pedidos && $check_pedidos->num_rows > 0) {
  $result = $conn->query("SELECT COUNT(*) as total FROM pedidos WHERE estado = 'entregado'");
  if ($result) {
    $row = $result->fetch_assoc();
    $stats['pedidos'] = (int)$row['total'];
  }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #f5f5f5;
    }

    /* Sección de Estadísticas */
    .statistics {
      padding: 80px 0;
      background: linear-gradient(135deg, #a1683a 0%, #8f5e4b 100%);
      position: relative;
      overflow: hidden;
    }

    .statistics::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -10%;
      width: 500px;
      height: 500px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 50%;
    }

    .statistics::after {
      content: '';
      position: absolute;
      bottom: -50%;
      left: -10%;
      width: 600px;
      height: 600px;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 50%;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 0 20px;
      position: relative;
      z-index: 1;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 40px;
    }

    .stat-item {
      text-align: center;
      color: white;
      position: relative;
      padding: 30px 20px;
      transition: transform 0.3s ease;
    }

    .stat-item:hover {
      transform: translateY(-10px);
    }

    .stat-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(10px);
      border-radius: 50%;
      transition: all 0.3s ease;
    }

    .stat-item:hover .stat-icon {
      background: rgba(255, 255, 255, 0.25);
      transform: scale(1.1);
    }

    .stat-icon i {
      font-size: 36px;
      color: white;
    }

    .stat-number {
      font-size: 56px;
      font-weight: 800;
      line-height: 1;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 4px;
    }

    .stat-plus {
      font-size: 36px;
      font-weight: 700;
      opacity: 0.9;
      display: inline-block;
      margin-left: 4px;
    }

    .stat-label {
      font-size: 18px;
      font-weight: 500;
      opacity: 0.95;
      margin-top: 12px;
    }

    /* Animación de contador */
    @keyframes countUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .stat-item.animate {
      animation: countUp 0.6s ease forwards;
    }

    /* Responsive */
    @media (max-width: 1200px) {
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 32px;
      }
    }

    @media (max-width: 768px) {
      .statistics {
        padding: 60px 0;
      }

      .stats-grid {
        grid-template-columns: 1fr;
        gap: 40px;
      }

      .stat-number {
        font-size: 48px;
      }

      .stat-plus {
        font-size: 32px;
      }

      .stat-label {
        font-size: 16px;
      }

      .stat-icon {
        width: 70px;
        height: 70px;
      }

      .stat-icon i {
        font-size: 32px;
      }
    }

    @media (max-width: 480px) {
      .stat-number {
        font-size: 42px;
      }

      .stat-label {
        font-size: 15px;
      }
    }
  </style>
</head>
<body>

<!-- Sección de Estadísticas -->
<section class="statistics">
  <div class="container">
    <div class="stats-grid">
      
      <div class="stat-item">
        <div class="stat-icon">
          <i class="fas fa-box"></i>
        </div>
        <div class="stat-number" data-target="<?= $stats['productos'] ?>">0</div>
        <div class="stat-label">Productos Disponibles</div>
      </div>

      <div class="stat-item">
        <div class="stat-icon">
          <i class="fas fa-users"></i>
        </div>
        <div class="stat-number" data-target="<?= $stats['clientes'] ?>">0</div>
        <div class="stat-label">Clientes Satisfechos</div>
      </div>

      <div class="stat-item">
        <div class="stat-icon">
          <i class="fas fa-award"></i>
        </div>
        <div class="stat-number" data-target="<?= $stats['experiencia'] ?>">0</div>
        <div class="stat-label">Años de Experiencia</div>
      </div>

      <div class="stat-item">
        <div class="stat-icon">
          <i class="fas fa-shipping-fast"></i>
        </div>
        <div class="stat-number" data-target="<?= $stats['pedidos'] ?>">0</div>
        <div class="stat-label">Pedidos Entregados</div>
      </div>

    </div>
  </div>
</section>

<script>
(()=>{
  // Animación de contador
  const animateCounter = (element, target) => {
    const duration = 2000; // 2 segundos
    const start = 0;
    const increment = target / (duration / 16); // 60fps
    let current = start;

    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        element.textContent = target.toLocaleString();
        clearInterval(timer);
      } else {
        element.textContent = Math.floor(current).toLocaleString();
      }
    }, 16);
  };

  // Intersection Observer para activar cuando sea visible
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const statItem = entry.target;
        const numberElement = statItem.querySelector('.stat-number');
        const target = parseInt(numberElement.dataset.target);

        // Animar el item
        statItem.classList.add('animate');

        // Animar el contador
        setTimeout(() => {
          animateCounter(numberElement, target);
        }, 200);

        // Dejar de observar este elemento
        observer.unobserve(statItem);
      }
    });
  }, {
    threshold: 0.3
  });

  // Observar todos los stat-items
  document.querySelectorAll('.stat-item').forEach(item => {
    observer.observe(item);
  });

  // Actualizar estadísticas en tiempo real (cada 30 segundos)
  const updateStats = async () => {
    try {
      const response = await fetch('<?= $BASE ?>api/stats/get-stats.php');
      const data = await response.json();
      
      if (data.ok) {
        document.querySelectorAll('.stat-number').forEach((element, index) => {
          const newTarget = Object.values(data.stats)[index];
          const currentValue = parseInt(element.textContent.replace(/,/g, ''));
          
          if (newTarget !== currentValue) {
            animateCounter(element, newTarget);
          }
        });
      }
    } catch (error) {
      console.error('Error al actualizar estadísticas:', error);
    }
  };

  // Actualizar cada 30 segundos (opcional)
  // setInterval(updateStats, 30000);

  console.log('✅ Estadísticas cargadas');
})();
</script>
</body>
</html>