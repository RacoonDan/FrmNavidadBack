-- Ejecutar en la base de datos de Railway (pestaña Data > Query del servicio MySQL).
-- No se crea ni se selecciona base de datos: Railway ya entrega una (variable MYSQLDATABASE).

CREATE TABLE IF NOT EXISTS personas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS registros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    persona_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion1 TEXT,
    enlace1 VARCHAR(2048),
    imagen1 MEDIUMBLOB,
    imagen1_tipo VARCHAR(50),
    descripcion2 TEXT,
    enlace2 VARCHAR(2048),
    imagen2 MEDIUMBLOB,
    imagen2_tipo VARCHAR(50),
    descripcion3 TEXT,
    enlace3 VARCHAR(2048),
    imagen3 MEDIUMBLOB,
    imagen3_tipo VARCHAR(50),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_persona_registro FOREIGN KEY (persona_id)
        REFERENCES personas(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

INSERT INTO personas (id, nombre) VALUES
(1, 'Gianina'), (2, 'Daniel'), (3, 'Fiorela'), (4, 'Jordan'), (5, 'Ariana'), (6, 'Camila')
ON DUPLICATE KEY UPDATE nombre = VALUES(nombre);

-- ------------------------------------------------------------------
-- SOLO si la tabla "registros" YA existía con la estructura anterior
-- (imagen1 VARCHAR), ejecuta esto en lugar de recrearla:
--
-- ALTER TABLE registros
--   MODIFY imagen1 MEDIUMBLOB, ADD COLUMN imagen1_tipo VARCHAR(50) AFTER imagen1,
--   MODIFY imagen2 MEDIUMBLOB, ADD COLUMN imagen2_tipo VARCHAR(50) AFTER imagen2,
--   MODIFY imagen3 MEDIUMBLOB, ADD COLUMN imagen3_tipo VARCHAR(50) AFTER imagen3;
-- ------------------------------------------------------------------
