# 03 — Modelo de Datos

**Proyecto:** Sistema web integral para la digitalización de cartas y pedidos en establecimientos de restauración
**Autor:** Nicolás Barrera Quintana
**Ciclo:** 2º ASIR — Administración de Sistemas Informáticos en Red
**Documento:** 03 · Modelo de Datos
**Estado:** Cerrado y validado
**Versión:** 1.0

---

## 1. Propósito del documento

Este documento define el **modelo relacional de la base de datos** del sistema: las tablas, sus campos, tipos, restricciones, relaciones y decisiones de diseño. Es la referencia que alimenta directamente el script `schema.sql` y los modelos PHP de la aplicación.

Se acompaña de las **justificaciones de diseño** necesarias para defender cada decisión ante el tribunal.

---

## 2. Decisiones transversales

Estas decisiones afectan a todas las tablas del esquema:

- **Motor de almacenamiento:** InnoDB (soporte de claves foráneas, transacciones y bloqueo a nivel de fila).
- **Charset:** `utf8mb4` con collation `utf8mb4_unicode_ci` (soporte completo de Unicode, incluidos emojis).
- **Zona horaria:** todos los `TIMESTAMP` se almacenan en UTC. La conversión a zona horaria local se realiza en la capa de presentación (PHP).
- **Claves primarias:** `INT UNSIGNED AUTO_INCREMENT` en todas las tablas principales, salvo las puente que usan clave compuesta.
- **Tipos numéricos monetarios:** `DECIMAL(8,2)` — nunca `FLOAT` ni `DOUBLE`, para evitar errores de redondeo.
- **Nomenclatura:** nombres de tablas y columnas en español, en minúsculas, con guion bajo como separador (`sesiones_mesa`, `lineas_pedido`).
- **Borrado lógico (*soft delete*):** se usa el campo `activo` en las tablas principales en lugar de borrar físicamente, para preservar la integridad histórica.
- **Integridad referencial explícita:** todas las claves foráneas declaran su política `ON DELETE` de forma expresa.
- **Defensa en profundidad:** las reglas de negocio críticas se protegen tanto desde el código PHP como desde el esquema de la BD (`CHECK`, `UNIQUE`, `NOT NULL`, FKs).

---

## 3. Diagrama entidad-relación

```
                            ┌──────────────┐
                            │  categorias  │
                            └──────┬───────┘
                                   │ 1
                                   │
                                   │ N
┌─────────────┐              ┌─────▼──────┐           ┌──────────────┐
│  alergenos  │◄─N──plato_──►│   platos   │◄─N──plato_►│  etiquetas_  │
└─────────────┘     alergeno └──────┬─────┘   etiqueta │  dieteticas  │
                                   │ 1              ▲  └──────────────┘
                                   │                │
                                   │ N              │
┌──────────────┐    ┌────────────┐ │                │
│    mesas     │    │  usuarios  │ │                │
└──────┬───────┘    └──────┬─────┘ │                │
       │ 1                 │ 1     │                │
       │                   │       │                │
       │ N                 │ N     │                │
       ▼              ┌────▼───────┴──┐             │
┌──────────────┐      │  sesiones_app │             │
│ sesiones_    │      └───────────────┘             │
│ mesa         │                                    │
└──────┬───────┘                                    │
       │ 1                                          │
       ├─────────────────┐                          │
       │ N               │ N                        │
       ▼                 ▼                          │
┌──────────────┐  ┌────────────────┐                │
│   pedidos    │  │alertas_camarero│                │
└──────┬───────┘  └────────────────┘                │
       │ 1                                          │
       │                                            │
       │ N                                          │
       ▼                                            │
┌──────────────┐                                    │
│lineas_pedido │─N──────────────────────────────────┘
└──────┬───────┘    (referencia a platos)
       │ 1
       │
       │ 0..1
       ▼
┌──────────────┐
│ valoraciones │
└──────────────┘
```

---

## 4. Tanda A — Núcleo operativo

### 4.1 Tabla `usuarios`

