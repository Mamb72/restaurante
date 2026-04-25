-- =========================================================================
-- db/datos_usuarios.sql
-- Datos demo de personal (admin y cocina) para desarrollo y defensa.
--
-- Contraseñas en claro (SOLO para desarrollo):
--   admin@restaurante.local    ->  admin1234
--   cocina@restaurante.local   ->  cocina1234
--
-- Los hashes son bcrypt cost=12, generados con password_hash() de PHP.
--
-- Ejecutar con:
--   mysql -u root -p restaurante < db/datos_usuarios.sql
-- =========================================================================

INSERT INTO usuarios (nombre, email, hash_password, rol, activo) VALUES
    ('Administrador',
     'admin@restaurante.local',
     '$2y$12$h5auPb9Rs3whqvPE/uOfXujDQgNQ79JF2khBS4Wcvi.5obpDkknHG',
     'admin',
     TRUE),
    ('Cocina',
     'cocina@restaurante.local',
     '$2y$12$NYCOoc2d8MTW8XovoIEVtOkfNF7KY8bBadfvaWdF56m62kJb5iuxy',
     'cocina',
     TRUE);

-- Comprobación rápida
SELECT id, nombre, email, rol, activo FROM usuarios;
