<?php
// Archivo de prueba para verificar la conexión
// Guardar como: htdocs/test_conexion.php
// Acceder desde: http://localhost/test_conexion.php

session_start();

echo "<h1>🔍 Diagnóstico del Sistema</h1>";
echo "<hr>";

// 1. Verificar estructura de carpetas
echo "<h2>1. Estructura de Archivos</h2>";
echo "<ul>";
echo "<li>Archivo actual: " . __FILE__ . "</li>";
echo "<li>Directorio actual: " . __DIR__ . "</li>";

$db_path = __DIR__ . '/db.php';
echo "<li>Ruta db.php: " . $db_path . " - " . (file_exists($db_path) ? '✅ Existe' : '❌ No existe') . "</li>";

$usuarios_path = __DIR__ . '/api/usuarios.php';
echo "<li>Ruta usuarios.php: " . $usuarios_path . " - " . (file_exists($usuarios_path) ? '✅ Existe' : '❌ No existe') . "</li>";

$html_path = __DIR__ . '/pages/gestionUsuarios.html';
echo "<li>Ruta HTML: " . $html_path . " - " . (file_exists($html_path) ? '✅ Existe' : '❌ No existe') . "</li>";
echo "</ul>";

// 2. Verificar conexión a base de datos
echo "<h2>2. Conexión a Base de Datos</h2>";
try {
    require_once $db_path;
    echo "<p>✅ Conexión a base de datos exitosa</p>";
    
    // Probar consulta
    $stmt = $pdo->query("SELECT DATABASE() as db_name");
    $result = $stmt->fetch();
    echo "<p>📊 Base de datos activa: <strong>" . $result['db_name'] . "</strong></p>";
    
    // Verificar tablas
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>📋 Tablas encontradas (" . count($tables) . "):</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Verificar empleados
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Empleado");
    $count = $stmt->fetch();
    echo "<p>👥 Total de empleados en BD: <strong>" . $count['total'] . "</strong></p>";
    
    if ($count['total'] > 0) {
        $stmt = $pdo->query("SELECT e.usuario, e.cargo, p.nombre, p.apellido FROM Empleado e INNER JOIN Persona p ON e.dni = p.dni LIMIT 5");
        $empleados = $stmt->fetchAll();
        echo "<p>📝 Empleados registrados:</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Usuario</th><th>Nombre</th><th>Cargo</th></tr>";
        foreach ($empleados as $emp) {
            echo "<tr><td>{$emp['usuario']}</td><td>{$emp['nombre']} {$emp['apellido']}</td><td>{$emp['cargo']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>⚠️ <strong>No hay empleados registrados. Ejecuta el script de inserción de datos.</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error de conexión: " . $e->getMessage() . "</p>";
}

// 3. Verificar sesión
echo "<h2>3. Estado de Sesión</h2>";
if (isset($_SESSION['usuario']) && isset($_SESSION['idEmpleado'])) {
    echo "<p>✅ Sesión activa</p>";
    echo "<ul>";
    echo "<li>Usuario: <strong>" . htmlspecialchars($_SESSION['usuario']) . "</strong></li>";
    echo "<li>ID Empleado: <strong>" . $_SESSION['idEmpleado'] . "</strong></li>";
    echo "</ul>";
    echo "<p><a href='api/logout.php'>Cerrar Sesión</a></p>";
} else {
    echo "<p>⚠️ No hay sesión activa</p>";
    echo "<p><a href='api/login.html'>Ir al Login</a></p>";
}

// 4. Verificar PHP
echo "<h2>4. Información de PHP</h2>";
echo "<ul>";
echo "<li>Versión PHP: <strong>" . phpversion() . "</strong></li>";
echo "<li>PDO disponible: " . (extension_loaded('pdo') ? '✅ Sí' : '❌ No') . "</li>";
echo "<li>PDO MySQL disponible: " . (extension_loaded('pdo_mysql') ? '✅ Sí' : '❌ No') . "</li>";
echo "</ul>";

echo "<hr>";
echo "<h2>🚀 Siguiente paso:</h2>";
if (!isset($_SESSION['usuario'])) {
    echo "<p>1. <a href='api/login.html'><strong>Iniciar Sesión</strong></a></p>";
} else {
    echo "<p>1. <a href='pages/gestionUsuarios.html'><strong>Ir a Gestión de Usuarios</strong></a></p>";
}

echo "<style>
    body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
    h1 { color: #667eea; }
    h2 { color: #764ba2; margin-top: 30px; }
    ul { background: white; padding: 20px; border-radius: 5px; }
    li { margin: 10px 0; }
    p { background: white; padding: 15px; border-radius: 5px; }
    table { background: white; margin: 10px 0; }
    a { color: #667eea; text-decoration: none; font-weight: bold; }
    a:hover { text-decoration: underline; }
</style>";
?>