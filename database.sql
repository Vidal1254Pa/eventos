-- database.sql
CREATE DATABASE IF NOT EXISTS asistencia_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE asistencia_db;

-- Usuarios del sistema (login)
CREATE TABLE IF NOT EXISTS usuarios (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  usuario VARCHAR(60) NOT NULL UNIQUE,
  pass_hash VARCHAR(255) NOT NULL,
  rol ENUM('admin','registro','viewer') NOT NULL DEFAULT 'viewer',
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Personas a las que se les toma asistencia (asistentes)
CREATE TABLE IF NOT EXISTS asistentes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  dni VARCHAR(15) NOT NULL UNIQUE,
  nombres VARCHAR(120) NOT NULL,
  apellidos VARCHAR(120) NOT NULL,
  mesa VARCHAR(50) NOT NULL,
  area VARCHAR(120) NULL,
  cargo VARCHAR(120) NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Eventos (reuniones, talleres, etc.)
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

-- Registro de asistencia
CREATE TABLE IF NOT EXISTS asistencias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  evento_id INT NOT NULL,
  asistente_id INT NOT NULL,
  fecha DATE NOT NULL,
  hora TIME NOT NULL,
  estado ENUM('PRESENTE','TARDE') NOT NULL DEFAULT 'PRESENTE',
  registrado_por INT NOT NULL,
  obs VARCHAR(255) NULL,
  creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_asistencia_evento (evento_id, asistente_id),
  CONSTRAINT fk_asist_evento FOREIGN KEY (evento_id) REFERENCES eventos(id) ON DELETE CASCADE,
  CONSTRAINT fk_asist_asistente FOREIGN KEY (asistente_id) REFERENCES asistentes(id) ON DELETE CASCADE,
  CONSTRAINT fk_asist_user FOREIGN KEY (registrado_por) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Usuario admin por defecto: admin / admin123 (cambiar luego)
INSERT INTO usuarios (nombre, usuario, pass_hash, rol, activo)
VALUES ('Administrador', 'admin', '$2y$10$k4m1bQ8xB7B5y3R1GkY0hO3Rjv8TRaQm7gJk8cYB4Qh0j1m3yKQeK', 'admin', 1)
ON DUPLICATE KEY UPDATE usuario=usuario;

-- Nota: el hash anterior corresponde a "admin123"
