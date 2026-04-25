# ESTADO_PROYECTO — Punto de retomada

> Este documento resume **dónde está el proyecto** en este momento exacto, qué se ha completado y **qué toca hacer en la próxima sesión**. Está pensado para retomar el trabajo en un chat nuevo de Claude sin perder contexto.

---

## Resumen ejecutivo

- **Proyecto:** Sistema web de pedidos para restaurantes (Proyecto Final ASIR de Nicolás Barrera Quintana).
- **Stack confirmado:** Apache 2.4 + PHP 8.2 + MariaDB 10.11 sobre Ubuntu 25 nativo (sin VMs ni Docker), MVC manual en PHP, sin frameworks.
- **Repositorio GitHub:** `https://github.com/Mamb72/restaurante` (público).
- **Carpeta local de trabajo:** `~/Proyectos/restaurante`, enlazada simbólicamente desde `/var/www/restaurante`.
- **Acceso local:** `http://restaurante.local`.
- **Usuario MariaDB de la app:** `rest_app`@`localhost` con permisos mínimos (SELECT/INSERT/UPDATE/DELETE).

---

## Bloques completados

### ✅ Bloque 1 — Alcance Funcional (v1.1)
Definidas todas las funcionalidades por rol (Cliente/Cocina/Admin), reglas de negocio, criterios de aceptación. Se añadieron 5 funcionalidades de valor añadido: vista "Mi mesa", filtro dietético, platos destacados, llamar al camarero, valoraciones.
**Documento:** `docs/01_Alcance_Funcional.md`

### ✅ Bloque 2 — Arquitectura Técnica (v2.0)
Stack LAMP nativo en Ubuntu 25, MVC manual, polling AJAX para tiempo real, seguridad estándar (PDO, bcrypt, sesiones, CSRF, UFW), backups con cron + mysqldump.
**Documento:** `docs/02_Arquitectura_Tecnica.md`

### ✅ Bloque 3 — Modelo de Datos (v1.0)
13 tablas validadas en MariaDB real: usuarios, mesas, sesiones_mesa, pedidos, lineas_pedido, categorias, platos, alergenos, etiquetas_dieteticas, plato_alergeno, plato_etiqueta, valoraciones, alertas_camarero, sesiones_app.
**Documento:** `docs/03_Modelo_Datos.md` · **Script SQL:** `db/schema.sql`

### ✅ Bloque 4 — Estructura de Archivos (v1.0)
Estructura completa de carpetas, convenciones de nombres, tabla de rutas, scripts de infraestructura.
**Documento:** `docs/04_Estructura_Archivos.md`

### ✅ Bloque 5 — Setup del entorno
Apache + PHP + MariaDB instalados y asegurados, Git configurado, repositorio GitHub creado, primer commit hecho, BD cargada con `schema.sql` (14 alérgenos + 4 etiquetas), usuario `rest_app` con permisos mínimos, VirtualHost de Apache funcionando en `restaurante.local`, MariaDB escuchando solo en `127.0.0.1`.

### ✅ Bloque 6.1 — Configuración y conexión a BD
Archivos creados:
- `config/config.php` — constantes globales, modo debug, zona horaria, cabeceras de seguridad.
- `config/db.php` — credenciales (NO versionado, en `.gitignore`).
- `app/core/Database.php` — clase Singleton para PDO con prepared statements reales y modo excepción.

### ✅ Bloque 6.2 — Router y Controller base
Archivos creados:
- `public/.htaccess` — reescritura de URLs hacia `index.php`.
- `app/core/Router.php` — registro de rutas GET/POST, soporte de parámetros dinámicos `{token}`, gestión de 404.
- `app/core/Controller.php` — clase abstracta base con métodos `vista()`, `redirigir()`, `json()`.
- `app/controllers/ClienteController.php` — primer controlador con método `inicio()`.
- `app/views/cliente/bienvenida.php` — primera vista renderizada por el MVC.
- `public/index.php` — punto de entrada que carga el router y resuelve la petición.

**Verificación:** `http://restaurante.local/` muestra la bienvenida; URLs inventadas devuelven 404. ✓

