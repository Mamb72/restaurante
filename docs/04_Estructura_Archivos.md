# 04 — Estructura de Archivos y Convenciones

**Proyecto:** Sistema web integral para la digitalización de cartas y pedidos en establecimientos de restauración
**Autor:** Nicolás Barrera Quintana
**Ciclo:** 2º ASIR — Administración de Sistemas Informáticos en Red
**Documento:** 04 · Estructura de Archivos y Convenciones
**Estado:** Cerrado y validado
**Versión:** 1.0

---

## 1. Propósito del documento

Este documento define **cómo se organiza físicamente el proyecto en el sistema de archivos**, qué convenciones de nombres se aplican, cómo funciona el sistema de rutas de la aplicación, y qué contienen los archivos de configuración, instalación y documentación.

Junto con los documentos 01, 02 y 03, forma el conjunto de referencias que permite reconstruir el proyecto desde cero con fidelidad al diseño original.

---

## 2. Estructura completa del proyecto

```
restaurante/
├── public/                      ← único directorio accesible desde el navegador
│   ├── index.php                ← punto de entrada y router
│   ├── .htaccess                ← reglas de reescritura de Apache
│   ├── assets/
│   │   ├── css/
│   │   │   ├── cliente.css
│   │   │   ├── cocina.css
│   │   │   └── admin.css
│   │   ├── js/
│   │   │   ├── cliente.js
│   │   │   ├── cocina.js
│   │   │   └── admin.js
│   │   ├── img/                 ← iconos, logo, etc.
│   │   └── uploads/             ← fotos subidas por el administrador
│   │       └── platos/
│   │           ├── .htaccess    ← bloquea ejecución de PHP en esta carpeta
│   │           └── .gitkeep
│   └── favicon.ico
│
├── app/                         ← código de la aplicación (NO accesible vía HTTP)
│   ├── controllers/
│   │   ├── ClienteController.php
│   │   ├── CocinaController.php
│   │   ├── AdminController.php
│   │   └── ApiController.php    ← endpoints JSON para el polling AJAX
│   ├── models/
│   │   ├── Usuario.php
│   │   ├── Mesa.php
│   │   ├── SesionMesa.php
│   │   ├── Pedido.php
│   │   ├── LineaPedido.php
│   │   ├── Categoria.php
│   │   ├── Plato.php
│   │   ├── Alergeno.php
│   │   ├── EtiquetaDietetica.php
│   │   ├── Valoracion.php
│   │   ├── AlertaCamarero.php
│   │   └── SesionApp.php
│   ├── views/
│   │   ├── layout/
│   │   │   ├── header.php
│   │   │   └── footer.php
│   │   ├── cliente/
│   │   │   ├── carta.php
│   │   │   ├── plato_detalle.php
│   │   │   ├── carrito.php
│   │   │   ├── mi_mesa.php
│   │   │   └── ticket.php
│   │   ├── cocina/
│   │   │   ├── login.php
│   │   │   └── panel.php
│   │   └── admin/
│   │       ├── login.php
│   │       ├── dashboard.php
│   │       ├── platos/
│   │       ├── categorias/
│   │       ├── mesas/
│   │       ├── usuarios/
│   │       └── pedidos/
│   ├── core/                    ← núcleo propio tipo "mini framework"
│   │   ├── Router.php
│   │   ├── Controller.php       ← clase base de los controladores
│   │   ├── Model.php            ← clase base de los modelos
│   │   ├── Database.php         ← wrapper de PDO (singleton)
│   │   ├── Request.php          ← envoltorio de $_GET/$_POST/etc.
│   │   ├── Response.php         ← helpers de redirección y JSON
│   │   ├── Session.php          ← gestión de sesiones PHP
│   │   ├── Csrf.php             ← generación y validación de tokens CSRF
│   │   └── Auth.php             ← middleware de control de rol
│   └── helpers/                 ← funciones sueltas reutilizables
│       ├── format.php           ← formateo de fechas, precios
│       └── qr.php               ← generación del QR de las mesas
│
├── config/                      ← configuración (FUERA de public/)
│   ├── config.php               ← versionado: constantes globales
│   └── db.php                   ← NO versionado (.gitignore)
│
├── db/
│   ├── schema.sql               ← esquema de la base de datos
│   └── datos_demo.sql           ← datos de prueba para la demostración
│
├── scripts/
│   ├── install.sh               ← instalación automatizada desde cero
│   └── backup.sh                ← volcado de BD programado con cron
│
├── docs/                        ← documentación del proyecto
│   ├── 01_Alcance_Funcional.md
│   ├── 02_Arquitectura_Tecnica.md
│   ├── 03_Modelo_Datos.md
│   ├── 04_Estructura_Archivos.md
│   └── manual_usuario.md        ← manual de uso (al final del desarrollo)
│
├── .gitignore
├── .htaccess                    ← raíz: deniega acceso a todo lo que no sea public/
├── README.md                    ← portada del repositorio
└── LICENSE                      ← licencia MIT
```

---

## 3. Principios de diseño de la estructura

### 3.1 Separación public / no-public

