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
- **Usuarios demo del personal:**
  - `admin@restaurante.local` / `admin1234` (rol admin)
  - `cocina@restaurante.local` / `cocina1234` (rol cocina)

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
- `config/config.php` — constantes globales, modo debug, zona horaria, cabeceras de seguridad.
- `config/db.php` — credenciales (NO versionado, en `.gitignore`).
- `app/core/Database.php` — clase Singleton para PDO con prepared statements reales y modo excepción.

### ✅ Bloque 6.2 — Router y Controller base
- `public/.htaccess` — reescritura de URLs hacia `index.php`.
- `app/core/Router.php` — registro de rutas GET/POST, soporte de parámetros dinámicos `{token}`, gestión de 404.
- `app/core/Controller.php` — clase abstracta base con `vista()`, `redirigir()`, `json()`.
- `app/controllers/ClienteController.php` — primer controlador con método `inicio()`.
- `app/views/cliente/bienvenida.php` — primera vista renderizada por el MVC.
- `public/index.php` — punto de entrada que carga el router y resuelve la petición.

**Verificación:** `http://restaurante.local/` muestra la bienvenida; URLs inventadas devuelven 404. ✓

### ✅ Bloque 6.3 — Modelos y carta pública
- `app/core/Model.php` — clase abstracta base con conexión PDO compartida.
- `app/models/Categoria.php` — modelo con `obtenerTodasActivas()`.
- `app/models/Plato.php` — modelo con `obtenerPorCategoria()`, `obtenerDestacados()`, `obtenerPorId()` y métodos privados para alérgenos y etiquetas.
- `app/controllers/ClienteController.php` — ampliado con método `carta()`.
- `app/views/cliente/carta.php` — primera vista con datos reales: categorías, destacados, badges de alérgenos y etiquetas dietéticas.
- `db/datos_demo.sql` — datos de demostración (4 categorías, 11 platos con sus relaciones).
- Nueva ruta: `GET /carta` registrada en `public/index.php`.

**Verificación:** `http://restaurante.local/carta` muestra la carta agrupada por categorías. ✓

### ✅ Bloque 6.4 — Sesiones de mesa y primer pedido del cliente
- `app/models/Mesa.php` — modelo con `obtenerPorToken()` y `obtenerPorId()`.
- `app/models/SesionMesa.php` — modelo con `obtenerActivaPorMesa()`, `abrir()`, `cerrar()`, `pedirCuenta()`, y patrón get-or-create `obtenerOAbrir()`.
- `app/models/Pedido.php` — modelo con `crear()`, `obtenerPorSesion()`, `cambiarEstado()`, `obtenerActivosParaCocina()`.
- `app/models/LineaPedido.php` — modelo con `crear()`, `obtenerPorPedido()`, `obtenerPorSesion()`, `cambiarEstado()`.
- `app/controllers/MesaController.php` — métodos `entrar()` (entrada por QR), `cartaMesa()`, `miMesa()`.
- `app/controllers/PedidoController.php` — endpoints AJAX `anadir()`, `quitar()`, `confirmar()` con validación CSRF y transacción atómica.
- `app/views/cliente/carta_mesa.php` — carta con botones AJAX, contador en tiempo real y toast.
- `app/views/cliente/mi_mesa.php` — pedido en curso + historial + total + botón confirmar.

**Verificación:** Un cliente entra por QR, navega la carta, gestiona carrito y confirma pedidos. Las líneas quedan en `lineas_pedido` con `estado = PENDIENTE`. ✓

### ✅ Bloque 6.5 — Autenticación y panel de cocina con tiempo real

**6.5.a — Autenticación del personal:**
- `app/models/Usuario.php` — modelo que extiende `Model`. Métodos `buscarPorEmail()`, `buscarPorId()`, `verificarCredenciales()` con `password_verify()` (bcrypt) y mitigación de timing attacks.
- `app/controllers/AuthController.php` — `mostrarLogin()`, `procesarLogin()`, `logout()`. Sesión PHP con cookies HttpOnly + SameSite=Lax, regeneración de id en login, CSRF en formulario.
- `app/views/auth/login.php` — formulario de acceso del personal.
- `db/datos_usuarios.sql` — usuarios demo con hashes bcrypt cost 12.

**6.5.b — Middleware de auth + panel de cocina con polling:**
- `app/core/Auth.php` — helper estático con `iniciarSesion()`, `estaAutenticado()`, `usuarioActual()`, `exigirRol()`, `tokenCsrf()`, `verificarCsrf()`. Centraliza la lógica de control de acceso para reutilizar en cualquier controlador protegido.
- `app/controllers/CocinaController.php` — métodos `panel()` (vista HTML), `pedidosActivosJson()` (endpoint de polling), `actualizarEstadoLinea()` (cambio de estado vía AJAX). Incluye `recalcularEstadoPedido()` que deriva el estado del pedido a partir del estado de sus líneas.
- `app/views/cocina/panel.php` — panel con tarjetas por mesa, polling cada 4 s, indicador de conexión, toast + beep WebAudio al entrar pedido nuevo, animación de entrada de tarjetas, botones de cambio de estado funcionando vía AJAX.
- Rutas nuevas: `GET /login`, `POST /login`, `GET /logout`, `GET /cocina`, `GET /cocina/pedidos.json`, `POST /cocina/linea/estado`.

**6.5.c — Polling del lado cliente:**
- `app/controllers/MesaController.php` — método nuevo `estadoSesionJson()` para devolver los estados de las líneas de la sesión activa de la mesa.
- `app/views/cliente/mi_mesa.php` — bloque JS de polling cada 5 s que detecta cambios de estado y actualiza badges sin recargar. Toast verde + flash de fila cuando un plato pasa a `LISTO`.
- Ruta nueva: `GET /mi-mesa/estado.json`.

