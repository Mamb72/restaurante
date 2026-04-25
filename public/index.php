<?php
/**
 * public/index.php
 * Punto de entrada único de la aplicación.
 *
 * 1. Carga configuración.
 * 2. Carga las clases del núcleo.
 * 3. Define las rutas.
 * 4. Delega en el Router para resolver la petición actual.
 */

declare(strict_types=1);

// --- 1. Configuración global ------------------------------------------
require dirname(__DIR__) . '/config/config.php';

// --- 2. Clases del núcleo ---------------------------------------------
require BASE_PATH . '/app/core/Database.php';
require BASE_PATH . '/app/core/Controller.php';
require BASE_PATH . '/app/core/Router.php';

// --- 3. Definición de rutas -------------------------------------------
$router = new Router();

$router->get('/', ['ClienteController', 'inicio']);
$router->get('/carta', ['ClienteController', 'carta']);
$router->get('/mesa/{token}', ['MesaController', 'entrar']);
$router->get('/mi-mesa', ['MesaController', 'miMesa']);

// (En las siguientes fases iremos añadiendo aquí más rutas)

// --- 4. Resolver la petición ------------------------------------------
try {
    $router->resolver();
} catch (Throwable $e) {
    http_response_code(500);
    if (DEBUG_MODE) {
        echo '<h1>Error 500</h1><pre>' . htmlspecialchars((string) $e) . '</pre>';
    } else {
        echo '<h1>Error interno</h1>';
        error_log((string) $e);
    }
}
