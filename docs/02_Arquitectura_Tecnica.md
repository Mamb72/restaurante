# 02 — Arquitectura Técnica y Stack

**Proyecto:** Sistema web integral para la digitalización de cartas y pedidos en establecimientos de restauración
**Autor:** Nicolás Barrera Quintana
**Ciclo:** 2º ASIR — Administración de Sistemas Informáticos en Red
**Documento:** 02 · Arquitectura Técnica
**Estado:** Cerrado y validado
**Versión:** 2.0

---

## 1. Propósito del documento

Este documento fija **cómo** se construye el sistema a nivel técnico: qué tecnologías se usan, cómo se estructura el código, sobre qué infraestructura se despliega, y por qué se toma cada decisión. Complementa al documento `01_Alcance_Funcional.md`.

Cada decisión se acompaña de su **justificación para la defensa**, de forma que el autor pueda explicar con criterio por qué eligió cada pieza.

---

## 2. Stack tecnológico

| Capa | Tecnología elegida | Justificación |
|---|---|---|
| Sistema operativo | **Ubuntu 25** (entorno nativo del autor) | Distribución Linux moderna, kernel reciente, ecosistema UNIX nativo para desarrollo y despliegue. |
| Servidor web | **Apache 2.4** | Servidor web maduro, extensible con módulos, configurable por VirtualHosts y `.htaccess`. Estándar profesional con amplísima documentación. |
| Lenguaje backend | **PHP 8.2+** | Masivamente usado en hostelería y pymes (coherente con el enfoque emprendedor del proyecto), tipado estricto disponible, integración nativa con Apache. |
| Base de datos | **MariaDB 10.11+** | Bifurcación libre de MySQL, 100% compatible, disponible en repositorios oficiales de Ubuntu. |
| Frontend | **HTML5 + CSS3 + JavaScript vanilla (sin frameworks)** | Dominio total del código. Apropiado al perfil ASIR (evita solape con temario de DAW). |
| Acceso a datos | **PDO (PHP Data Objects)** con consultas preparadas | Abstracción estándar, protección contra inyección SQL. |
| Hashing contraseñas | **`password_hash()` de PHP con bcrypt** | Implementación segura estándar, resistente a ataques por fuerza bruta. |
| Editor de código | **Visual Studio Code** | Editor moderno, extensible, soporte nativo de PHP y Git. |
| Control de versiones | **Git + GitHub público** | Estándar de la industria, portfolio profesional, respaldo en la nube, historial demostrable de trabajo. |
| Automatización de despliegue | **Script Bash (`install.sh`)** | Permite reproducir el entorno completo en cualquier Ubuntu desde cero con un solo comando. |

---

## 3. Arquitectura de código — Patrón MVC manual

El código PHP se organiza siguiendo el patrón **Modelo-Vista-Controlador** implementado manualmente, **sin frameworks** como Laravel o Symfony. Esta decisión es deliberada:

- Mantiene el proyecto dentro del alcance del ciclo ASIR.
- Permite **comprender y defender** cada línea de código.
- Evita dependencias externas pesadas.

### 3.1 Responsabilidades de cada capa

- **Modelos** (`/app/models/`) — clases PHP que encapsulan la lógica de acceso a la base de datos. Un modelo por entidad principal (`Plato.php`, `Pedido.php`, `Mesa.php`, etc.).
- **Vistas** (`/app/views/`) — archivos PHP dedicados exclusivamente a generar HTML. No contienen lógica de negocio ni consultas a la BD.
- **Controladores** (`/app/controllers/`) — reciben la petición HTTP, orquestan las llamadas al modelo y eligen la vista a renderizar.
- **Router** (`/public/index.php`) — punto de entrada único. Analiza la URL y decide qué controlador responde.

### 3.2 Justificación para la defensa

> *"He optado por implementar MVC a mano porque el ciclo formativo se centra en administración de sistemas, no en frameworks específicos. Usar Laravel o Symfony habría sido desproporcionado para el volumen de funcionalidades, habría añadido una curva de aprendizaje de un framework concreto y habría dificultado explicar con detalle cómo funciona internamente cada parte del sistema."*

---

## 4. Arquitectura de infraestructura

### 4.1 Despliegue nativo en Ubuntu 25

