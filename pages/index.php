<?php
// Aunque no se usa en el HTML estático, dejamos la conexión por si se quiere hacer dinámico en el futuro.
// include 'db.php'; 
// try {
//   $stmt = $pdo->query("SELECT nombre, descripcion, precioBase, duracionDias FROM TipoMembresia");
//   $planes = $stmt->fetchAll();
// } catch(PDOException $e) {
//   $planes = []; // Error al cargar planes
// }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gimnasio Cuerpo Sano</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    html, body {
      height: 100%;
      font-family: 'Inter', system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      color: #222;
      background: linear-gradient(135deg, #b2f5ea 0%, #e6fffa 50%, #fefcbf 100%);
      background-attachment: fixed;
      background-size: cover;
    }

    a { color: inherit; text-decoration: none; }

    .container { max-width: 1100px; margin: 0 auto; padding: 20px; }

    header {
      background: linear-gradient(90deg, #0f766e, #0891b2);
      color: white;
      padding: 18px 0;
      box-shadow: 0 3px 8px rgba(0,0,0,0.15);
    }

    .brand { display: flex; flex-direction: column; gap: 4px; }

    .brand h1 {
      font-size: 36px;
      font-weight: 800;
      margin: 0;
    }

    .brand span {
      font-size: 15px;
      opacity: 0.9;
    }

    nav {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    nav a {
      background: rgba(255,255,255,0.1);
      padding: 8px 14px;
      border-radius: 8px;
      color: #fff;
      transition: all 0.2s ease;
    }

    nav a:hover {
      background: rgba(255,255,255,0.25);
    }

    
    .hero {
      display: grid;
      grid-template-columns: 1fr;
      gap: 24px;
      align-items: center;
      padding: 50px 0;
      text-align: center;
    }

    .hero h1 {
      font-size: 32px;
      color: #0f766e;
      line-height: 1.2;
    }

    .hero p {
      color: rgba(0,0,0,0.7);
      font-size: 1.05em;
      margin-top: 12px;
    }

    
    .features {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 20px;
      padding: 40px 0;
    }

    .feature {
      background-color: #ffffff;
      border-radius: 16px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.08);
      padding: 24px;
      width: 300px;
      transition: transform 0.2s ease;
    }

    .feature:hover {
      transform: translateY(-4px);
    }

    .feature h3 {
      color: #0f766e;
      font-size: 1.2rem;
      margin-bottom: 10px;
    }

    .feature p {
      color: #333;
      line-height: 1.4;
    }

    
    .planes {
      margin-top: 40px;
    }

    .section-title {
      font-size: 1.5rem;
      color: #0f766e;
      text-align: center;
      margin-bottom: 20px;
    }

    .plan-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      justify-content: center;
    }

    .plan {
      background: #fff;
      border-radius: 16px;
      padding: 20px;
      width: 250px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      text-align: center;
      transition: transform 0.2s ease;
    }

    .plan:hover {
      transform: scale(1.03);
    }

    .plan h3 {
      color: #0f766e;
      font-size: 1.1rem;
      margin-bottom: 8px;
    }

    .plan p {
      color: #333;
      font-size: 0.95rem;
      margin-bottom: 10px;
    }

    footer {
      padding: 20px 0;
      border-top: 1px solid rgba(0,0,0,0.1);
      text-align: center;
      color: #444;
      background: rgba(255,255,255,0.6);
      margin-top: 40px;
    }

    @media (min-width: 768px) {
      .hero { grid-template-columns: 1fr; }
      .hero h1 { font-size: 38px; }
    }
  </style>
</head>

<body>
  <header>
    <div class="container" style="display:flex;align-items:center;justify-content:space-between;">
      <div class="brand">
        <h1>Gimnasio Cuerpo Sano</h1>
        <span>Salud • Bienestar • Comunidad</span>
      </div>
      <nav>
        <a href="login.html">Iniciar Sesión</a>
      </nav>
    </div>
  </header>

  <main class="container">
    <section class="hero">
      <div>
        <h1>Tu mejor versión, paso a paso 🏋️‍♀️</h1>
        <p>Entrenamiento, nutrición y comunidad pensados para que disfrutes el proceso.<br>
        <b>Horario:</b> Lunes a Viernes 6:00–23:00 • Sábado 8:00–20:00</p>
      </div>
    </section>

    <section class="features">
      <div class="feature">
        <h3>💪 Sala de musculación</h3>
        <p>Equipamiento moderno con máquinas, pesas libres y jaulas para todos los objetivos.</p>
      </div>
      <div class="feature">
        <h3>🥗 Plan nutricional</h3>
        <p>Asesoramiento nutricional personalizado para lograr resultados saludables.</p>
      </div>
      <div class="feature">
        <h3>🏋️ Entrenadores</h3>
        <p>Profesionales certificados que te acompañan en cada paso hacia tus metas.</p>
      </div>
    </section>

    <section class="planes">
      <h2 class="section-title">Planes y Formas de Pago</h2>
      <div class="plan-grid">
        <div class="plan">
          <h3>Plan Mensual</h3>
          <p>Acceso total al gimnasio y clases grupales.</p>
          <div><b>Precio:</b> A definir / mes</div>
        </div>
        <div class="plan">
          <h3>Plan Trimestral</h3>
          <p>Entrená con continuidad y ahorrá un 10%.</p>
          <div><b>Precio:</b> A definir / 3 meses</div>
        </div>
        <div class="plan">
          <h3>Plan Anual</h3>
          <p>Comprometete con tu bienestar y obtené grandes beneficios.</p>
          <div><b>Precio:</b> A definir / 12 meses</div>
        </div>
        <div class="plan">
          <h3>Entrenamiento Personalizado</h3>
          <p>Sesiones 1:1 a medida con entrenador personal.</p>
          <div><b>Precio:</b> Consultar</div>
        </div>
      </div>

      <br>
      <p style="text-align:center; font-size:0.95rem;">
        🎓 Descuentos: 20% para estudiantes y 25% para mayores de 60 años.
      </p>
      <br>
      <p style="text-align:center; font-size:1rem;">Se acepta:</p>
      <ul style="list-style:none; text-align:center; margin-top:10px;">
        <li>Efectivo 💵</li>
        <li>Tarjetas de débito/crédito 💳</li>
        <li>Transferencias bancarias 💻</li>
        <li>Billeteras virtuales (Mercado Pago, Ualá) 📱</li>
      </ul>
    </section>
  </main>

  <footer>
    © 2025 Gimnasio Cuerpo Sano — Salud • Bienestar • Comunidad
  </footer>
</body>
</html>