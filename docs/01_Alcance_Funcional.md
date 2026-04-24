# 01 — Alcance Funcional del Proyecto

**Proyecto:** Sistema web integral para la digitalización de cartas y pedidos en establecimientos de restauración
**Autor:** Nicolás Barrera Quintana
**Ciclo:** 2º ASIR — Administración de Sistemas Informáticos en Red
**Documento:** 01 · Alcance Funcional
**Estado:** Cerrado y validado
**Versión:** 1.1

---

## 1. Propósito del documento

Este documento fija **qué va a hacer el sistema y qué no**, sirviendo como referencia inamovible para el resto del proyecto. Cualquier cambio posterior deberá justificarse y registrarse como nueva versión.

Su objetivo secundario es permitir que un tercero (persona o IA) pueda reconstruir el proyecto desde cero con fidelidad al planteamiento original.

---

## 2. Visión general

El sistema es una **aplicación web** que permite a los clientes de un restaurante consultar la carta y realizar pedidos desde su propio móvil, sin intermediación directa de personal. Los pedidos llegan en tiempo cuasi-real a un panel de cocina, y el restaurante dispone de un panel de administración para gestionar la carta, las mesas y los pedidos.

El acceso del cliente se realiza mediante **código QR pegado en cada mesa**, sin necesidad de registro ni descarga de aplicaciones.

---

## 3. Actores del sistema

| Actor | Descripción | Acceso |
|---|---|---|
| **Cliente / Comensal** | Persona que consume en el restaurante | QR de mesa, sin autenticación |
| **Operario de cocina** | Personal que prepara los pedidos | Login con usuario y contraseña |
| **Administrador** | Dueño o encargado del restaurante | Login con usuario y contraseña y rol privilegiado |

---

## 4. Funcionalidades por actor

### 4.1 Cliente / Comensal

1. Acceder a la carta escaneando el QR de su mesa. La URL contiene un identificador único de mesa.
2. Visualizar la carta organizada por categorías (entrantes, principales, bebidas, postres, etc.).
3. Ver una sección destacada al inicio de la carta con los **platos del día / platos destacados** marcados por el administrador.
4. **Filtrar la carta por criterio dietético**: vegetariano, vegano, sin gluten, sin lactosa. Los filtros se calculan a partir de la información de alérgenos e ingredientes de cada plato.
5. Ver el detalle de cada plato: nombre, descripción, precio, fotografía, alérgenos, valoración media de otros clientes y disponibilidad.
6. Añadir platos a un carrito, modificar cantidades y eliminar ítems.
7. Escribir una nota libre por plato (ej. "sin cebolla", "poco hecho").
8. Enviar el pedido, que queda asociado a su mesa.
9. Realizar **pedidos sucesivos** dentro de la misma sesión de mesa (bebidas → comida → postre).
10. Consultar el estado actualizado de sus pedidos: *Pendiente → En preparación → Listo → Servido*.
11. **Vista "Mi mesa"**: panel que muestra de un vistazo todo lo que ha ido pidiendo la mesa en la sesión, el estado de cada plato en tiempo real y el **total gastado acumulado**.
12. **Llamar al camarero** mediante un botón que envía una alerta al panel de cocina/sala, indicando la mesa desde la que se solicita ayuda.
13. **Valorar un plato** (de 1 a 5 estrellas) una vez que haya pasado al estado *Servido*. La valoración es opcional y anónima.
14. Solicitar la cuenta, que genera un ticket en pantalla con el desglose total (ver sección 7).

### 4.2 Cocina

1. Iniciar sesión con credenciales propias.
2. Visualizar en un **único panel unificado** todos los pedidos entrantes (platos y bebidas).
3. Cada pedido muestra: número de mesa, hora, lista de platos con notas del cliente y estado actual.
4. Cambiar el estado de cada pedido o plato mediante botones: *Pendiente → En preparación → Listo*.
5. Refresco automático del panel cada pocos segundos para ver nuevos pedidos sin recargar manualmente.
6. Aviso visual (y opcionalmente sonoro) al entrar un pedido nuevo.
7. Recibir y visualizar de forma destacada las **alertas de "llamar al camarero"** enviadas desde las mesas, con opción de marcarlas como atendidas.
8. Consultar el histórico de pedidos del día en curso.

### 4.3 Administrador

1. Iniciar sesión con credenciales de administrador.
2. **Gestión de la carta:**
   - CRUD de categorías.
   - CRUD de platos (nombre, descripción, precio, foto, alérgenos, etiquetas dietéticas, categoría, disponibilidad).
   - Marcar un plato como *agotado* sin eliminarlo.
   - Marcar uno o varios platos como **destacados / plato del día**, con opción de fijar una fecha de expiración.
3. **Gestión de mesas:**
   - CRUD de mesas.
   - Generación e impresión del código QR de cada mesa desde el propio panel.
4. **Gestión de usuarios internos:**
   - Alta, baja y modificación de usuarios de cocina y otros administradores.
   - Cambio de contraseñas.
5. **Gestión operativa:**
   - Ver todos los pedidos del día con sus estados.
   - Marcar pedidos como servidos o anulados.
   - Ver el listado de alertas de "llamar al camarero" del día.
6. **Dashboard de estadísticas** (elemento clave para la defensa):
   - Facturación del día, semana y mes.
   - Plato más pedido y menos pedido.
   - Hora punta / distribución de pedidos por franja horaria.
   - Mesa con más rotación.
   - **Plato mejor valorado y peor valorado** (nota media y número de valoraciones).
   - **Nota media global del restaurante** agregada a partir de todas las valoraciones.