El VirtualHost de Apache apunta su `DocumentRoot` a `/var/www/restaurante/public/`. Todo lo que quede **fuera de esa carpeta es invisible al navegador**: código fuente, credenciales, esquemas de base de datos, scripts y documentación. Esto elimina toda una familia de vulnerabilidades clásicas.

### 3.2 Convención sobre configuración

Cada carpeta tiene un **único propósito** claramente definido, y el código espera encontrar los archivos en su lugar convenido. No hay archivos de configuración que indiquen "dónde están las vistas" o "dónde están los modelos" — las rutas son fijas y conocidas por el núcleo.

### 3.3 Soft delete y archivos estáticos

El directorio `public/assets/uploads/` es el **único** dentro de `public/` donde se permite escritura en tiempo de ejecución (para que el administrador pueda subir fotos de platos). Se protege con un `.htaccess` propio que impide la ejecución de PHP en esa carpeta, mitigando el vector clásico de subir un script PHP disfrazado de imagen.

### 3.4 Documentación como ciudadano de primera clase

Los documentos `.md` viven dentro del repositorio (carpeta `/docs`), no aparte. Esto garantiza que:

- La documentación se versiona con el código (historial completo).
- Cualquier clon del proyecto trae consigo toda su documentación.
- GitHub los renderiza automáticamente para cualquier visitante.

---

## 4. Ruteo de la aplicación

### 4.1 Funcionamiento general

Todas las peticiones HTTP, sin excepción, se redirigen a `public/index.php` mediante reglas de reescritura de Apache (`.htaccess`). Desde ahí, el `Router` analiza la URL y el método HTTP y delega la petición al controlador correspondiente.

Esto se traduce en **URLs limpias** (por ejemplo, `/admin/platos/5/editar` en lugar de `/admin/platos.php?action=edit&id=5`).

### 4.2 Tabla de rutas principales

| Método | Ruta | Controlador | Acción |
|---|---|---|---|
| GET  | `/`                                | `ClienteController`  | `inicio` |
| GET  | `/mesa/{token}`                    | `ClienteController`  | `abrirSesion` |
| GET  | `/mesa/{token}/carta`              | `ClienteController`  | `carta` |
| GET  | `/mesa/{token}/plato/{id}`         | `ClienteController`  | `detallePlato` |
| POST | `/mesa/{token}/pedido`             | `ClienteController`  | `crearPedido` |
| GET  | `/mesa/{token}/mi-mesa`            | `ClienteController`  | `miMesa` |
| POST | `/mesa/{token}/alerta`             | `ClienteController`  | `llamarCamarero` |
| POST | `/mesa/{token}/valorar/{linea}`    | `ClienteController`  | `valorarLinea` |
| POST | `/mesa/{token}/cuenta`             | `ClienteController`  | `pedirCuenta` |
| GET  | `/cocina/login`                    | `CocinaController`   | `loginForm` |
| POST | `/cocina/login`                    | `CocinaController`   | `login` |
| POST | `/cocina/logout`                   | `CocinaController`   | `logout` |
| GET  | `/cocina`                          | `CocinaController`   | `panel` |
| POST | `/cocina/linea/{id}/estado`        | `CocinaController`   | `cambiarEstadoLinea` |
| POST | `/cocina/alerta/{id}/atender`      | `CocinaController`   | `atenderAlerta` |
| GET  | `/admin/login`                     | `AdminController`    | `loginForm` |
| POST | `/admin/login`                     | `AdminController`    | `login` |
| GET  | `/admin`                           | `AdminController`    | `dashboard` |
| GET  | `/admin/platos`                    | `AdminController`    | `listaPlatos` |
| GET  | `/admin/platos/nuevo`              | `AdminController`    | `formularioPlato` |
| POST | `/admin/platos`                    | `AdminController`    | `crearPlato` |
| GET  | `/admin/platos/{id}/editar`        | `AdminController`    | `formularioPlato` |
| POST | `/admin/platos/{id}`               | `AdminController`    | `actualizarPlato` |
| POST | `/admin/platos/{id}/eliminar`      | `AdminController`    | `eliminarPlato` |
| GET  | `/admin/mesas`                     | `AdminController`    | `listaMesas` |
| GET  | `/admin/mesas/{id}/qr`             | `AdminController`    | `generarQr` |
| *(análogo para categorías, usuarios, pedidos)* | | | |
| GET  | `/api/cocina/pedidos`              | `ApiController`      | `pedidosPendientes` |
| GET  | `/api/cocina/alertas`              | `ApiController`      | `alertasPendientes` |
| GET  | `/api/mesa/{token}/estado`         | `ApiController`      | `estadoMesa` |

La tabla anterior se completará a medida que avance el desarrollo.

### 4.3 Middleware de autenticación

Las rutas bajo `/cocina/*` (excepto el login) y `/admin/*` (excepto el login) están protegidas por un **middleware de autenticación** (`Auth`) que verifica:

1. Que exista una sesión PHP activa.
2. Que el usuario tenga el rol adecuado (`cocina` o `admin`).