Todos los componentes del sistema (servidor web, intérprete PHP y base de datos) se instalan y ejecutan **directamente sobre el sistema operativo del equipo de desarrollo**, sin virtualización ni contenedores. Se gestionan como **servicios systemd** del propio sistema.

```
┌──────────────────────────────────────────────────────────────┐
│  Equipo de desarrollo — Ubuntu 25, 16 GB RAM                 │
│                                                              │
│   ┌──────────────────────────────────────────────────────┐   │
│   │  systemd                                             │   │
│   │                                                      │   │
│   │   ┌─────────────────┐         ┌──────────────────┐   │   │
│   │   │ apache2.service │ ──PDO──►│ mariadb.service  │   │   │
│   │   │  Apache 2.4     │ (127..) │  MariaDB 10.11   │   │   │
│   │   │  + PHP 8.2      │         │  escucha         │   │   │
│   │   │  :80, :443      │         │  127.0.0.1:3306  │   │   │
│   │   └────────┬────────┘         └──────────────────┘   │   │
│   │            │                                         │   │
│   │            ▼                                         │   │
│   │   UFW: 22, 80, 443 permitidos                        │   │
│   └──────────────────────────────────────────────────────┘   │
│                                                              │
│   Código en:   /var/www/restaurante/                         │
│   Backups en:  /var/backups/bd/                              │
│                                                              │
└──────────────────────────────────────────────────────────────┘
                               ▲
                               │  HTTP/HTTPS
                               │
            Navegador del host (localhost) o
            móvil conectado a la LAN del equipo
```

### 4.2 Rutas y ubicaciones estándar

- **Código fuente del proyecto:** `/var/www/restaurante/`
- **Punto de entrada público:** `/var/www/restaurante/public/`
- **Configuración de Apache:** `/etc/apache2/sites-available/restaurante.conf`
- **Logs de Apache:** `/var/log/apache2/`
- **Datos de MariaDB:** `/var/lib/mysql/`
- **Backups automáticos:** `/var/backups/bd/`
- **Script de instalación:** `install.sh` en la raíz del repositorio Git.

### 4.3 Seguridad sin separación física de capas

Al ejecutarse todo en el mismo sistema, la seguridad se garantiza por configuración en lugar de por separación física:

- **MariaDB solo escucha en `127.0.0.1`** (loopback). Nunca es accesible desde la red exterior, aunque el equipo esté conectado a internet.
- **Usuario MariaDB dedicado** para la aplicación, con permisos mínimos sobre la base de datos del restaurante (solo SELECT, INSERT, UPDATE, DELETE). No se usa nunca `root` desde la aplicación.
- **UFW** (firewall del sistema) activo, permitiendo únicamente los puertos estrictamente necesarios: 22 (SSH), 80 (HTTP) y 443 (HTTPS).
- Apache sirve la aplicación desde `public/`, de forma que el resto del código (modelos, controladores, configuración) queda **fuera del directorio accesible desde el navegador**.

### 4.4 Portabilidad del proyecto

Al no usar VMs ni contenedores, la portabilidad se consigue mediante **automatización del despliegue**:

1. **Proyecto versionado en GitHub** → `git clone` descarga todo el código en cualquier equipo.
2. **Script `install.sh`** → instala Apache, PHP, MariaDB, crea el usuario de base de datos, importa el esquema y los datos de demostración, configura el VirtualHost de Apache y deja el sistema listo para servir.
3. **Archivos `schema.sql` y `datos_demo.sql`** en el repositorio, para reproducir la base de datos desde cero.

Con esto, un evaluador con cualquier Ubuntu reciente podría tener el proyecto funcionando en cuestión de minutos ejecutando un único comando.

### 4.5 Justificación para la defensa

> *"He decidido desplegar todos los componentes directamente sobre el sistema operativo del equipo de desarrollo, sin virtualización ni contenedores, priorizando la simplicidad del entorno y la agilidad de desarrollo. La seguridad se garantiza por configuración: la base de datos escucha exclusivamente en la interfaz de loopback, lo que la hace inaccesible desde la red; la aplicación se conecta con un usuario dedicado con permisos mínimos; y el firewall del sistema solo expone los puertos estrictamente necesarios. Para garantizar la portabilidad, he automatizado el despliegue completo mediante un script Bash que reconstruye el entorno desde cero en cualquier Ubuntu, lo cual es una práctica habitual en administración de sistemas y más defendible que la simple copia de imágenes de máquinas virtuales."*

