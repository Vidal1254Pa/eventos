-- migrate_add_eventos.sql
-- Ejecuta esto SOLO si ya tenías el sistema anterior instalado.

USE asistencia_db;

CREATE TABLE IF NOT EXISTS eventos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(180) NOT NULL,
  descripcion VARCHAR(255) NULL,
  lugar VARCHAR(180) NULL,
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_por INT NOT NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_evento_user FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Agregar columna evento_id a asistencias (si no existe)
ALTER TABLE asistencias
  ADD COLUMN evento_id INT NULL AFTER id;

-- Asignar un evento por defecto para registros antiguos (opcional)
INSERT INTO eventos (titulo, descripcion, lugar, fecha, hora_inicio, hora_fin, activo, creado_por)
SELECT 'Asistencia General', 'Evento creado por migración', NULL, CURDATE(), '08:00:00', NULL, 1, (SELECT id FROM usuarios WHERE usuario='admin' LIMIT 1)
WHERE NOT EXISTS (SELECT 1 FROM eventos);

SET @evt := (SELECT id FROM eventos ORDER BY id ASC LIMIT 1);

UPDATE asistencias SET evento_id=@evt WHERE evento_id IS NULL;

-- Hacer NOT NULL y crear FK/UNIQUE
ALTER TABLE asistencias
  MODIFY evento_id INT NOT NULL;

ALTER TABLE asistencias
  DROP INDEX uq_asistencia_dia;

ALTER TABLE asistencias
  ADD UNIQUE KEY uq_asistencia_evento (evento_id, asistente_id);

ALTER TABLE asistencias
  ADD CONSTRAINT fk_asist_evento FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE;