Personal del restaurante con acceso al sistema (administradores y cocina). Los clientes/comensales **no** tienen registro en esta tabla.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | Identificador interno. |
| `nombre` | `VARCHAR(100)` | NOT NULL | Nombre mostrado en paneles. |
| `email` | `VARCHAR(150)` | NOT NULL, UNIQUE | Usado como login. |
| `hash_password` | `VARCHAR(255)` | NOT NULL | Hash bcrypt (`password_hash()` de PHP). |
| `rol` | `ENUM('admin','cocina')` | NOT NULL | Controla permisos de acceso. |
| `activo` | `BOOLEAN` | NOT NULL, DEFAULT TRUE | Permite desactivar sin eliminar. |
| `creado_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | Auditoría. |

**Índices:** `UNIQUE(email)`.

**Justificación de diseño:** se usa `activo` en lugar de borrado físico para no romper la integridad referencial con `alertas_camarero.atendida_por` y con `sesiones_app.usuario_id`, preservando así el histórico de auditoría.

---

### 4.2 Tabla `mesas`

Mesas físicas del restaurante. Cada una tiene un token único que se incrusta en su código QR.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `numero` | `INT UNSIGNED` | NOT NULL, UNIQUE | Número visible (1, 2, 3…). |
| `token_qr` | `CHAR(32)` | NOT NULL, UNIQUE | Cadena aleatoria incrustada en la URL del QR. |
| `activa` | `BOOLEAN` | NOT NULL, DEFAULT TRUE | Si el administrador retira una mesa. |
| `creada_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |

**Índices:** `UNIQUE(numero)`, `UNIQUE(token_qr)`.

**Justificación de diseño:** el uso de un `token_qr` aleatorio en lugar del número de mesa impide que un usuario pueda hacer pedidos a cualquier mesa manipulando la URL. Solo quien esté físicamente presente ante el QR puede acceder a esa mesa.

---

### 4.3 Tabla `sesiones_mesa`

Pieza central del modelo operativo. Representa el lapso de tiempo en que un grupo de comensales está usando una mesa, desde que escanean el QR hasta que piden la cuenta. Todos los pedidos de una misma comida cuelgan de la misma sesión.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `mesa_id` | `INT UNSIGNED` | NOT NULL, FK → `mesas.id` ON DELETE RESTRICT | |
| `abierta_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `cerrada_en` | `TIMESTAMP` | NULL | NULL mientras la sesión sigue activa. |
| `estado` | `ENUM('ABIERTA','PEDIDA_CUENTA','CERRADA')` | NOT NULL, DEFAULT 'ABIERTA' | Máquina de estados de la sesión. |

**Índices:** `INDEX(mesa_id)`, `INDEX(estado)`.

**Máquina de estados:** `ABIERTA → PEDIDA_CUENTA → CERRADA`.

**Regla de negocio crítica:** una misma mesa no puede tener dos sesiones simultáneamente en estado `ABIERTA` o `PEDIDA_CUENTA`. Esta regla se valida desde el código PHP antes de insertar una nueva sesión.

---

### 4.4 Tabla `pedidos`

Cada vez que el cliente pulsa "Enviar pedido", se genera una fila en esta tabla. Una sesión de mesa puede contener varios pedidos (bebidas, luego comida, luego postre).

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `sesion_mesa_id` | `INT UNSIGNED` | NOT NULL, FK → `sesiones_mesa.id` ON DELETE CASCADE | |
| `creado_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `estado` | `ENUM('PENDIENTE','EN_PREPARACION','LISTO','SERVIDO','ANULADO')` | NOT NULL, DEFAULT 'PENDIENTE' | Estado general del pedido. |

**Índices:** `INDEX(sesion_mesa_id)`, `INDEX(estado)`, `INDEX(creado_en)`.

**Justificación de diseño:** el estado general del pedido se deriva conceptualmente del estado de sus líneas, pero se guarda explícitamente por eficiencia: evita recalcularlo en cada consulta al panel de cocina. La sincronización la gestiona el controlador de pedidos.

---

### 4.5 Tabla `lineas_pedido`

Cada plato concreto dentro de un pedido, con su cantidad, precio y nota.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `pedido_id` | `INT UNSIGNED` | NOT NULL, FK → `pedidos.id` ON DELETE CASCADE | |
| `plato_id` | `INT UNSIGNED` | NOT NULL, FK → `platos.id` ON DELETE RESTRICT | |
| `cantidad` | `INT UNSIGNED` | NOT NULL, CHECK (`cantidad > 0`) | |
| `precio_unitario` | `DECIMAL(8,2)` | NOT NULL, CHECK (`precio_unitario >= 0`) | Copia del precio del plato en el momento del pedido. |
| `nota` | `VARCHAR(255)` | NULL | "Sin cebolla", "poco hecho", etc. |
| `estado` | `ENUM('PENDIENTE','EN_PREPARACION','LISTO','SERVIDO','ANULADO')` | NOT NULL, DEFAULT 'PENDIENTE' | Estado del plato individual. |

**Índices:** `INDEX(pedido_id)`, `INDEX(plato_id)`, `INDEX(estado)`.