---

## 5. Fuera de alcance

Estas funcionalidades **no** formarán parte del sistema entregado, y se documentarán como líneas de evolución futura:

- Pasarela de pago real (Redsys, Stripe, PayPal).
- Aplicación móvil nativa (iOS/Android).
- Sistema de reservas de mesa.
- Gestión multi-restaurante (una sola instancia atiende a varios establecimientos).
- Gestión de empleados, turnos y nóminas.
- Integración con TPV físico o impresora de tickets térmica.
- Programa de fidelización o cupones de descuento.

---

## 6. Reglas de negocio clave

- Una mesa puede estar activa con **una sesión abierta** que agrupa varios pedidos sucesivos.
- La sesión de mesa se cierra cuando se solicita la cuenta o cuando el administrador la cierra manualmente.
- Los estados de un pedido siguen la máquina: `PENDIENTE → EN_PREPARACION → LISTO → SERVIDO`. No se admiten saltos hacia atrás salvo por anulación del administrador.
- Un plato marcado como *agotado* no aparece como seleccionable en la carta, pero sigue existiendo en la base de datos.
- La gestión de alérgenos se ajustará a los **14 alérgenos de declaración obligatoria** según el Reglamento UE 1169/2011: cereales con gluten, crustáceos, huevos, pescado, cacahuetes, soja, lácteos, frutos de cáscara, apio, mostaza, sésamo, sulfitos, altramuces y moluscos.
- Cada mesa tiene un QR único y persistente, regenerable desde el panel de administración.
- Un plato solo puede ser **valorado** por un cliente una vez haya alcanzado el estado *Servido* dentro de esa sesión de mesa. Una misma mesa no puede valorar el mismo plato más de una vez dentro de la misma sesión.
- Las **etiquetas dietéticas** (vegetariano, vegano, sin gluten, sin lactosa) son propiedades que el administrador asigna a cada plato al darlo de alta o editarlo, y son las que alimentan los filtros del cliente.
- Los **platos destacados** pueden tener fecha de expiración: si se marca una fecha, dejan de aparecer como destacados al llegar ese día, sin necesidad de intervención manual.
- Una **alerta de llamar al camarero** se considera resuelta cuando un operario la marca como atendida desde el panel de cocina. Las alertas no resueltas se mantienen visibles de forma destacada.

---

## 7. Tratamiento del pago

El pago **no se implementa** funcionalmente. La demostración se resuelve así:

1. El cliente pulsa *"Pedir la cuenta"* en su interfaz.
2. El sistema calcula el total de la sesión de la mesa.
3. Se genera un **ticket en pantalla** (con opción de imprimir o guardar como PDF) con el desglose.
4. Se muestra el mensaje: *"Un camarero pasará a cobrar"*.
5. El administrador puede marcar la mesa como *cerrada* desde su panel.

En la memoria del proyecto se incluirá un apartado específico titulado **"Integración futura con pasarelas de pago"** donde se explicará con rigor técnico cómo se implementaría la pasarela real (Redsys para bancos españoles, Stripe para internacional), las implicaciones de seguridad (PCI-DSS, tokenización, 3D Secure) y el flujo de una transacción.

---

## 8. Requisitos no funcionales preliminares

- **Seguridad:** autenticación con contraseñas hasheadas, control de acceso por rol, sanitización de entradas, protección contra SQLi y XSS, HTTPS en despliegue.
- **Rendimiento:** refresco del panel de cocina cada 3–5 segundos sin degradar la experiencia.
- **Disponibilidad:** sistema pensado para operar de forma continua durante el servicio.
- **Usabilidad:** interfaz de cliente **responsive** y pensada *mobile-first* (el cliente accede desde su móvil).
- **Accesibilidad:** contraste adecuado, tipografía legible, navegación simple.
- **Idioma:** español (castellano). Se diseñará la estructura para facilitar futura internacionalización.
- **Persistencia:** toda la información relevante (pedidos, platos, mesas, usuarios) se guarda en base de datos relacional.
- **Backups:** procedimiento documentado de copia y restauración de la base de datos.

---

## 9. Criterios de aceptación del proyecto

El proyecto se considerará completado y defendible cuando:

- Un cliente pueda escanear un QR de mesa, ver la carta, pedir, realizar pedidos sucesivos y solicitar la cuenta sin errores.
- El panel de cocina reciba todos los pedidos en menos de 5 segundos desde que el cliente los envía.
- El administrador pueda gestionar íntegramente la carta, las mesas y los usuarios, y generar los QR.
- El dashboard muestre al menos las cinco métricas descritas en 4.3 con datos reales.
- La documentación técnica y la memoria estén completas y permitan reconstruir el sistema.
- El sistema supere una demostración completa de extremo a extremo sin intervención correctiva.

---

## 10. Historial de cambios

| Versión | Fecha | Cambios |
|---|---|---|
| 1.0 | 2026-04-24 | Versión inicial cerrada tras acuerdo Bloque 1. |
| 1.1 | 2026-04-24 | Añadidas cinco funcionalidades de valor añadido: vista "Mi mesa" con histórico y gasto acumulado, filtro dietético de la carta, platos destacados/plato del día, llamar al camarero y valoración de platos. Ampliado el dashboard del administrador con métricas de valoraciones. |