Si el control falla, se redirige al formulario de login correspondiente.

---

## 5. Convenciones de nombres

| Elemento | Convención | Ejemplo |
|---|---|---|
| Clases PHP | PascalCase | `LineaPedido`, `ClienteController` |
| Archivos de clases | Igual al nombre de la clase + `.php` | `LineaPedido.php` |
| Métodos y funciones | camelCase | `obtenerPedidosPendientes()` |
| Propiedades de clase | camelCase | `$this->precioUnitario` |
| Constantes | MAYÚSCULAS_CON_GUIONES | `ESTADO_PENDIENTE`, `ROL_ADMIN` |
| Variables locales | camelCase | `$pedidosPendientes` |
| Tablas de BD | snake_case en español | `lineas_pedido` |
| Columnas de BD | snake_case en español | `precio_unitario` |
| URLs | minúsculas con guiones | `/admin/mesas`, `/mi-mesa` |
| Archivos de vista | snake_case | `plato_detalle.php` |
| Archivos CSS/JS | snake_case o kebab-case | `cliente.css` |
| Ramas Git | `main`, `dev`, `feature/...` | `feature/valoraciones` |
| Mensajes de commit | Imperativo en español | `Añade CRUD de platos` |

---

## 6. Archivos especiales

### 6.1 `.htaccess` de la raíz del proyecto

Deniega el acceso directo a todo el contenido salvo `public/`. Redirige silenciosamente a `public/index.php`.

### 6.2 `.htaccess` dentro de `public/`

Reescribe todas las peticiones a `index.php`, dejando que el router haga su trabajo.

### 6.3 `.htaccess` dentro de `public/assets/uploads/platos/`

Bloquea la ejecución de cualquier archivo `.php`, `.phtml`, `.php5`, etc. Evita ataques de carga de scripts maliciosos disfrazados.

### 6.4 `config/config.php`

Constantes globales del proyecto: URL base, zona horaria, modo debug, ruta absoluta, etc. Se versiona en Git.

### 6.5 `config/db.php`

Credenciales de acceso a la base de datos. **Nunca se versiona** (está en `.gitignore`). El script `install.sh` lo genera automáticamente durante la instalación a partir de una plantilla.

### 6.6 `.gitignore`

Contenido del archivo:

```gitignore
# Credenciales
config/db.php

# Cargas del administrador (salvo la de ejemplo)
public/assets/uploads/platos/*
!public/assets/uploads/platos/.gitkeep

# Backups de base de datos
/var/backups/
*.sql.gz

# Entorno local
.vscode/
.idea/
*.log
*.swp
.DS_Store
Thumbs.db

# Dependencias (reservado por si en el futuro se añade Composer)
vendor/
composer.lock
```

---

## 7. Scripts de infraestructura

### 7.1 `scripts/install.sh`

Script de instalación idempotente que deja el proyecto listo para funcionar en cualquier Ubuntu reciente. Ejecuta, en orden:

1. Comprueba que el sistema es Ubuntu (avisa si no lo es).
2. Actualiza el índice de paquetes (`apt update`).
3. Instala las dependencias: `apache2`, `php`, `php-mysql`, `mariadb-server`, `git`.
4. Habilita los módulos de Apache necesarios (`rewrite`, `ssl`).
5. Crea la base de datos `restaurante` y el usuario dedicado con permisos mínimos.
6. Carga `db/schema.sql` y `db/datos_demo.sql`.
7. Genera `config/db.php` a partir de una plantilla con las credenciales reales.
8. Copia y habilita el VirtualHost de Apache.
9. Ajusta permisos en `public/assets/uploads/` para que el usuario de Apache (`www-data`) pueda escribir.
10. Configura UFW (puertos 22, 80 y 443).
11. Programa `backup.sh` en el cron diario del usuario `root`.
12. Reinicia Apache.
13. Imprime un mensaje final con la URL local y las credenciales de demo.

### 7.2 `scripts/backup.sh`

Script invocado por `cron` a diario. Vuelca la base de datos con `mysqldump`, comprime el resultado con `gzip`, lo guarda en `/var/backups/bd/` y rota los archivos más antiguos de 7 días.

---

## 8. Dónde se ejecuta todo

Recordatorio consolidado de ubicaciones en el sistema de archivos de despliegue:

| Concepto | Ruta en Ubuntu |
|---|---|
| Raíz del proyecto | `/var/www/restaurante/` |
| Directorio público (DocumentRoot de Apache) | `/var/www/restaurante/public/` |
| Configuración de Apache | `/etc/apache2/sites-available/restaurante.conf` |
| Logs de Apache | `/var/log/apache2/` |
| Datos de MariaDB | `/var/lib/mysql/` |
| Backups automáticos | `/var/backups/bd/` |
| Cron del backup | `/etc/cron.d/restaurante-backup` |

---

## 9. Historial de cambios

| Versión | Fecha | Cambios |
|---|---|---|
| 1.0 | 2026-04-24 | Versión inicial cerrada tras el Bloque 4. Define estructura completa de carpetas, convenciones de nombres, tabla de rutas y scripts de infraestructura. |
