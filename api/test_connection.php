<?php
// test_connection.php - VERSIÓN COMPATIBLE CON NOMBRES MINÚSCULAS

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Conexión - CuerpoSano</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        h2 {
            color: #333;
            margin-top: 30px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            border: 1px solid #dee2e6;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico del Sistema - CuerpoSano</h1>
        
        <?php
        $errors = [];
        $warnings = [];
        $success = [];
        
        // ====================================
        // 1. VERIFICAR PHP
        // ====================================
        echo "<h2>1. Configuración de PHP</h2>";
        
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '7.0.0', '>=')) {
            echo "<div class='success'>✅ PHP versión: $phpVersion (OK)</div>";
        } else {
            echo "<div class='error'>❌ PHP versión: $phpVersion (Se requiere 7.0 o superior)</div>";
            $errors[] = "Versión de PHP obsoleta";
        }
        
        // Verificar extensiones
        $requiredExtensions = ['pdo', 'pdo_mysql', 'json'];
        echo "<h3>Extensiones PHP:</h3>";
        echo "<table>";
        echo "<tr><th>Extensión</th><th>Estado</th></tr>";
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $status = $loaded ? "<span class='status-ok'>✅ Cargada</span>" : "<span class='status-error'>❌ No encontrada</span>";
            echo "<tr><td>$ext</td><td>$status</td></tr>";
            if (!$loaded) {
                $errors[] = "Extensión $ext no disponible";
            }
        }
        echo "</table>";
        
        // ====================================
        // 2. VERIFICAR ARCHIVOS
        // ====================================
        echo "<h2>2. Verificación de Archivos</h2>";
        
        $requiredFiles = [
            'db.php' => 'Conexión a base de datos',
            'payment.html' => 'Interfaz de pagos',
            'payment.css' => 'Estilos',
            'payment.js' => 'JavaScript',
            'get_data.php' => 'API de consultas',
            'process_payment.php' => 'API de pagos'
        ];
        
        echo "<table>";
        echo "<tr><th>Archivo</th><th>Descripción</th><th>Estado</th></tr>";
        foreach ($requiredFiles as $file => $desc) {
            $exists = file_exists($file);
            $status = $exists ? "<span class='status-ok'>✅ Existe</span>" : "<span class='status-error'>❌ No encontrado</span>";
            echo "<tr><td>$file</td><td>$desc</td><td>$status</td></tr>";
            if (!$exists) {
                $warnings[] = "Archivo $file no encontrado";
            }
        }
        echo "</table>";
        
        // ====================================
        // 3. VERIFICAR CONEXIÓN A BD
        // ====================================
        echo "<h2>3. Conexión a Base de Datos</h2>";
        
        try {
            if (file_exists('db.php')) {
                require_once '../db.php';
                
                if (isset($pdo) && $pdo instanceof PDO) {
                    echo "<div class='success'>✅ Conexión a base de datos establecida</div>";
                    
                    // Verificar base de datos
                    $stmt = $pdo->query("SELECT DATABASE() as dbname");
                    $db = $stmt->fetch(PDO::FETCH_ASSOC);
                    echo "<div class='info'>📊 Base de datos activa: <strong>{$db['dbname']}</strong></div>";
                    
                    // Obtener TODAS las tablas reales
                    echo "<h3>Tablas de la Base de Datos:</h3>";
                    $stmt = $pdo->query("SHOW TABLES");
                    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    echo "<div class='info'>📋 Total de tablas encontradas: <strong>" . count($allTables) . "</strong></div>";
                    
                    // Mostrar todas las tablas con su estructura
                    echo "<table>";
                    echo "<tr><th>Tabla</th><th>Columnas</th><th>Registros</th></tr>";
                    
                    foreach ($allTables as $table) {
                        try {
                            // Contar registros
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                            $count = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            // Obtener columnas
                            $stmt = $pdo->query("SHOW COLUMNS FROM `$table`");
                            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            $colList = implode(', ', array_slice($columns, 0, 3));
                            if (count($columns) > 3) {
                                $colList .= "... (+" . (count($columns) - 3) . " más)";
                            }
                            
                            echo "<tr>";
                            echo "<td><strong>$table</strong></td>";
                            echo "<td style='font-size: 11px;'>$colList</td>";
                            echo "<td>{$count['count']}</td>";
                            echo "</tr>";
                        } catch (Exception $e) {
                            echo "<tr><td>$table</td><td colspan='2'><span class='status-error'>Error: {$e->getMessage()}</span></td></tr>";
                        }
                    }
                    echo "</table>";
                    
                    // ====================================
                    // 4. IDENTIFICAR TABLAS CORRECTAS
                    // ====================================
                    echo "<h2>4. Identificación de Tablas del Sistema</h2>";
                    
                    // Buscar tablas por patrones (minúsculas o PascalCase)
                    $tableMap = [];
                    $patterns = [
                        'persona' => ['persona', 'Persona'],
                        'miembro' => ['miembro', 'Miembro'],
                        'tipomembresia' => ['tipomembresia', 'TipoMembresia', 'tipo_membresia'],
                        'membresia' => ['membresia', 'Membresia'],
                        'pago' => ['pago', 'Pago'],
                        'descuento' => ['descuento', 'Descuento']
                    ];
                    
                    echo "<table>";
                    echo "<tr><th>Tabla Requerida</th><th>Tabla Encontrada</th><th>Estado</th></tr>";
                    
                    foreach ($patterns as $required => $variants) {
                        $found = false;
                        $foundName = '';
                        
                        foreach ($variants as $variant) {
                            if (in_array($variant, $allTables)) {
                                $found = true;
                                $foundName = $variant;
                                break;
                            }
                        }
                        
                        if ($found) {
                            echo "<tr><td>$required</td><td><strong>$foundName</strong></td><td><span class='status-ok'>✅ OK</span></td></tr>";
                            $tableMap[$required] = $foundName;
                        } else {
                            echo "<tr><td>$required</td><td>-</td><td><span class='status-error'>❌ No encontrada</span></td></tr>";
                            $errors[] = "Tabla $required no encontrada";
                        }
                    }
                    echo "</table>";
                    
                    // ====================================
                    // 5. VERIFICAR DATOS
                    // ====================================
                    if (!empty($tableMap)) {
                        echo "<h2>5. Datos de Prueba</h2>";
                        
                        // Miembros
                        if (isset($tableMap['miembro'])) {
                            $miembroTable = $tableMap['miembro'];
                            
                            // Verificar si existe la columna 'estado'
                            $stmt = $pdo->query("SHOW COLUMNS FROM `$miembroTable`");
                            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (in_array('estado', $columns)) {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$miembroTable` WHERE estado = 'activo'");
                            } else {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$miembroTable`");
                            }
                            
                            $miembros = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo "<div class='info'>👥 Miembros encontrados: <strong>{$miembros['count']}</strong></div>";
                            
                            if ($miembros['count'] == 0) {
                                echo "<div class='warning'>⚠️ No hay miembros registrados. Necesitas insertar datos de prueba.</div>";
                                $warnings[] = "Sin datos de miembros";
                            }
                            
                            // Mostrar algunos miembros de ejemplo
                            if ($miembros['count'] > 0) {
                                $stmt = $pdo->query("SELECT * FROM `$miembroTable` LIMIT 3");
                                $ejemplos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                echo "<h4>Ejemplos de Miembros:</h4>";
                                echo "<div class='code'>";
                                foreach ($ejemplos as $ejemplo) {
                                    echo "ID: {$ejemplo['idMiembro']} | DNI: {$ejemplo['dni']}<br>";
                                }
                                echo "</div>";
                            }
                        }
                        
                        // Tipos de membresía
                        if (isset($tableMap['tipomembresia'])) {
                            $tipoTable = $tableMap['tipomembresia'];
                            
                            // Verificar columna activo
                            $stmt = $pdo->query("SHOW COLUMNS FROM `$tipoTable`");
                            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (in_array('activo', $columns)) {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tipoTable` WHERE activo = TRUE");
                            } else {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$tipoTable`");
                            }
                            
                            $tipos = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo "<div class='info'>💳 Tipos de membresía: <strong>{$tipos['count']}</strong></div>";
                        }
                        
                        // Descuentos
                        if (isset($tableMap['descuento'])) {
                            $descTable = $tableMap['descuento'];
                            
                            $stmt = $pdo->query("SHOW COLUMNS FROM `$descTable`");
                            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            if (in_array('activo', $columns)) {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$descTable` WHERE activo = TRUE");
                            } else {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$descTable`");
                            }
                            
                            $descuentos = $stmt->fetch(PDO::FETCH_ASSOC);
                            echo "<div class='info'>🏷️ Descuentos: <strong>{$descuentos['count']}</strong></div>";
                        }
                    }
                    
                    // ====================================
                    // 6. GENERAR QUERIES CORRECTAS
                    // ====================================
                    if (!empty($tableMap)) {
                        echo "<h2>6. Queries SQL Corregidas</h2>";
                        echo "<div class='info'>";
                        echo "<h4>Usa estas queries para consultar tus datos:</h4>";
                        echo "<div class='code'>";
                        
                        if (isset($tableMap['miembro']) && isset($tableMap['persona'])) {
                            $miembroTable = $tableMap['miembro'];
                            $personaTable = $tableMap['persona'];
                            
                            echo "-- Ver miembros con sus datos personales<br>";
                            echo "SELECT m.*, p.nombre, p.apellido, p.email<br>";
                            echo "FROM `$miembroTable` m<br>";
                            echo "INNER JOIN `$personaTable` p ON m.dni = p.dni;<br><br>";
                        }
                        
                        if (isset($tableMap['pago'])) {
                            $pagoTable = $tableMap['pago'];
                            echo "-- Ver todos los pagos<br>";
                            echo "SELECT * FROM `$pagoTable` ORDER BY fechaPago DESC;<br><br>";
                        }
                        
                        echo "</div>";
                        echo "</div>";
                    }
                    
                } else {
                    echo "<div class='error'>❌ No se pudo establecer la conexión a la base de datos</div>";
                    $errors[] = "Error de conexión a BD";
                }
            } else {
                echo "<div class='error'>❌ Archivo db.php no encontrado</div>";
                $errors[] = "db.php no existe";
            }
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
            $errors[] = "Error de BD: " . $e->getMessage();
        }
        
        // ====================================
        // CONFIGURACIÓN
        // ====================================
        echo "<h2>7. Configuración del Sistema</h2>";
        
        echo "<div class='code'>";
        echo "<strong>Ruta del sistema:</strong> " . __DIR__ . "<br>";
        echo "<strong>URL base:</strong> http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "<br>";
        echo "<strong>Servidor:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
        echo "</div>";
        
        // Detectar carpeta
        $folder = basename(dirname($_SERVER['PHP_SELF']));
        if ($folder === '/') {
            $folder = basename(__DIR__);
        }
        
        echo "<div class='warning'>";
        echo "<h4>⚠️ IMPORTANTE - Configuración de payment.js:</h4>";
        echo "<div class='code'>";
        echo "const API_URL = 'http://localhost/$folder';<br>";
        echo "</div>";
        echo "<p>Asegúrate de que esta línea esté en <strong>payment.js</strong> (línea 4)</p>";
        echo "</div>";
        
        // ====================================
        // RESUMEN
        // ====================================
        echo "<h2>📋 Resumen</h2>";
        
        if (count($errors) == 0) {
            echo "<div class='success'>";
            echo "<h3>✅ Sistema Operativo</h3>";
            echo "<p>La base de datos está conectada y contiene las tablas necesarias.</p>";
            
            if (count($warnings) > 0) {
                echo "<h4>⚠️ Advertencias:</h4><ul>";
                foreach ($warnings as $warning) {
                    echo "<li>$warning</li>";
                }
                echo "</ul>";
            }
            
            echo "<a href='payment.html' class='btn'>🚀 Abrir Sistema de Pagos</a>";
            echo "</div>";
        } else {
            echo "<div class='error'>";
            echo "<h3>❌ Errores Encontrados</h3>";
            echo "<ul>";
            foreach ($errors as $error) {
                echo "<li>$error</li>";
            }
            echo "</ul>";
            echo "</div>";
        }
        
        // ====================================
        // INSTRUCCIONES
        // ====================================
        echo "<h2>📚 Próximos Pasos</h2>";
        
        echo "<div class='info'>";
        echo "<ol>";
        echo "<li><strong>Actualiza payment.js</strong> con la URL correcta mostrada arriba</li>";
        echo "<li><strong>Actualiza get_data.php</strong> para usar los nombres de tablas en minúsculas</li>";
        echo "<li><strong>Actualiza process_payment.php</strong> con los nombres correctos</li>";
        echo "<li><strong>Inserta datos de prueba</strong> si no hay miembros</li>";
        echo "</ol>";
        echo "</div>";
        ?>
        
    </div>
</body>
</html>