**Justificación de diseño:**

- Se usa `DECIMAL(8,2)` para el precio, nunca `FLOAT`, porque los números en coma flotante introducen errores de redondeo incompatibles con el manejo de dinero.
- Se copia el `precio_unitario` en la línea en lugar de referenciar el precio actual del plato. De lo contrario, cualquier cambio de precio posterior alteraría retroactivamente el importe de tickets ya emitidos, lo que sería inaceptable contable y legalmente.
- La FK a `platos` usa `ON DELETE RESTRICT` para impedir el borrado físico de un plato que aparezca en algún pedido histórico. Los platos retirados se marcan con `activo = FALSE` (soft delete).

---

## 5. Tanda B — La carta

### 5.1 Tabla `categorias`

Categorías de la carta: entrantes, principales, bebidas, postres, etc.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `nombre` | `VARCHAR(60)` | NOT NULL, UNIQUE | |
| `orden` | `INT UNSIGNED` | NOT NULL, DEFAULT 0 | Posición en la carta del cliente. |
| `activa` | `BOOLEAN` | NOT NULL, DEFAULT TRUE | Permite ocultar sin borrar. |

**Índices:** `UNIQUE(nombre)`, `INDEX(orden)`.

---

### 5.2 Tabla `platos`

Tabla principal de la carta.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `categoria_id` | `INT UNSIGNED` | NOT NULL, FK → `categorias.id` ON DELETE RESTRICT | |
| `nombre` | `VARCHAR(120)` | NOT NULL | |
| `descripcion` | `TEXT` | NULL | |
| `precio` | `DECIMAL(8,2)` | NOT NULL, CHECK (`precio >= 0`) | |
| `ruta_foto` | `VARCHAR(255)` | NULL | Ruta relativa a la imagen. |
| `disponible` | `BOOLEAN` | NOT NULL, DEFAULT TRUE | FALSE = agotado temporalmente. |
| `destacado` | `BOOLEAN` | NOT NULL, DEFAULT FALSE | TRUE = aparece en "Plato del día". |
| `destacado_hasta` | `DATE` | NULL | Fecha en que deja de estar destacado. |
| `orden` | `INT UNSIGNED` | NOT NULL, DEFAULT 0 | Orden dentro de su categoría. |
| `activo` | `BOOLEAN` | NOT NULL, DEFAULT TRUE | FALSE = retirado del menú (soft delete). |
| `creado_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `actualizado_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP | |

**Índices:** `INDEX(categoria_id)`, `INDEX(disponible)`, `INDEX(destacado, destacado_hasta)`, `INDEX(activo)`.

**Justificación de diseño:**

- Separación entre `disponible` (agotado temporalmente, se restaura al día siguiente) y `activo` (retirado del menú, soft delete). Son dos conceptos distintos que conviene modelar por separado.
- El par `destacado` + `destacado_hasta` permite fijar platos del día con expiración automática: si `destacado_hasta` ha pasado, el plato deja de aparecer en la sección destacada sin intervención manual.
- No se almacena el precio en coma flotante por los motivos ya expuestos.

---

### 5.3 Tabla `alergenos`

Catálogo maestro de los 14 alérgenos de declaración obligatoria según el Reglamento UE 1169/2011.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `codigo` | `VARCHAR(30)` | NOT NULL, UNIQUE | Identificador técnico: `gluten`, `lactosa`, etc. |
| `nombre` | `VARCHAR(60)` | NOT NULL | Nombre visible. |
| `icono` | `VARCHAR(60)` | NULL | Ruta a icono SVG. |

**Índices:** `UNIQUE(codigo)`.

**Justificación de diseño:** los 14 alérgenos se insertan inicialmente en `schema.sql`. Son datos maestros establecidos por normativa europea; no se contempla su edición rutinaria desde la aplicación.

---

### 5.4 Tabla `etiquetas_dieteticas`

Catálogo de etiquetas para los filtros dietéticos del cliente: vegetariano, vegano, sin gluten, sin lactosa.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `codigo` | `VARCHAR(30)` | NOT NULL, UNIQUE | |
| `nombre` | `VARCHAR(60)` | NOT NULL | |
| `icono` | `VARCHAR(60)` | NULL | |

**Índices:** `UNIQUE(codigo)`.

**Justificación de diseño:** se modela como tabla en lugar de un `ENUM` en `platos` para facilitar la ampliación futura (añadir "halal", "kosher", etc. sin necesidad de `ALTER TABLE`).

---

### 5.5 Tabla `plato_alergeno` (tabla puente)

