<?php
/**
 * public/index.php
 * Punto de entrada único de la aplicación.
 */
declare(strict_types=1);

// --- 1. Configuración global ------------------------------------------
require dirname(__DIR__) . '/config/config.php';

// --- 2. Clases del núcleo ---------------------------------------------
require BASE_PATH . '/app/core/Database.php';
require BASE_PATH . '/app/core/Model.php';
require BASE_PATH . '/app/core/Controller.php';
require BASE_PATH . '/app/core/Router.php';
require BASE_PATH . '/app/core/Auth.php';

// --- 3. Definición de rutas -------------------------------------------
$router = new Router();

// Cliente público
$router->get('/',              ['ClienteController', 'inicio']);
$router->get('/carta',         ['ClienteController', 'carta']);

// Cliente con mesa (entrada por QR)
$router->get('/mesa/{token}',        ['MesaController', 'entrar']);
$router->get('/carta-mesa',          ['MesaController', 'cartaMesa']);
$router->get('/mi-mesa',             ['MesaController', 'miMesa']);
$router->get('/mi-mesa/estado.json', ['MesaController', 'estadoSesionJson']);

// Pedidos del cliente (AJAX)
$router->post('/pedido/anadir',     ['PedidoController', 'anadir']);
$router->post('/pedido/quitar',     ['PedidoController', 'quitar']);
$router->post('/pedido/confirmar',  ['PedidoController', 'confirmar']);

// Autenticación del personal
$router->get('/login',   ['AuthController', 'mostrarLogin']);
$router->post('/login',  ['AuthController', 'procesarLogin']);
$router->get('/logout',  ['AuthController', 'logout']);

// Panel de cocina
$router->get('/cocina',                ['CocinaController', 'panel']);
$router->get('/cocina/pedidos.json',   ['CocinaController', 'pedidosActivosJson']);
$router->post('/cocina/linea/estado',  ['CocinaController', 'actualizarEstadoLinea']);

// Panel de administrador — Dashboard
$router->get('/admin',  ['AdminController', 'dashboard']);

// Panel de administrador — Categorías
$router->get('/admin/categorias',                 ['AdminController', 'categorias']);
$router->get('/admin/categorias/nueva',           ['AdminController', 'nuevaCategoria']);
$router->post('/admin/categorias',                ['AdminController', 'crearCategoria']);
$router->get('/admin/categorias/{id}/editar',     ['AdminController', 'editarCategoria']);
$router->post('/admin/categorias/{id}',           ['AdminController', 'actualizarCategoria']);
$router->post('/admin/categorias/{id}/toggle',    ['AdminController', 'toggleCategoria']);

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
