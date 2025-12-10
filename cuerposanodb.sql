-- Base de datos para el sistema CuerpoSano
-- Motor: MySQL

DROP DATABASE IF EXISTS CuerpoSanoDB;
CREATE DATABASE CuerpoSanoDB CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE CuerpoSanoDB;

-- ============================================
-- TABLA: Persona
-- Almacena información básica de todas las personas
-- ============================================
CREATE TABLE Persona (
    dni VARCHAR(20) PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    direccion VARCHAR(200),
    telefono VARCHAR(20),
    fechaNacimiento DATE,
    email VARCHAR(100) UNIQUE,
    INDEX idx_nombre (nombre),
    INDEX idx_apellido (apellido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: TipoMembresia
-- Define los tipos de membresías disponibles
-- ============================================
CREATE TABLE TipoMembresia (
    idTipo INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(300),
    precioBase DECIMAL(10,2) NOT NULL,
    duracionDias INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    fechaCreacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CHECK (precioBase > 0),
    CHECK (duracionDias > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Membresia
-- Registro de membresías específicas
-- ============================================
CREATE TABLE Membresia (
    idMembresia INT AUTO_INCREMENT PRIMARY KEY,
    idTipo INT NOT NULL,
    costo DECIMAL(10,2) NOT NULL,
    duracion INT NOT NULL,
    fechaRegistro DATE NOT NULL,
    estado ENUM('activa', 'vencida', 'cancelada') DEFAULT 'activa',
    FOREIGN KEY (idTipo) REFERENCES TipoMembresia(idTipo) ON DELETE RESTRICT,
    CHECK (costo >= 0),
    CHECK (duracion > 0),
    INDEX idx_estado (estado),
    INDEX idx_fecha (fechaRegistro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Empleado
-- Información de empleados del gimnasio
-- ============================================
CREATE TABLE Empleado (
    idEmpleado INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(20) NOT NULL UNIQUE,
    cargo VARCHAR(100) NOT NULL,
    fechaIngreso DATE NOT NULL,
    usuario VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    activo BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (dni) REFERENCES Persona(dni) ON DELETE RESTRICT,
    INDEX idx_usuario (usuario),
    INDEX idx_cargo (cargo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Entrenador
-- Información de entrenadores
-- ============================================
CREATE TABLE Entrenador (
    idEntrenador INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    especialidad VARCHAR(100),
    certificacion BOOLEAN NOT NULL DEFAULT FALSE,
    fechaCertificacion DATE,
    activo BOOLEAN DEFAULT TRUE,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Actividad
-- Catálogo de actividades ofrecidas
-- ============================================
CREATE TABLE Actividad (
    idActividad INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion VARCHAR(300),
    duracion INT NOT NULL COMMENT 'Duración en minutos',
    activo BOOLEAN DEFAULT TRUE,
    CHECK (duracion > 0),
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Clase
-- Define las clases disponibles
-- ============================================
CREATE TABLE Clase (
    idClase INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    horaInicio TIME NOT NULL,
    horaFin TIME NOT NULL,
    capacidad INT NOT NULL,
    idEntrenador INT NOT NULL,
    idActividad INT NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    CHECK (capacidad > 0),
    CHECK (horaFin > horaInicio),
    FOREIGN KEY (idEntrenador) REFERENCES Entrenador(idEntrenador) ON DELETE RESTRICT,
    FOREIGN KEY (idActividad) REFERENCES Actividad(idActividad) ON DELETE RESTRICT,
    INDEX idx_horario (horaInicio, horaFin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: HorarioClase
-- Horarios semanales de las clases
-- ============================================
CREATE TABLE HorarioClase (
    idHorario INT AUTO_INCREMENT PRIMARY KEY,
    diaSemana ENUM('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo') NOT NULL,
    horaInicio TIME NOT NULL,
    horaFin TIME NOT NULL,
    estado ENUM('activo', 'cancelado', 'completo') DEFAULT 'activo',
    idClase INT NOT NULL,
    CHECK (horaFin > horaInicio),
    FOREIGN KEY (idClase) REFERENCES Clase(idClase) ON DELETE CASCADE,
    INDEX idx_dia (diaSemana),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Descuento
-- Catálogo de descuentos disponibles
-- ============================================
CREATE TABLE Descuento (
    idDescuento INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(300) NOT NULL,
    porcentaje DECIMAL(5,2) NOT NULL,
    tipo VARCHAR(50),
    vigenteDesde DATE NOT NULL,
    vigenteHasta DATE NOT NULL,
    activo BOOLEAN DEFAULT TRUE,
    CHECK (porcentaje BETWEEN 0 AND 100),
    CHECK (vigenteHasta > vigenteDesde),
    INDEX idx_vigencia (vigenteDesde, vigenteHasta),
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Miembro
-- Información de miembros del gimnasio
-- ============================================
CREATE TABLE Miembro (
    idMiembro INT AUTO_INCREMENT PRIMARY KEY,
    dni VARCHAR(20) NOT NULL UNIQUE,
    foto VARCHAR(200),
    idTipoMembresia INT NOT NULL,
    idMembresia INT NOT NULL,
    fechaInicio DATE NOT NULL,
    fechaFin DATE NOT NULL,
    descuento DECIMAL(5,2) DEFAULT 0,
    estado ENUM('activo', 'inactivo', 'suspendido') DEFAULT 'activo',
    CHECK (descuento >= 0 AND descuento <= 100),
    CHECK (fechaFin > fechaInicio),
    FOREIGN KEY (dni) REFERENCES Persona(dni) ON DELETE RESTRICT,
    FOREIGN KEY (idTipoMembresia) REFERENCES TipoMembresia(idTipo) ON DELETE RESTRICT,
    FOREIGN KEY (idMembresia) REFERENCES Membresia(idMembresia) ON DELETE RESTRICT,
    INDEX idx_estado (estado),
    INDEX idx_fechas (fechaInicio, fechaFin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Lector
-- Dispositivos de lectura para acceso
-- ============================================
CREATE TABLE Lector (
    idLector INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL,
    modelo VARCHAR(100),
    ubicacion VARCHAR(100),
    activo BOOLEAN DEFAULT TRUE,
    fechaInstalacion DATE,
    INDEX idx_tipo (tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Asistencia
-- Registro de asistencias de miembros
-- ============================================
CREATE TABLE Asistencia (
    idAsistencia INT AUTO_INCREMENT PRIMARY KEY,
    fecha DATE NOT NULL,
    horaIngreso TIME NOT NULL,
    horaEgreso TIME,
    idMiembro INT NOT NULL,
    idClase INT,
    idLector INT,
    FOREIGN KEY (idMiembro) REFERENCES Miembro(idMiembro) ON DELETE CASCADE,
    FOREIGN KEY (idClase) REFERENCES Clase(idClase) ON DELETE SET NULL,
    FOREIGN KEY (idLector) REFERENCES Lector(idLector) ON DELETE SET NULL,
    INDEX idx_fecha (fecha),
    INDEX idx_miembro_fecha (idMiembro, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: InscripcionClase
-- Inscripciones de miembros a clases
-- ============================================
CREATE TABLE InscripcionClase (
    idInscripcion INT AUTO_INCREMENT PRIMARY KEY,
    estado ENUM('pendiente', 'confirmada', 'cancelada', 'asistio', 'ausente') DEFAULT 'confirmada',
    fechaInscripcion DATE NOT NULL,
    idMiembro INT NOT NULL,
    idClase INT NOT NULL,
    FOREIGN KEY (idMiembro) REFERENCES Miembro(idMiembro) ON DELETE CASCADE,
    FOREIGN KEY (idClase) REFERENCES Clase(idClase) ON DELETE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_fecha (fechaInscripcion),
    UNIQUE KEY unique_inscripcion (idMiembro, idClase, fechaInscripcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Pago
-- Registro de pagos de miembros
-- ============================================
CREATE TABLE Pago (
    idPago INT AUTO_INCREMENT PRIMARY KEY,
    monto DECIMAL(10,2) NOT NULL,
    fechaPago DATE NOT NULL,
    metodoPago ENUM('Efectivo', 'Tarjeta de Crédito', 'Tarjeta de Débito', 'Transferencia Bancaria', 'QR') NOT NULL,
    referencia VARCHAR(100),
    idMiembro INT NOT NULL,
    estado ENUM('pendiente', 'completado', 'rechazado') DEFAULT 'completado',
    CHECK (monto > 0),
    FOREIGN KEY (idMiembro) REFERENCES Miembro(idMiembro) ON DELETE RESTRICT,
    INDEX idx_fecha (fechaPago),
    INDEX idx_metodo (metodoPago),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: Reporte
-- Registro de reportes generados
-- ============================================
CREATE TABLE Reporte (
    idReporte INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(100) NOT NULL,
    contenido TEXT,
    idEmpleado INT NOT NULL,
    fechaGeneracion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idEmpleado) REFERENCES Empleado(idEmpleado) ON DELETE RESTRICT,
    INDEX idx_tipo (tipo),
    INDEX idx_fecha (fechaGeneracion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: MiembroDescuento
-- Relación muchos a muchos entre Miembros y Descuentos
-- ============================================
CREATE TABLE MiembroDescuento (
    idMiembro INT NOT NULL,
    idDescuento INT NOT NULL,
    fechaAplicacion DATE DEFAULT (CURRENT_DATE),
    PRIMARY KEY (idMiembro, idDescuento),
    FOREIGN KEY (idMiembro) REFERENCES Miembro(idMiembro) ON DELETE CASCADE,
    FOREIGN KEY (idDescuento) REFERENCES Descuento(idDescuento) ON DELETE CASCADE,
    INDEX idx_fecha (fechaAplicacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLA: DescuentoInscripcion
-- Descuentos aplicados a inscripciones
-- ============================================
CREATE TABLE DescuentoInscripcion (
    idDescuento INT NOT NULL,
    idInscripcion INT NOT NULL,
    fechaRegistro DATE DEFAULT (CURRENT_DATE),
    PRIMARY KEY (idDescuento, idInscripcion),
    FOREIGN KEY (idDescuento) REFERENCES Descuento(idDescuento) ON DELETE CASCADE,
    FOREIGN KEY (idInscripcion) REFERENCES InscripcionClase(idInscripcion) ON DELETE CASCADE,
    INDEX idx_fecha (fechaRegistro)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DATOS DE PRUEBA
-- ============================================

-- Insertar Personas
INSERT INTO Persona (dni, nombre, apellido, direccion, telefono, fechaNacimiento, email) VALUES
('12345678', 'Juan', 'Pérez', 'Av. Principal 123', '555-0001', '1985-03-15', 'juan.perez@email.com'),
('87654321', 'María', 'García', 'Calle Secundaria 456', '555-0002', '1990-07-22', 'maria.garcia@email.com'),
('11223344', 'Carlos', 'López', 'Av. Norte 789', '555-0003', '1988-11-10', 'carlos.lopez@email.com'),
('99887766', 'Ana', 'Martínez', 'Calle Sur 321', '555-0004', '1992-05-18', 'ana.martinez@email.com'),
('55443322', 'Pedro', 'Rodríguez', 'Av. Este 654', '555-0005', '1987-09-25', 'pedro.rodriguez@email.com');

-- Insertar Tipos de Membresía
INSERT INTO TipoMembresia (nombre, descripcion, precioBase, duracionDias) VALUES
('Básica', 'Acceso al gimnasio general', 50.00, 30),
('Premium', 'Acceso con clases grupales', 80.00, 30),
('VIP', 'Acceso con entrenador personal', 150.00, 30),
('Anual', 'Membresía anual con descuento', 500.00, 365);

-- Insertar Membresías
INSERT INTO Membresia (idTipo, costo, duracion, fechaRegistro, estado) VALUES
(1, 50.00, 30, '2024-01-01', 'activa'),
(2, 80.00, 30, '2024-01-01', 'activa'),
(3, 150.00, 30, '2024-01-01', 'activa'),
(4, 450.00, 365, '2024-01-01', 'activa');

-- Insertar Empleados
INSERT INTO Empleado (dni, cargo, fechaIngreso, usuario, password, activo) VALUES
('12345678', 'Administrador', '2023-01-15', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
('87654321', 'Recepcionista', '2023-02-01', 'recep', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE),
('11223344', 'Supervisor', '2023-01-20', 'super', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', TRUE);

-- Insertar Entrenadores
INSERT INTO Entrenador (nombre, especialidad, certificacion, fechaCertificacion, activo) VALUES
('Roberto Silva', 'Yoga y Pilates', TRUE, '2022-06-15', TRUE),
('Ana Martínez', 'CrossFit', TRUE, '2021-03-20', TRUE),
('Luis Rodríguez', 'Musculación', FALSE, NULL, TRUE),
('Carmen López', 'Spinning', TRUE, '2022-09-10', TRUE),
('Miguel Torres', 'Zumba', TRUE, '2023-01-05', TRUE);

-- Insertar Actividades
INSERT INTO Actividad (nombre, descripcion, duracion, activo) VALUES
('Yoga', 'Clase de relajación y flexibilidad', 60, TRUE),
('CrossFit', 'Entrenamiento funcional de alta intensidad', 45, TRUE),
('Pilates', 'Ejercicios de fortalecimiento del core', 50, TRUE),
('Spinning', 'Ciclismo indoor con música', 45, TRUE),
('Zumba', 'Baile aeróbico latino', 60, TRUE),
('Funcional', 'Entrenamiento funcional general', 50, TRUE),
('Boxeo', 'Acondicionamiento y técnica de boxeo', 60, TRUE);

-- Insertar Clases
INSERT INTO Clase (nombre, horaInicio, horaFin, capacidad, idEntrenador, idActividad, activo) VALUES
('Yoga Matutino', '07:00:00', '08:00:00', 15, 1, 1, TRUE),
('CrossFit Intenso', '18:00:00', '18:45:00', 20, 2, 2, TRUE),
('Pilates Suave', '10:00:00', '10:50:00', 12, 3, 3, TRUE),
('Spinning Nocturno', '19:00:00', '19:45:00', 25, 4, 4, TRUE),
('Zumba Express', '18:30:00', '19:30:00', 30, 5, 5, TRUE);

-- Insertar Horarios de Clases
INSERT INTO HorarioClase (diaSemana, horaInicio, horaFin, estado, idClase) VALUES
('Lunes', '07:00:00', '08:00:00', 'activo', 1),
('Miércoles', '07:00:00', '08:00:00', 'activo', 1),
('Viernes', '07:00:00', '08:00:00', 'activo', 1),
('Lunes', '18:00:00', '18:45:00', 'activo', 2),
('Miércoles', '18:00:00', '18:45:00', 'activo', 2),
('Viernes', '18:00:00', '18:45:00', 'activo', 2);

-- Insertar Miembros
INSERT INTO Miembro (dni, foto, idTipoMembresia, idMembresia, fechaInicio, fechaFin, descuento, estado) VALUES
('99887766', 'foto1.jpg', 2, 2, '2024-01-01', '2024-01-31', 0.00, 'activo'),
('55443322', 'foto2.jpg', 3, 3, '2024-01-01', '2024-01-31', 10.00, 'activo');

-- Insertar Lectores
INSERT INTO Lector (tipo, modelo, ubicacion, activo, fechaInstalacion) VALUES
('RFID', 'Modelo Pro-100', 'Entrada Principal', TRUE, '2023-01-10'),
('Código de Barras', 'Scanner USB-200', 'Recepción', TRUE, '2023-01-10');

-- Insertar Pagos
INSERT INTO Pago (monto, fechaPago, metodoPago, referencia, idMiembro, estado) VALUES
(80.00, '2024-01-01', 'Tarjeta de Crédito', 'REF-001', 1, 'completado'),
(135.00, '2024-01-02', 'Transferencia Bancaria', 'REF-002', 2, 'completado');

-- Insertar Descuentos
INSERT INTO Descuento (descripcion, porcentaje, tipo, vigenteDesde, vigenteHasta, activo) VALUES
('Descuento estudiante', 15.00, 'Estudiante', '2024-01-01', '2024-12-31', TRUE),
('Descuento tercera edad', 20.00, 'Adulto Mayor', '2024-01-01', '2024-12-31', TRUE),
('Promoción verano', 25.00, 'Temporal', '2024-01-01', '2024-03-31', TRUE);

-- ============================================
-- VISTAS ÚTILES
-- ============================================

-- Vista de miembros activos con sus datos personales
CREATE VIEW v_miembros_activos AS
SELECT 
    m.idMiembro,
    p.dni,
    CONCAT(p.nombre, ' ', p.apellido) AS nombreCompleto,
    p.email,
    p.telefono,
    tm.nombre AS tipoMembresia,
    m.fechaInicio,
    m.fechaFin,
    m.estado,
    DATEDIFF(m.fechaFin, CURDATE()) AS diasRestantes
FROM Miembro m
INNER JOIN Persona p ON m.dni = p.dni
INNER JOIN TipoMembresia tm ON m.idTipoMembresia = tm.idTipo
WHERE m.estado = 'activo';

-- Vista de clases con información completa
CREATE VIEW v_clases_completas AS
SELECT 
    c.idClase,
    c.nombre AS nombreClase,
    a.nombre AS actividad,
    e.nombre AS entrenador,
    c.horaInicio,
    c.horaFin,
    c.capacidad,
    c.activo
FROM Clase c
INNER JOIN Actividad a ON c.idActividad = a.idActividad
INNER JOIN Entrenador e ON c.idEntrenador = e.idEntrenador;

-- ============================================
-- MENSAJE FINAL
-- ============================================
SELECT 'Base de datos CuerpoSanoDB creada exitosamente!' AS Resultado;