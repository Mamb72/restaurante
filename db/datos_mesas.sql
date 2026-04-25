-- ====================================================================
-- db/datos_mesas.sql
-- Datos de demostración para mesas físicas del restaurante.
-- Cada mesa tiene un token único de 32 caracteres que codifica su QR.
-- ====================================================================

USE restaurante;

-- Limpiar (cuidado: solo en desarrollo)
DELETE FROM sesiones_mesa;
DELETE FROM mesas;
ALTER TABLE mesas AUTO_INCREMENT = 1;

-- 5 mesas con tokens de exactamente 32 caracteres
-- (en producción se generarán con bin2hex(random_bytes(16)))
INSERT INTO mesas (numero, token_qr, activa) VALUES
  (1, 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6', TRUE),
  (2, 'b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7', TRUE),
  (3, 'c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8', TRUE),
  (4, 'd4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9', TRUE),
  (5, 'e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0', TRUE);
