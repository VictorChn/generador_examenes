CREATE TABLE temas (
    id_tema INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE preguntas (
    id_pregunta INT AUTO_INCREMENT PRIMARY KEY,
    id_tema INT NOT NULL,
    tipo ENUM('opcion_multiple', 'verdadero_falso') NOT NULL,
    enunciado TEXT NOT NULL,
    opcion_a VARCHAR(255) NULL,
    opcion_b VARCHAR(255) NULL,
    opcion_c VARCHAR(255) NULL,
    opcion_d VARCHAR(255) NULL,
    respuesta_correcta VARCHAR(20) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_tema) REFERENCES temas(id_tema)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE examenes (
    id_examen INT AUTO_INCREMENT PRIMARY KEY,
    titulo VARCHAR(150) NOT NULL,
    total_preguntas INT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE examen_preguntas (
    id_examen_pregunta INT AUTO_INCREMENT PRIMARY KEY,
    id_examen INT NOT NULL,
    id_pregunta INT NOT NULL,
    FOREIGN KEY (id_examen) REFERENCES examenes(id_examen)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (id_pregunta) REFERENCES preguntas(id_pregunta)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE resultados (
    id_resultado INT AUTO_INCREMENT PRIMARY KEY,
    id_examen INT NOT NULL,
    nombre_estudiante VARCHAR(120) NOT NULL,
    total_preguntas INT NOT NULL,
    respuestas_correctas INT NOT NULL,
    calificacion DECIMAL(5,2) NOT NULL,
    fecha_presentacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_examen) REFERENCES examenes(id_examen)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE respuestas_estudiante (
    id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
    id_resultado INT NOT NULL,
    id_pregunta INT NOT NULL,
    respuesta_estudiante VARCHAR(20) NOT NULL,
    es_correcta TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_resultado) REFERENCES resultados(id_resultado)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    FOREIGN KEY (id_pregunta) REFERENCES preguntas(id_pregunta)
        ON UPDATE CASCADE
        ON DELETE CASCADE
);

CREATE TABLE restablecimientos_password (
    id_restablecimiento INT AUTO_INCREMENT PRIMARY KEY,
    correo VARCHAR(100) NOT NULL,
    token VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expirado TINYINT(1) DEFAULT 0
);

INSERT INTO temas (nombre, descripcion) VALUES
('Matematicas', 'Preguntas relacionadas con operaciones y razonamiento matematico.'),
('Historia', 'Preguntas sobre hechos historicos.'),
('Ciencias', 'Preguntas de ciencias naturales.');

INSERT INTO preguntas (
    id_tema,
    tipo,
    enunciado,
    opcion_a,
    opcion_b,
    opcion_c,
    opcion_d,
    respuesta_correcta
) VALUES
(1, 'opcion_multiple', 'Cuanto es 5 + 3?', '6', '7', '8', '9', 'C'),
(1, 'verdadero_falso', 'El numero 10 es mayor que el numero 4.', 'Verdadero', 'Falso', NULL, NULL, 'Verdadero'),
(2, 'opcion_multiple', 'En que ano inicio la Independencia de Mexico?', '1810', '1821', '1910', '1521', 'A'),
(3, 'verdadero_falso', 'El agua esta formada por hidrogeno y oxigeno.', 'Verdadero', 'Falso', NULL, NULL, 'Verdadero');