---

## 5. Estrategia de tiempo cuasi-real

Las funcionalidades que requieren actualización en vivo son:

- Panel de cocina — aparición de nuevos pedidos.
- Vista "Mi mesa" del cliente — cambios de estado de sus platos.
- Alertas de "llamar al camarero".

### 5.1 Técnica elegida: **Polling AJAX**

El navegador realiza una petición AJAX al servidor **cada 3-5 segundos** preguntando si hay novedades. Se implementa con `setInterval` en JavaScript y `fetch()` contra endpoints PHP dedicados que devuelven JSON.

### 5.2 Alternativas descartadas

| Técnica | Razón del descarte |
|---|---|
| Server-Sent Events (SSE) | Añade complejidad de configuración en Apache/PHP sin aportar valor perceptible con este volumen de peticiones. |
| WebSockets | Requiere infraestructura adicional (Ratchet, Node.js o similar). Desproporcionado para el caso de uso. |

### 5.3 Justificación para la defensa

> *"He elegido polling AJAX porque es técnicamente suficiente para el volumen de peticiones del sistema (un restaurante procesa decenas, no miles, de pedidos por hora), es sencillo de implementar y depurar, no requiere infraestructura adicional y puedo explicar cada línea de su funcionamiento. Utilizar WebSockets habría sido ingeniería sobredimensionada sin beneficio real."*

---

## 6. Control de versiones

### 6.1 Herramienta: Git + GitHub público

- **Git** como sistema de control de versiones local.
- **GitHub** como repositorio remoto con visibilidad pública.

### 6.2 Estrategia de ramas

Se usa un flujo sencillo con dos ramas principales:

- `main` — rama estable. Solo se actualiza cuando una funcionalidad está terminada y probada.
- `dev` — rama de desarrollo diario.

### 6.3 Convención de mensajes de commit

Formato breve y descriptivo en español, en imperativo:

```
Añade CRUD de platos en panel admin
Corrige validación de precio negativo
Documenta instalación de MariaDB
Refactoriza controlador de pedidos
```

### 6.4 Archivos excluidos del repositorio (.gitignore)

- Credenciales y archivos de configuración con contraseñas (`config/db.php`).
- Archivos temporales, de IDE, de sistema operativo.
- Imágenes subidas por el administrador durante las pruebas (salvo las de ejemplo).
- Backups de base de datos.

### 6.5 Justificación para la defensa

> *"He usado Git y GitHub desde el primer día por tres razones: primero, como copia de seguridad ante cualquier incidente con el equipo de desarrollo; segundo, como historial completo de la evolución del proyecto, que permite revertir cambios o identificar cuándo se introdujo un error; tercero, porque GitHub es el estándar de facto en la industria y tener el proyecto publicado demuestra transparencia y permite incluirlo en mi portfolio profesional."*

---

## 7. Seguridad

Se implementa **seguridad básica pero sólida**, cubriendo los vectores de ataque más habituales en aplicaciones web:

### 7.1 Medidas implementadas

| Medida | Implementación |
|---|---|
| Contraseñas hasheadas | `password_hash()` con algoritmo bcrypt en PHP. Verificación con `password_verify()`. |
| Gestión de sesiones | Sesiones PHP con cookies marcadas `HttpOnly` y `Secure`. Regeneración de ID al hacer login. |
| Protección contra SQL injection | **PDO con consultas preparadas**, sin excepción. Prohibido concatenar variables en SQL. |
| Protección contra XSS | `htmlspecialchars()` en toda salida de datos provenientes del usuario. |
| Protección CSRF | Token aleatorio por sesión en formularios sensibles (login, administración). |
| HTTPS | Certificado autofirmado en desarrollo. Documentación para Let's Encrypt en producción. |
| Firewall | **UFW** activo permitiendo solo 22, 80 y 443. |
| Base de datos no expuesta | MariaDB escucha únicamente en `127.0.0.1`. No accesible desde la red. |
| Usuario BD con permisos mínimos | Usuario de aplicación con permisos limitados a la base de datos del restaurante. No se usa `root`. |
| Control de acceso por rol | Middleware propio en el router que valida el rol antes de acceder a rutas protegidas. |