### ✅ Bloque 6.3 — Modelos y carta pública
Archivos creados:
- `app/core/Model.php` — clase abstracta base con conexión PDO compartida.
- `app/models/Categoria.php` — modelo con `obtenerTodasActivas()`.
- `app/models/Plato.php` — modelo con `obtenerPorCategoria()`, `obtenerDestacados()`, `obtenerPorId()` y métodos privados para alérgenos y etiquetas.
- `app/controllers/ClienteController.php` — ampliado con método `carta()`.
- `app/views/cliente/carta.php` — primera vista con datos reales: categorías, destacados, badges de alérgenos y etiquetas dietéticas.
- `db/datos_demo.sql` — datos de demostración (4 categorías, 11 platos con sus relaciones).
- Nueva ruta: `GET /carta` registrada en `public/index.php`.

**Verificación:** `http://restaurante.local/carta` muestra la carta agrupada por categorías con destacados, badges de alérgenos (rojos) y etiquetas dietéticas (verdes). ✓

---

## Cosas que hay que hacer al retomar

**Antes de empezar a programar nada nuevo:**

1. Verificar que el entorno sigue funcionando: `http://restaurante.local/` debe mostrar la página de bienvenida.
2. Si se ha apagado el equipo: comprobar que Apache y MariaDB están activos (`sudo systemctl status apache2 mariadb`).

---
## Próxima fase: Bloque 6.4 — Sesiones de mesa y primer pedido

Lo que toca implementar:

1. **Modelo `Mesa`** (`app/models/Mesa.php`) con método `obtenerPorToken(string $token)`.
2. **Modelo `SesionMesa`** (`app/models/SesionMesa.php`) con métodos para abrir, recuperar y cerrar sesiones de mesa.
3. **Modelo `Pedido`** y **`LineaPedido`** con la lógica básica de carrito.
4. **Controlador `MesaController`** con:
   - `entrar(array $params)` — recibe `{token}`, abre/recupera sesión, redirige a carta de mesa.
   - `miMesa(array $params)` — muestra el pedido en curso.
5. **Vista `cliente/carta_mesa.php`** — versión de la carta con botones "Añadir al pedido".
6. **Vista `cliente/mi_mesa.php`** — pedido en curso con líneas, total y botón confirmar.
7. **Endpoints AJAX** para añadir/quitar líneas sin recargar página.
8. **Nuevas rutas:**
   - `GET /mesa/{token}` → `MesaController@entrar`
   - `GET /mi-mesa` → `MesaController@miMesa`
   - `POST /pedido/anadir` → endpoint AJAX
   - `POST /pedido/confirmar` → cerrar pedido, pasar a cocina

**Resultado esperado al final de la fase 6.4:** un cliente entra a `http://restaurante.local/mesa/MESA-001-TOKEN`, ve la carta con botones de añadir, mete platos, ve su pedido en "Mi mesa" y lo confirma.
---

## Convenciones del proyecto (recordatorio)

- **PHP:** `declare(strict_types=1);` en cada archivo. Clases `final` salvo bases abstractas. Métodos y propiedades tipados. PSR-1/12 style con sintaxis moderna PHP 8.2+.
- **Nombres:** Clases PascalCase, métodos camelCase, columnas BD snake_case en español.
- **SQL:** PDO con prepared statements siempre. Nunca concatenar variables a SQL.
- **Mensajes Git:** Imperativo en español. Ejemplos: *"Añade modelo Plato con búsqueda por categoría"*, *"Implementa vista de carta pública"*.
- **Commit + push** después de cada pieza completa.

---

## Decisiones técnicas de referencia (no recambiar sin motivo)

- Sin frameworks (sin Laravel, sin Symfony, sin Composer todavía).
- Sin VMs ni Docker.
- MariaDB local solo en 127.0.0.1.
- Polling AJAX para tiempo real (no WebSockets).
- Subidas de imágenes en `public/assets/uploads/platos/` con `.htaccess` que bloquea ejecución de PHP.
- Soft delete con campo `activo` en tablas principales (no borrado físico).
- Patrón MVC manual + Router + Singleton para BD.

---

## Si surgen dudas en la próxima sesión

Los **4 documentos de `docs/`** son la fuente autoritativa. Si algo entra en conflicto con ellos, prevalecen los documentos. Cualquier cambio mayor debe registrarse aumentando la versión en su tabla "Historial de cambios".

---
*Actualizado al cierre del Bloque 6.3 para facilitar la retomada del proyecto en un chat nuevo.*
