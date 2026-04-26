<?php
/**
 * app/controllers/MesaController.php
 * Controlador de las acciones ligadas a una mesa concreta.
 */

declare(strict_types=1);

final class MesaController extends Controller
{
    /**
     * Recibe el token de una mesa, valida que existe, abre o recupera
     * la sesión activa, guarda los datos en la sesión PHP del navegador
     * y redirige a la carta de la mesa.
     */
    public function entrar(array $params): void
    {
        require_once BASE_PATH . '/app/models/Mesa.php';
        require_once BASE_PATH . '/app/models/SesionMesa.php';

        $token = $params['token'] ?? '';

        if (strlen($token) !== 32 || !ctype_xdigit($token)) {
            $this->vista('errores/mesa_no_valida', [
                'titulo'  => 'Mesa no encontrada',
                'mensaje' => 'El código QR no es válido. Por favor, avise a un camarero.',
            ]);
            return;
        }

        $mesaModel = new Mesa();
        $mesa = $mesaModel->obtenerPorToken($token);

        if ($mesa === null) {
            $this->vista('errores/mesa_no_valida', [
                'titulo'  => 'Mesa no encontrada',
                'mensaje' => 'Esta mesa no está disponible. Por favor, avise a un camarero.',
            ]);
            return;
        }

        $sesionMesaModel = new SesionMesa();
        $sesion = $sesionMesaModel->obtenerOAbrir((int) $mesa['id']);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['mesa_id']        = (int) $mesa['id'];
        $_SESSION['mesa_numero']    = (int) $mesa['numero'];
        $_SESSION['sesion_mesa_id'] = (int) $sesion['id'];

        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->redirigir('/carta-mesa');
    }

    /**
     * Muestra el estado completo de la mesa: carrito en curso,
     * historial de pedidos confirmados y total acumulado.
     */
    public function miMesa(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->redirigir('/');
            return;
        }

        require_once BASE_PATH . '/app/models/Pedido.php';
        require_once BASE_PATH . '/app/models/LineaPedido.php';

        $lineaModel = new LineaPedido();
        $sesionId   = (int) $_SESSION['sesion_mesa_id'];

        // Líneas ya confirmadas (en BD) de toda la sesión.
        $lineasConfirmadas = $lineaModel->obtenerPorSesion($sesionId);

        $totalConfirmado = 0.0;
        foreach ($lineasConfirmadas as $l) {
            $totalConfirmado += (float) $l['precio_unitario'] * (int) $l['cantidad'];
        }

        // Total del carrito en curso (aún sin confirmar).
        $totalCarrito = 0.0;
        $itemsCarrito = 0;
        foreach ($_SESSION['carrito'] ?? [] as $linea) {
            $totalCarrito += $linea['cantidad'] * $linea['snapshot']['precio'];
            $itemsCarrito += $linea['cantidad'];
        }

        $this->vista('cliente/mi_mesa', [
            'titulo'             => 'Mi mesa — Mesa ' . $_SESSION['mesa_numero'],
            'mesa_numero'        => $_SESSION['mesa_numero'],
            'sesion_mesa_id'     => $sesionId,
            'carrito'            => $_SESSION['carrito'] ?? [],
            'csrf_token'         => $_SESSION['csrf_token'] ?? '',
            'items_carrito'      => $itemsCarrito,
            'total_carrito'      => $totalCarrito,
            'lineas_confirmadas' => $lineasConfirmadas,
            'total_confirmado'   => $totalConfirmado,
        ]);
    }

    /**
     * Muestra la carta del restaurante con botones de añadir al pedido.
     * Solo accesible si hay sesión de mesa activa.
     */
    public function cartaMesa(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->redirigir('/');
            return;
        }

        require_once BASE_PATH . '/app/models/Categoria.php';
        require_once BASE_PATH . '/app/models/Plato.php';

        $categoriaModel = new Categoria();
        $platoModel     = new Plato();

        $categorias = $categoriaModel->obtenerTodasActivas();

        $platosPorCategoria = [];
        foreach ($categorias as $cat) {
            $platosPorCategoria[$cat['id']] = $platoModel->obtenerPorCategoria((int) $cat['id']);
        }

        $destacados = $platoModel->obtenerDestacados();

        $itemsCarrito = 0;
        foreach ($_SESSION['carrito'] ?? [] as $linea) {
            $itemsCarrito += $linea['cantidad'];
        }

        $this->vista('cliente/carta_mesa', [
            'titulo'             => 'Carta — Mesa ' . $_SESSION['mesa_numero'],
            'mesa_numero'        => $_SESSION['mesa_numero'],
            'categorias'         => $categorias,
            'platosPorCategoria' => $platosPorCategoria,
            'destacados'         => $destacados,
            'csrf_token'         => $_SESSION['csrf_token'] ?? '',
            'items_carrito'      => $itemsCarrito,
        ]);
    }

    /**
     * GET /mi-mesa/estado.json — endpoint AJAX de polling para el cliente.
     *
     * Devuelve solo los datos imprescindibles para refrescar los badges
     * de estado de las líneas confirmadas. No reenvía precios ni cantidades
     * porque esos datos no cambian una vez confirmado el pedido.
     *
     * Solo accesible si hay sesión de mesa activa: en caso contrario
     * responde 401 para que el JS deje de hacer polling.
     */
    public function estadoSesionJson(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->json(['ok' => false, 'error' => 'Sin sesión de mesa'], 401);
            return;
        }

        require_once BASE_PATH . '/app/models/LineaPedido.php';

        $lineaModel = new LineaPedido();
        $sesionId   = (int) $_SESSION['sesion_mesa_id'];

        $lineas = $lineaModel->obtenerPorSesion($sesionId);

        // Devolvemos un payload mínimo: lo que el JS necesita para
        // detectar cambios y actualizar la UI.
        $salida = array_map(static function (array $l): array {
            return [
                'id'           => (int)    $l['id'],
                'plato_nombre' => (string) $l['plato_nombre'],
                'estado'       => (string) $l['estado'],
            ];
        }, $lineas);

        $this->json([
            'ok'     => true,
            'lineas' => $salida,
            'ts'     => time(),
        ]);
    }
}