### 7.2 Justificación para la defensa

> *"He implementado las medidas de seguridad estándar contra las principales amenazas OWASP: inyección SQL mediante consultas preparadas con PDO, XSS mediante escape de salida, CSRF mediante tokens, y control de acceso mediante verificación de rol en cada petición protegida. Las contraseñas se almacenan hasheadas con bcrypt, nunca en claro. La base de datos está configurada para escuchar exclusivamente en la interfaz de loopback, por lo que no es accesible desde la red aunque el equipo esté conectado a internet, y la aplicación se conecta con un usuario dedicado con permisos mínimos."*

---

## 8. Copias de seguridad y recuperación

### 8.1 Estrategia

- **Volcado automático diario** de la base de datos mediante `mysqldump` programado con `cron`.
- Archivos comprimidos (`.sql.gz`) y almacenados en `/var/backups/bd/`, con rotación de los **últimos 7 días**.
- Documentación del **procedimiento de restauración** paso a paso para la memoria final.

### 8.2 Ejemplo de script diario

```bash
#!/bin/bash
FECHA=$(date +%Y-%m-%d)
mysqldump -u backup -p'CONTRASEÑA' restaurante | gzip > /var/backups/bd/restaurante_$FECHA.sql.gz
find /var/backups/bd/ -name "restaurante_*.sql.gz" -mtime +7 -delete
```

### 8.3 Justificación para la defensa

> *"El sistema incluye una política de copias de seguridad automatizada mediante cron y mysqldump, con rotación de 7 días para evitar que las copias consuman espacio indefinidamente. El procedimiento de restauración está documentado y probado. Esta es una responsabilidad básica de un administrador de sistemas y una línea de defensa frente a pérdidas de datos."*

---

## 9. Entornos

El proyecto contempla un único entorno:

- **Desarrollo / Demostración** — el equipo del autor, con todos los servicios instalados de forma nativa, datos de prueba cargados, certificado autofirmado y modo debug de PHP activado.

Se documenta en la memoria cómo se migraría a un **entorno de producción real** (servidor dedicado o VPS con certificado Let's Encrypt, modo debug desactivado, logs rotados, etc.), pero no se implementa.

---

## 10. Diagrama de despliegue resumen

```
    ┌──────────────────────┐
    │  Dispositivo cliente │
    │ (móvil del comensal) │
    └──────────┬───────────┘
               │ HTTP/HTTPS (puerto 80/443)
               ▼
    ┌─────────────────────────────────┐
    │  Equipo Ubuntu 25               │
    │                                 │
    │  ┌───────────────────────────┐  │
    │  │ apache2.service           │  │
    │  │ Apache 2.4 + PHP 8.2      │  │
    │  │ VirtualHost → /var/www/   │  │
    │  │        restaurante/public │  │
    │  └─────────────┬─────────────┘  │
    │                │ PDO sobre      │
    │                │ 127.0.0.1      │
    │                ▼                │
    │  ┌───────────────────────────┐  │
    │  │ mariadb.service           │  │
    │  │ MariaDB 10.11             │  │
    │  │ bind-address = 127.0.0.1  │  │
    │  └───────────────────────────┘  │
    │                                 │
    │  UFW: 22, 80, 443               │
    │  cron: backup diario            │
    └─────────────────────────────────┘
```

---

## 11. Historial de cambios

| Versión | Fecha | Cambios |
|---|---|---|
| 1.0 | 2026-04-24 | Versión inicial con arquitectura de dos VMs separadas (web + BD), PHP y MariaDB. |
| 2.0 | 2026-04-24 | Cambio arquitectónico mayor: eliminación de VMs. Todo se despliega de forma nativa sobre Ubuntu 25. Se sustituye la portabilidad vía `.ova` por automatización del despliegue mediante script Bash (`install.sh`) y repositorio Git. Se mantiene PHP+MariaDB. Se adaptan las secciones de infraestructura, seguridad y entornos para reflejar el nuevo diseño. |