**Verificación end-to-end:** En dos navegadores distintos (cliente + cocina), confirmar un pedido en el cliente lo hace aparecer en el panel de cocina con toast y beep en menos de 4 s; cambiar el estado en cocina (Preparar → Listo → Servido) actualiza los badges del cliente en menos de 5 s, con toast verde y flash de fila cuando llega a "Listo". ✓

---

## Cosas que hay que hacer al retomar

**Antes de empezar a programar nada nuevo:**

1. Verificar servicios activos: `sudo systemctl status apache2 mariadb`
2. Verificar la app pública: `http://restaurante.local/` muestra la bienvenida.
3. Verificar el flujo cliente: entrar en `http://restaurante.local/mesa/a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6` muestra la carta y se puede pedir.
4. Verificar el panel cocina: login en `http://restaurante.local/login` con `cocina@restaurante.local` / `cocina1234` lleva a `/cocina` y muestra los pedidos activos.

---

## Próxima fase: Bloque 7 — Panel de administrador

Lo que toca implementar, en orden recomendado:

### 7.1 — Layout base del admin y dashboard mínimo
- Vista layout `app/views/admin/_layout.php` con sidebar de navegación y reutilización en todas las páginas del admin.
- `AdminController::dashboard()` con resumen sencillo: nº de pedidos activos, nº de mesas ocupadas, total facturado del día.
- Ruta `GET /admin` protegida por `Auth::exigirRol(['admin'])`.

### 7.2 — CRUD de categorías
- Listado, crear, editar, activar/desactivar.
- Validación de no borrar categorías con platos asociados.

### 7.3 — CRUD de platos
- Listado con filtros por categoría y disponibilidad.
- Formulario crear/editar con: nombre, descripción, precio, categoría, alérgenos (multi-checkbox), etiquetas dietéticas (multi-checkbox), destacado, disponible.
- Subida opcional de foto a `public/assets/uploads/platos/` con validación de tipo MIME y tamaño.
- "Marcar agotado" como soft-toggle.

### 7.4 — CRUD de mesas y generación de QR
- Listado con número, capacidad y estado de sesión actual.
- Crear/editar/dar de baja mesa.
- Generación e impresión del QR de cada mesa (URL `http://restaurante.local/mesa/{token}`).
- Considerar usar `endroid/qr-code` solo si es viable sin Composer; alternativa: API externa libre como `qrserver.com` (decisión a tomar al implementar).

### 7.5 — Gestión de usuarios del personal
- CRUD: alta, edición, desactivar.
- Cambio de contraseña con confirmación, hasheada con `password_hash()`.
- Validación de no auto-desactivar el usuario en sesión.

### 7.6 — Operativa diaria
- Listado de todos los pedidos del día con sus estados, mesa y total.
- Marcar como anulado o servido manualmente.
- Listado de alertas de "llamar al camarero" del día (si se implementa esa feature antes).

### 7.7 — Dashboard de estadísticas (pieza clave para la defensa)
- Facturación día / semana / mes.
- Plato más pedido y menos pedido.
- Distribución de pedidos por franja horaria (gráfica con `<canvas>` o SVG manual, sin librerías externas).
- Mesa con más rotación.
- Plato mejor / peor valorado y nota media global.

---

## Convenciones del proyecto (recordatorio)

- **PHP:** `declare(strict_types=1);` en cada archivo. Clases `final` salvo bases abstractas. Métodos y propiedades tipados. PSR-1/12 con sintaxis moderna PHP 8.2+.
- **Nombres:** Clases PascalCase, métodos camelCase, columnas BD snake_case en español.
- **SQL:** PDO con prepared statements siempre. Nunca concatenar variables a SQL.
- **Mensajes Git:** Imperativo en español. Ejemplos: *"Añade panel de cocina con middleware de autenticación por rol"*, *"Implementa polling AJAX y cambio de estado en panel de cocina"*.
- **Commit + push** después de cada pieza completa y defendible.
- **Carga de modelos:** cada controlador hace `require_once` de los modelos que usa. La clase base `Model.php` se carga globalmente desde `public/index.php`.
- **Autenticación:** `Auth::exigirRol(['rol1','rol2'])` al inicio de cada método de controlador protegido. CSRF con `Auth::tokenCsrf()` y `Auth::verificarCsrf()`.

---

## Estructura de archivos actual (resumen)
~/Proyectos/restaurante/
├── app/
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── ClienteController.php
│   │   ├── CocinaController.php
│   │   ├── MesaController.php
│   │   └── PedidoController.php
│   ├── core/
│   │   ├── Auth.php
│   │   ├── Controller.php
│   │   ├── Database.php
│   │   ├── Model.php
│   │   └── Router.php
│   ├── models/
│   │   ├── Categoria.php
│   │   ├── LineaPedido.php
│   │   ├── Mesa.php
│   │   ├── Pedido.php
│   │   ├── Plato.php
│   │   ├── SesionMesa.php
│   │   └── Usuario.php
│   └── views/
│       ├── auth/login.php
│       ├── cliente/{bienvenida,carta,carta_mesa,mi_mesa}.php
│       ├── cocina/panel.php
│       └── errores/mesa_no_valida.php
├── config/
│   ├── config.php
│   └── db.php (no versionado)
├── db/
│   ├── schema.sql
│   ├── datos_demo.sql
│   └── datos_usuarios.sql
├── docs/
│   ├── 01_Alcance_Funcional.md
│   ├── 02_Arquitectura_Tecnica.md
│   ├── 03_Modelo_Datos.md
│   └── 04_Estructura_Archivos.md
├── public/
│   ├── .htaccess
│   └── index.php
└── ESTADO_PROYECTO.md (este archivo)
