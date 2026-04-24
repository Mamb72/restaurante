-- =========================================================================
-- schema.sql
-- Base de datos del sistema de pedidos de restaurante
-- Autor: Nicolás Barrera Quintana — 2º ASIR
-- SGBD: MariaDB 10.11+
-- Motor: InnoDB  ·  Charset: utf8mb4  ·  Collation: utf8mb4_unicode_ci
-- =========================================================================

-- Crear y seleccionar la base de datos
DROP DATABASE IF EXISTS restaurante;
CREATE DATABASE restaurante
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE restaurante;

-- =========================================================================
-- TANDA A — Núcleo operativo
-- =========================================================================

-- Personal del restaurante (admins y cocina)
CREATE TABLE usuarios (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(100) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    hash_password   VARCHAR(255) NOT NULL,
    rol             ENUM('admin','cocina') NOT NULL,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mesas físicas del restaurante
CREATE TABLE mesas (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    numero          INT UNSIGNED NOT NULL,
    token_qr        CHAR(32) NOT NULL,
    activa          BOOLEAN NOT NULL DEFAULT TRUE,
    creada_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_mesas_numero (numero),
    UNIQUE KEY uk_mesas_token (token_qr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sesiones de mesa: cada ciclo "escaneo QR → pedir la cuenta"
CREATE TABLE sesiones_mesa (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    mesa_id         INT UNSIGNED NOT NULL,
    abierta_en      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cerrada_en      TIMESTAMP NULL,
    estado          ENUM('ABIERTA','PEDIDA_CUENTA','CERRADA')
                        NOT NULL DEFAULT 'ABIERTA',
    PRIMARY KEY (id),
    KEY idx_sesmesa_mesa (mesa_id),
    KEY idx_sesmesa_estado (estado),
    CONSTRAINT fk_sesmesa_mesa
        FOREIGN KEY (mesa_id) REFERENCES mesas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- TANDA B — Carta (se define antes de pedidos porque pedidos depende de ella)
-- =========================================================================

-- Categorías de la carta
CREATE TABLE categorias (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre          VARCHAR(60) NOT NULL,
    orden           INT UNSIGNED NOT NULL DEFAULT 0,
    activa          BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (id),
    UNIQUE KEY uk_categorias_nombre (nombre),
    KEY idx_categorias_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Platos
CREATE TABLE platos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id    INT UNSIGNED NOT NULL,
    nombre          VARCHAR(120) NOT NULL,
    descripcion     TEXT NULL,
    precio          DECIMAL(8,2) NOT NULL,
    ruta_foto       VARCHAR(255) NULL,
    disponible      BOOLEAN NOT NULL DEFAULT TRUE,
    destacado       BOOLEAN NOT NULL DEFAULT FALSE,
    destacado_hasta DATE NULL,
    orden           INT UNSIGNED NOT NULL DEFAULT 0,
    activo          BOOLEAN NOT NULL DEFAULT TRUE,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    actualizado_en  TIMESTAMP NOT NULL
                        DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_platos_categoria (categoria_id),
    KEY idx_platos_disponible (disponible),
    KEY idx_platos_destacado (destacado, destacado_hasta),
    KEY idx_platos_activo (activo),
    CONSTRAINT fk_platos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_platos_precio CHECK (precio >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alérgenos (catálogo maestro — los 14 de la UE)
CREATE TABLE alergenos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo          VARCHAR(30) NOT NULL,
    nombre          VARCHAR(60) NOT NULL,
    icono           VARCHAR(60) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_alergenos_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Etiquetas dietéticas (catálogo maestro)
CREATE TABLE etiquetas_dieteticas (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo          VARCHAR(30) NOT NULL,
    nombre          VARCHAR(60) NOT NULL,
    icono           VARCHAR(60) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_etiquetas_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla puente plato <-> alérgeno (N:M)
CREATE TABLE plato_alergeno (
    plato_id        INT UNSIGNED NOT NULL,
    alergeno_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (plato_id, alergeno_id),
    KEY idx_platoalg_alergeno (alergeno_id),
    CONSTRAINT fk_platoalg_plato
        FOREIGN KEY (plato_id) REFERENCES platos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_platoalg_alergeno
        FOREIGN KEY (alergeno_id) REFERENCES alergenos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla puente plato <-> etiqueta dietética (N:M)
CREATE TABLE plato_etiqueta (
    plato_id        INT UNSIGNED NOT NULL,
    etiqueta_id     INT UNSIGNED NOT NULL,
    PRIMARY KEY (plato_id, etiqueta_id),
    KEY idx_platoetq_etiqueta (etiqueta_id),
    CONSTRAINT fk_platoetq_plato
        FOREIGN KEY (plato_id) REFERENCES platos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_platoetq_etiqueta
        FOREIGN KEY (etiqueta_id) REFERENCES etiquetas_dieteticas(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- TANDA A (continuación) — Pedidos y líneas de pedido
-- Se crean ahora porque dependen de platos
-- =========================================================================

-- Cada vez que el cliente pulsa "Enviar pedido"
CREATE TABLE pedidos (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sesion_mesa_id  INT UNSIGNED NOT NULL,
    creado_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    estado          ENUM('PENDIENTE','EN_PREPARACION','LISTO','SERVIDO','ANULADO')
                        NOT NULL DEFAULT 'PENDIENTE',
    PRIMARY KEY (id),
    KEY idx_pedidos_sesion (sesion_mesa_id),
    KEY idx_pedidos_estado (estado),
    KEY idx_pedidos_creado (creado_en),
    CONSTRAINT fk_pedidos_sesion
        FOREIGN KEY (sesion_mesa_id) REFERENCES sesiones_mesa(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cada plato concreto dentro de un pedido
CREATE TABLE lineas_pedido (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id       INT UNSIGNED NOT NULL,
    plato_id        INT UNSIGNED NOT NULL,
    cantidad        INT UNSIGNED NOT NULL,
    precio_unitario DECIMAL(8,2) NOT NULL,
    nota            VARCHAR(255) NULL,
    estado          ENUM('PENDIENTE','EN_PREPARACION','LISTO','SERVIDO','ANULADO')
                        NOT NULL DEFAULT 'PENDIENTE',
    PRIMARY KEY (id),
    KEY idx_lineas_pedido (pedido_id),
    KEY idx_lineas_plato (plato_id),
    KEY idx_lineas_estado (estado),
    CONSTRAINT fk_lineas_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_lineas_plato
        FOREIGN KEY (plato_id) REFERENCES platos(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_lineas_cantidad CHECK (cantidad > 0),
    CONSTRAINT chk_lineas_precio CHECK (precio_unitario >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- TANDA C — Funciones extra
-- =========================================================================

-- Valoraciones de estrellas por línea de pedido servida
CREATE TABLE valoraciones (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    linea_pedido_id INT UNSIGNED NOT NULL,
    puntuacion      TINYINT UNSIGNED NOT NULL,
    comentario      VARCHAR(255) NULL,
    creada_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_valoraciones_linea (linea_pedido_id),
    KEY idx_valoraciones_puntuacion (puntuacion),
    CONSTRAINT fk_valoraciones_linea
        FOREIGN KEY (linea_pedido_id) REFERENCES lineas_pedido(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_valoraciones_puntuacion
        CHECK (puntuacion BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alertas de "llamar al camarero"
CREATE TABLE alertas_camarero (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sesion_mesa_id  INT UNSIGNED NOT NULL,
    motivo          ENUM('GENERICA','CUENTA','AYUDA')
                        NOT NULL DEFAULT 'GENERICA',
    creada_en       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atendida_en     TIMESTAMP NULL,
    atendida_por    INT UNSIGNED NULL,
    PRIMARY KEY (id),
    KEY idx_alertas_sesion (sesion_mesa_id),
    KEY idx_alertas_atendida (atendida_en),
    KEY idx_alertas_motivo (motivo),
    CONSTRAINT fk_alertas_sesion
        FOREIGN KEY (sesion_mesa_id) REFERENCES sesiones_mesa(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_alertas_usuario
        FOREIGN KEY (atendida_por) REFERENCES usuarios(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Registro de auditoría de logins del personal
CREATE TABLE sesiones_app (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED NOT NULL,
    ip              VARCHAR(45) NOT NULL,
    user_agent      VARCHAR(255) NULL,
    iniciada_en     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cerrada_en      TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY idx_sesapp_usuario (usuario_id),
    KEY idx_sesapp_iniciada (iniciada_en),
    CONSTRAINT fk_sesapp_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================================
-- CARGA DE CATÁLOGOS MAESTROS
-- =========================================================================

-- 14 alérgenos de declaración obligatoria (Reglamento UE 1169/2011)
INSERT INTO alergenos (codigo, nombre) VALUES
    ('gluten',      'Cereales con gluten'),
    ('crustaceos',  'Crustáceos'),
    ('huevos',      'Huevos'),
    ('pescado',     'Pescado'),
    ('cacahuetes',  'Cacahuetes'),
    ('soja',        'Soja'),
    ('lacteos',     'Lácteos'),
    ('frutos_casc', 'Frutos de cáscara'),
    ('apio',        'Apio'),
    ('mostaza',     'Mostaza'),
    ('sesamo',      'Sésamo'),
    ('sulfitos',    'Sulfitos'),
    ('altramuces',  'Altramuces'),
    ('moluscos',    'Moluscos');

-- Etiquetas dietéticas
INSERT INTO etiquetas_dieteticas (codigo, nombre) VALUES
    ('vegetariano',  'Vegetariano'),
    ('vegano',       'Vegano'),
    ('sin_gluten',   'Sin gluten'),
    ('sin_lactosa',  'Sin lactosa');

-- =========================================================================
-- FIN DEL SCRIPT
-- =========================================================================