Relación muchos-a-muchos entre platos y alérgenos.

| Campo | Tipo | Restricciones |
|---|---|---|
| `plato_id` | `INT UNSIGNED` | NOT NULL, FK → `platos.id` ON DELETE CASCADE |
| `alergeno_id` | `INT UNSIGNED` | NOT NULL, FK → `alergenos.id` ON DELETE RESTRICT |

**Clave primaria compuesta:** `PRIMARY KEY (plato_id, alergeno_id)`.
**Índices adicionales:** `INDEX(alergeno_id)`.

**Justificación de diseño:** la PK compuesta impide por construcción duplicar el mismo alérgeno en el mismo plato. `ON DELETE RESTRICT` sobre `alergeno_id` protege el catálogo maestro.

---

### 5.6 Tabla `plato_etiqueta` (tabla puente)

Relación muchos-a-muchos entre platos y etiquetas dietéticas.

| Campo | Tipo | Restricciones |
|---|---|---|
| `plato_id` | `INT UNSIGNED` | NOT NULL, FK → `platos.id` ON DELETE CASCADE |
| `etiqueta_id` | `INT UNSIGNED` | NOT NULL, FK → `etiquetas_dieteticas.id` ON DELETE RESTRICT |

**Clave primaria compuesta:** `PRIMARY KEY (plato_id, etiqueta_id)`.
**Índices adicionales:** `INDEX(etiqueta_id)`.

---

## 6. Tanda C — Funciones extra

### 6.1 Tabla `valoraciones`

Valoración de estrellas que el cliente asigna a un plato una vez servido.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `linea_pedido_id` | `INT UNSIGNED` | NOT NULL, UNIQUE, FK → `lineas_pedido.id` ON DELETE CASCADE | |
| `puntuacion` | `TINYINT UNSIGNED` | NOT NULL, CHECK (`puntuacion BETWEEN 1 AND 5`) | Estrellas de 1 a 5. |
| `comentario` | `VARCHAR(255)` | NULL | Campo reservado para ampliación futura. |
| `creada_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |

**Índices:** `UNIQUE(linea_pedido_id)`, `INDEX(puntuacion)`.

**Justificación de diseño:**

- `UNIQUE(linea_pedido_id)` hace cumplir a nivel de BD la regla de que cada línea servida se valora **como máximo una vez**, sin depender exclusivamente del código PHP (defensa en profundidad).
- `TINYINT UNSIGNED` (1 byte) es el tipo más eficiente para un valor entre 1 y 5; `INT` sería un desperdicio.
- La restricción `CHECK` impide físicamente almacenar puntuaciones fuera de rango.
- El campo `comentario` queda reservado con coste cero: permite añadir reseñas textuales en el futuro sin migración de esquema.

---

### 6.2 Tabla `alertas_camarero`

Registro de las peticiones de atención que el cliente envía desde su mesa.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `sesion_mesa_id` | `INT UNSIGNED` | NOT NULL, FK → `sesiones_mesa.id` ON DELETE CASCADE | |
| `motivo` | `ENUM('GENERICA','CUENTA','AYUDA')` | NOT NULL, DEFAULT 'GENERICA' | |
| `creada_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `atendida_en` | `TIMESTAMP` | NULL | NULL mientras no se haya atendido. |
| `atendida_por` | `INT UNSIGNED` | NULL, FK → `usuarios.id` ON DELETE SET NULL | |

**Índices:** `INDEX(sesion_mesa_id)`, `INDEX(atendida_en)`, `INDEX(motivo)`.

**Justificación de diseño:**

- Se usa el campo `atendida_en` como marcador de estado (NULL = pendiente, con fecha = atendida), en lugar de una columna `estado` adicional. Es un patrón sencillo y eficiente.
- `ON DELETE SET NULL` sobre `atendida_por` preserva el histórico de alertas aunque se elimine el usuario que las atendió.
- `ENUM` para `motivo` permite diferenciar entre una llamada genérica, una petición de cuenta y una petición de ayuda, lo que enriquece el dashboard y la experiencia del personal.

---

### 6.3 Tabla `sesiones_app`

Registro de auditoría de los inicios de sesión del personal del restaurante. **No** confundir con las sesiones PHP de navegador: esta tabla es un historial de accesos para auditoría.

