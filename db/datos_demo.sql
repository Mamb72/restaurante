-- ====================================================================
-- db/datos_demo.sql
-- Datos de demostración para presentar el proyecto.
-- ====================================================================

USE restaurante;

-- Limpiar tablas (orden inverso a las dependencias)
DELETE FROM plato_etiqueta;
DELETE FROM plato_alergeno;
DELETE FROM platos;
DELETE FROM categorias;
ALTER TABLE platos AUTO_INCREMENT = 1;
ALTER TABLE categorias AUTO_INCREMENT = 1;

-- Categorías
INSERT INTO categorias (nombre, orden, activa) VALUES
  ('Entrantes',   1, TRUE),
  ('Principales', 2, TRUE),
  ('Bebidas',     3, TRUE),
  ('Postres',     4, TRUE);

-- Platos
INSERT INTO platos (categoria_id, nombre, descripcion, precio, disponible, destacado, orden, activo) VALUES
  (1, 'Croquetas caseras de jamón',
      'Seis unidades crujientes con bechamel suave de jamón ibérico.',
      9.50, TRUE, TRUE, 1, TRUE),
  (1, 'Ensalada de la huerta',
      'Tomate raf, lechuga, cebolla morada, aceitunas y aceite de oliva virgen extra.',
      8.00, TRUE, FALSE, 2, TRUE),
  (1, 'Hummus con crudités',
      'Crema de garbanzos con tahini, acompañada de zanahoria, pepino y pan de pita.',
      7.50, TRUE, FALSE, 3, TRUE),
  (2, 'Solomillo al pimienta',
      'Solomillo de ternera con salsa de pimienta verde y patatas panaderas.',
      18.50, TRUE, TRUE, 1, TRUE),
  (2, 'Risotto de setas',
      'Arroz arborio cocinado con caldo de setas y parmesano.',
      14.00, TRUE, FALSE, 2, TRUE),
  (2, 'Lubina al horno',
      'Lubina entera al horno con verduras de temporada.',
      19.00, TRUE, FALSE, 3, TRUE),
  (3, 'Agua mineral',
      'Botella 50cl. Con o sin gas.',
      2.00, TRUE, FALSE, 1, TRUE),
  (3, 'Refresco',
      'Cola, naranja o limón. Lata 33cl.',
      2.80, TRUE, FALSE, 2, TRUE),
  (3, 'Vino de la casa (copa)',
      'Tinto, blanco o rosado.',
      3.50, TRUE, FALSE, 3, TRUE),
  (4, 'Tarta de queso',
      'Receta tradicional al horno con coulis de frutos rojos.',
      5.50, TRUE, TRUE, 1, TRUE),
  (4, 'Sorbete de limón',
      'Helado artesanal de limón con un toque de cava.',
      4.50, TRUE, FALSE, 2, TRUE);

-- Asociar alérgenos (los códigos vienen de schema.sql)
INSERT INTO plato_alergeno (plato_id, alergeno_id)
  SELECT 1, id FROM alergenos WHERE codigo IN ('gluten', 'lacteos', 'huevos');
INSERT INTO plato_alergeno (plato_id, alergeno_id)
  SELECT 3, id FROM alergenos WHERE codigo IN ('gluten', 'sesamo');
INSERT INTO plato_alergeno (plato_id, alergeno_id)
  SELECT 5, id FROM alergenos WHERE codigo IN ('lacteos');
INSERT INTO plato_alergeno (plato_id, alergeno_id)
  SELECT 6, id FROM alergenos WHERE codigo IN ('pescado');
INSERT INTO plato_alergeno (plato_id, alergeno_id)
  SELECT 9, id FROM alergenos WHERE codigo IN ('sulfitos');
INSERT INTO plato_alergeno (plato_id, alergeno_id)
  SELECT 10, id FROM alergenos WHERE codigo IN ('gluten', 'lacteos', 'huevos');

-- Asociar etiquetas dietéticas
INSERT INTO plato_etiqueta (plato_id, etiqueta_id)
  SELECT 2, id FROM etiquetas_dieteticas WHERE codigo IN ('vegetariano', 'vegano', 'sin_gluten', 'sin_lactosa');
INSERT INTO plato_etiqueta (plato_id, etiqueta_id)
  SELECT 3, id FROM etiquetas_dieteticas WHERE codigo IN ('vegetariano', 'vegano');
INSERT INTO plato_etiqueta (plato_id, etiqueta_id)
  SELECT 5, id FROM etiquetas_dieteticas WHERE codigo IN ('vegetariano');
INSERT INTO plato_etiqueta (plato_id, etiqueta_id)
  SELECT 7, id FROM etiquetas_dieteticas WHERE codigo IN ('vegetariano', 'vegano', 'sin_gluten', 'sin_lactosa');
INSERT INTO plato_etiqueta (plato_id, etiqueta_id)
  SELECT 11, id FROM etiquetas_dieteticas WHERE codigo IN ('vegetariano', 'sin_gluten');
