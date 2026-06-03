-- =============================================
-- Tablas para módulo de Login y Admin
-- Agregar a base_datos.sql
-- =============================================

-- Tabla de administradores
CREATE TABLE administradores (
    id_admin INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de profesores (gestionada por el admin)
CREATE TABLE profesores (
    id_profesor INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(120) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    numero_empleado VARCHAR(30) NOT NULL UNIQUE,
    materia VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    estado ENUM('activo', 'suspendido') NOT NULL DEFAULT 'activo',
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Admin de prueba (password: Admin123#)
INSERT INTO administradores (nombre, correo, password) VALUES
('Administrador', 'admin@instituto.edu.mx', '$2y$10$exampleHashHere');

-- Profesores de prueba
INSERT INTO profesores (nombre_completo, correo, numero_empleado, materia, password, estado) VALUES
('Juan Carlos Pérez López',   'jperez@instituto.edu.mx',   'EMP-001', 'Matemáticas', '$2y$10$exampleHashHere', 'activo'),
('María González Ruiz',       'mgonzalez@instituto.edu.mx','EMP-002', 'Historia',     '$2y$10$exampleHashHere', 'activo'),
('Roberto Sánchez Torres',    'rsanchez@instituto.edu.mx', 'EMP-003', 'Ciencias',     '$2y$10$exampleHashHere', 'suspendido');