| Campo | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `INT UNSIGNED` | PK, AUTO_INCREMENT | |
| `usuario_id` | `INT UNSIGNED` | NOT NULL, FK → `usuarios.id` ON DELETE CASCADE | |
| `ip` | `VARCHAR(45)` | NOT NULL | Soporta IPv4 e IPv6. |
| `user_agent` | `VARCHAR(255)` | NULL | Cadena del navegador. |
| `iniciada_en` | `TIMESTAMP` | NOT NULL, DEFAULT CURRENT_TIMESTAMP | |
| `cerrada_en` | `TIMESTAMP` | NULL | NULL mientras la sesión sigue activa. |

**Índices:** `INDEX(usuario_id)`, `INDEX(iniciada_en)`.

**Justificación de diseño:** se incluye este registro de auditoría porque la trazabilidad de accesos es una responsabilidad básica en administración de sistemas. El tamaño `VARCHAR(45)` para la IP es el exacto para una dirección IPv6 con notación extendida.

---

## 7. Integridad referencial: resumen de políticas `ON DELETE`

| Relación | Política | Razón |
|---|---|---|
| `pedidos` → `sesiones_mesa` | CASCADE | Los pedidos no tienen sentido sin su sesión. |
| `lineas_pedido` → `pedidos` | CASCADE | Las líneas no tienen sentido sin su pedido. |
| `lineas_pedido` → `platos` | RESTRICT | No se borra un plato con histórico; se usa soft delete. |
| `valoraciones` → `lineas_pedido` | CASCADE | Si la línea se anula por completo, su valoración se elimina. |
| `alertas_camarero` → `sesiones_mesa` | CASCADE | Alerta sin sesión no tiene sentido. |
| `alertas_camarero` → `usuarios` | SET NULL | Preserva el histórico aunque se borre el usuario. |
| `sesiones_app` → `usuarios` | CASCADE | El historial pertenece al usuario. |
| `sesiones_mesa` → `mesas` | RESTRICT | No se borra una mesa con histórico de sesiones. |
| `platos` → `categorias` | RESTRICT | No se borra una categoría con platos asignados. |
| `plato_alergeno` → `platos` | CASCADE | |
| `plato_alergeno` → `alergenos` | RESTRICT | Protege el catálogo maestro. |
| `plato_etiqueta` → `platos` | CASCADE | |
| `plato_etiqueta` → `etiquetas_dieteticas` | RESTRICT | |

---

## 8. Consultas de ejemplo clave

Algunas de las consultas que el sistema ejecutará con frecuencia. Sirven como prueba de que el modelo cubre los casos de uso reales:

**Total gastado por una mesa en su sesión actual (vista "Mi mesa"):**

```sql
SELECT SUM(lp.cantidad * lp.precio_unitario) AS total
FROM lineas_pedido lp
JOIN pedidos p ON p.id = lp.pedido_id
JOIN sesiones_mesa sm ON sm.id = p.sesion_mesa_id
WHERE sm.mesa_id = :mesa_id
  AND sm.estado IN ('ABIERTA','PEDIDA_CUENTA')
  AND lp.estado <> 'ANULADO';
```

**Pedidos pendientes para el panel de cocina:**

```sql
SELECT lp.id, lp.cantidad, lp.nota, lp.estado,
       pl.nombre AS plato, m.numero AS mesa, p.creado_en
FROM lineas_pedido lp
JOIN pedidos p ON p.id = lp.pedido_id
JOIN sesiones_mesa sm ON sm.id = p.sesion_mesa_id
JOIN mesas m ON m.id = sm.mesa_id
JOIN platos pl ON pl.id = lp.plato_id
WHERE lp.estado IN ('PENDIENTE','EN_PREPARACION')
ORDER BY p.creado_en ASC;
```

**Plato mejor valorado:**

```sql
SELECT pl.nombre,
       AVG(v.puntuacion) AS media,
       COUNT(*) AS n_valoraciones
FROM valoraciones v
JOIN lineas_pedido lp ON lp.id = v.linea_pedido_id
JOIN platos pl ON pl.id = lp.plato_id
GROUP BY pl.id, pl.nombre
HAVING n_valoraciones >= 5
ORDER BY media DESC
LIMIT 1;
```

**Alertas pendientes para el panel de cocina:**

```sql
SELECT a.id, a.motivo, a.creada_en, m.numero AS mesa
FROM alertas_camarero a
JOIN sesiones_mesa sm ON sm.id = a.sesion_mesa_id
JOIN mesas m ON m.id = sm.mesa_id
WHERE a.atendida_en IS NULL
ORDER BY a.creada_en ASC;
```

---

## 9. Historial de cambios

| Versión | Fecha | Cambios |
|---|---|---|
| 1.0 | 2026-04-24 | Versión inicial cerrada tras las tandas A, B y C del Bloque 3. |
