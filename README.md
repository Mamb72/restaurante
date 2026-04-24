# Sistema de pedidos para restaurantes

> Proyecto Final del ciclo formativo de Grado Superior **Administración de Sistemas Informáticos en Red (ASIR)**

Aplicación web que permite a los clientes de un restaurante consultar la carta y realizar pedidos desde su propio móvil escaneando un código QR, sin necesidad de atención directa de un camarero. Los pedidos llegan en tiempo cuasi-real a un panel de cocina, y el restaurante dispone de un panel de administración completo para gestionar la carta, las mesas, los usuarios y visualizar estadísticas.

**Autor:** Nicolás Barrera Quintana
**Ciclo:** 2º ASIR
**Curso:** 2025 / 2026

---

## Características principales

- Acceso del cliente mediante **QR por mesa**, sin registro ni descarga de aplicaciones.
- Carta digital con **categorías, fotos, alérgenos y etiquetas dietéticas** (vegetariano, vegano, sin gluten, sin lactosa).
- Pedidos sucesivos dentro de la misma sesión de mesa.
- Panel de cocina **en tiempo cuasi-real** con actualización automática.
- Panel de administración con **CRUD** completo, generación de códigos QR y **dashboard de estadísticas**.
- Vista **"Mi mesa"** para el cliente con histórico de pedidos y total gastado.
- **Llamada al camarero** tipificada desde la mesa.
- **Valoraciones por plato** (1-5 estrellas) una vez servido.
- **Platos destacados** / plato del día con expiración automática.
- Gestión de **14 alérgenos** según el Reglamento UE 1169/2011.

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Sistema operativo | Ubuntu 25 (desarrollo) / Ubuntu Server (producción) |
| Servidor web | Apache 2.4 |
| Backend | PHP 8.2+ (arquitectura MVC manual, sin frameworks) |
| Base de datos | MariaDB 10.11+ |
| Frontend | HTML5 + CSS3 + JavaScript vanilla |
| Acceso a datos | PDO con consultas preparadas |
| Control de versiones | Git + GitHub |

El proyecto evita frameworks pesados (Laravel, Symfony, React, Vue) para mantenerse alineado con el perfil competencial del ciclo ASIR y permitir explicar cada línea del código durante la defensa.

---

## Requisitos

- Sistema Ubuntu 22.04 o superior (probado en Ubuntu 25).
- Acceso `sudo` en la máquina de despliegue.
- Conexión a internet para la descarga de paquetes.

## Instalación

La instalación está completamente automatizada mediante un único script:

```bash
git clone https://github.com/<usuario>/restaurante.git
cd restaurante
chmod +x scripts/install.sh
sudo ./scripts/install.sh
```

El script realiza las siguientes tareas:

1. Instala Apache, PHP, MariaDB y demás dependencias.
2. Habilita los módulos necesarios de Apache (`rewrite`, `ssl`).
3. Crea la base de datos y un usuario dedicado con permisos mínimos.
4. Carga el esquema y los datos de demostración.
5. Configura el VirtualHost de Apache.
6. Ajusta permisos y configura el firewall (UFW).
7. Programa una copia de seguridad diaria automática.

Al finalizar, el sistema estará disponible en `http://localhost`.

## Credenciales de la demostración

Una vez instalado, se puede acceder con los siguientes usuarios:

| Rol | Usuario | Contraseña |
|---|---|---|
| Administrador | admin@demo.local | `admin1234` |
| Cocina | cocina@demo.local | `cocina1234` |

> **Nota:** estas credenciales son solo para el entorno de demostración. En cualquier despliegue real deben cambiarse inmediatamente.

Para simular un cliente, escanear el QR de cualquier mesa desde el panel de administración o visitar directamente la URL que se muestre en la lista de mesas.

---

## Estructura del proyecto

```
restaurante/
├── public/         ← único directorio accesible desde el navegador
├── app/            ← código de la aplicación (controladores, modelos, vistas)
├── config/         ← configuración (credenciales no versionadas)
├── db/             ← esquema SQL y datos de demostración
├── scripts/        ← instalación y backup automatizados
└── docs/           ← documentación del proyecto
```

La estructura completa y las convenciones de código están documentadas en [`docs/04_Estructura_Archivos.md`](docs/04_Estructura_Archivos.md).

## Documentación

Toda la documentación técnica del proyecto se encuentra en la carpeta [`docs/`](docs/):

- [`01_Alcance_Funcional.md`](docs/01_Alcance_Funcional.md) — **qué hace** el sistema, funcionalidades por rol, reglas de negocio y criterios de aceptación.
- [`02_Arquitectura_Tecnica.md`](docs/02_Arquitectura_Tecnica.md) — **cómo se construye** el sistema, decisiones técnicas justificadas.
- [`03_Modelo_Datos.md`](docs/03_Modelo_Datos.md) — **esquema completo de la base de datos** con 13 tablas, diagrama entidad-relación y consultas de ejemplo.
- [`04_Estructura_Archivos.md`](docs/04_Estructura_Archivos.md) — **organización del código**, convenciones de nombres y tabla de rutas de la aplicación.

## Seguridad

El sistema implementa las medidas estándar contra las principales amenazas OWASP:

- Contraseñas almacenadas con **bcrypt** (`password_hash()`).
- **PDO con consultas preparadas** contra inyección SQL.
- Escape de salida con `htmlspecialchars()` contra XSS.
- **Tokens CSRF** en formularios sensibles.
- Sesiones con cookies `HttpOnly` y `Secure`, con regeneración de ID.
- **MariaDB escucha solo en `127.0.0.1`**: inaccesible desde la red.
- Usuario de aplicación con permisos mínimos (no se usa `root`).
- **UFW** configurado para permitir solo los puertos necesarios (22, 80, 443).
- HTTPS en producción mediante Let's Encrypt (documentado).

Detalle completo en [`docs/02_Arquitectura_Tecnica.md`](docs/02_Arquitectura_Tecnica.md), sección 7.

## Copias de seguridad

El script de instalación configura una copia de seguridad diaria automatizada mediante `cron` y `mysqldump`, con rotación de los últimos 7 días.

---

## Estado del proyecto

Este repositorio corresponde al **Proyecto Final** del ciclo formativo de Grado Superior ASIR. El desarrollo se realiza de forma individual y sigue una metodología incremental.

### Fases del desarrollo

- [x] **Bloque 1** — Alcance funcional
- [x] **Bloque 2** — Arquitectura técnica
- [x] **Bloque 3** — Modelo de datos
- [x] **Bloque 4** — Estructura de archivos y convenciones
- [x] **Bloque 5** — Setup del entorno de desarrollo
- [ ] **Desarrollo** — implementación de backend y frontend
- [ ] **Pruebas** — funcionales y de integración
- [ ] **Memoria final y defensa**


## Proyección futura

El proyecto se concibe como base potencial para una iniciativa emprendedora real. Las líneas de evolución contempladas son:

- Integración con **pasarelas de pago reales** (Redsys, Stripe).
- **Aplicación móvil** nativa para el personal.
- Sistema de **reservas de mesa** online.
- Gestión **multi-restaurante** (una instancia, múltiples establecimientos).
- Integración con **TPV físico** e impresora térmica de tickets.
- **Internacionalización** (castellano / inglés).

---

## Licencia

Este proyecto se distribuye bajo los términos de la licencia MIT. Consulta el archivo [`LICENSE`](LICENSE) para más detalles.
