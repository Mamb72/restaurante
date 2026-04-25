<?php
/**
 * app/controllers/PedidoController.php
 * Endpoints AJAX para gestionar el carrito y el pedido de la mesa.
 *
 * Todas las acciones esperan POST y devuelven JSON.
 * Requieren sesión de mesa activa y token CSRF válido.
 */

declare(strict_types=1);

final class PedidoController extends Controller
{
    /**
     * POST /pedido/anadir
     */
    public function anadir(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->json(['ok' => false, 'error' => 'No hay sesión de mesa activa.'], 401);
            return;
        }

        $csrfRecibido = $_POST['csrf_token'] ?? '';
        $csrfEsperado = $_SESSION['csrf_token'] ?? '';
        if ($csrfEsperado === '' || !hash_equals($csrfEsperado, $csrfRecibido)) {
            $this->json(['ok' => false, 'error' => 'Token de seguridad inválido.'], 403);
            return;
        }

        $platoId = filter_input(INPUT_POST, 'plato_id', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($platoId === false || $platoId === null) {
            $this->json(['ok' => false, 'error' => 'Plato no válido.'], 400);
            return;
        }

        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 20, 'default' => 1],
        ]);
        if ($cantidad === false || $cantidad === null) {
            $cantidad = 1;
        }

        require_once BASE_PATH . '/app/core/Model.php';
        require_once BASE_PATH . '/app/models/Plato.php';

        $platoModel = new Plato();
        $plato = $platoModel->obtenerPorId($platoId);

        if ($plato === null) {
            $this->json(['ok' => false, 'error' => 'El plato no existe.'], 404);
            return;
        }
        if (!$plato['activo'] || !$plato['disponible']) {
            $this->json(['ok' => false, 'error' => 'Este plato no está disponible.'], 409);
            return;
        }

        if (!isset($_SESSION['carrito'][$platoId])) {
            $_SESSION['carrito'][$platoId] = [
                'cantidad' => 0,
                'snapshot' => [
                    'id'     => (int) $plato['id'],
                    'nombre' => (string) $plato['nombre'],
                    'precio' => (float) $plato['precio'],
                ],
            ];
        }

        $_SESSION['carrito'][$platoId]['cantidad'] += $cantidad;

        $itemsTotal = 0;
        $importeTotal = 0.0;
        foreach ($_SESSION['carrito'] as $linea) {
            $itemsTotal   += $linea['cantidad'];
            $importeTotal += $linea['cantidad'] * $linea['snapshot']['precio'];
        }

        $this->json([
            'ok'             => true,
            'items_carrito'  => $itemsTotal,
            'total'          => number_format($importeTotal, 2, ',', '.') . ' €',
            'plato_anadido'  => $plato['nombre'],
        ]);
    }

    /**
     * POST /pedido/quitar
     * Quita 1 unidad del plato indicado del carrito en sesión.
     * Si la cantidad llega a 0, elimina la línea entera del carrito.
     */
    public function quitar(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->json(['ok' => false, 'error' => 'No hay sesión de mesa activa.'], 401);
            return;
        }

        $csrfRecibido = $_POST['csrf_token'] ?? '';
        $csrfEsperado = $_SESSION['csrf_token'] ?? '';
        if ($csrfEsperado === '' || !hash_equals($csrfEsperado, $csrfRecibido)) {
            $this->json(['ok' => false, 'error' => 'Token de seguridad inválido.'], 403);
            return;
        }

        $platoId = filter_input(INPUT_POST, 'plato_id', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($platoId === false || $platoId === null) {
            $this->json(['ok' => false, 'error' => 'Plato no válido.'], 400);
            return;
        }

        if (!isset($_SESSION['carrito'][$platoId])) {
            $this->json(['ok' => false, 'error' => 'Ese plato no está en el carrito.'], 404);
            return;
        }

        $_SESSION['carrito'][$platoId]['cantidad']--;
        if ($_SESSION['carrito'][$platoId]['cantidad'] <= 0) {
            unset($_SESSION['carrito'][$platoId]);
        }

        $itemsTotal = 0;
        $importeTotal = 0.0;
        foreach ($_SESSION['carrito'] as $linea) {
            $itemsTotal   += $linea['cantidad'];
            $importeTotal += $linea['cantidad'] * $linea['snapshot']['precio'];
        }

        $this->json([
            'ok'            => true,
            'items_carrito' => $itemsTotal,
            'total'         => number_format($importeTotal, 2, ',', '.') . ' €',
        ]);
    }

    /**
     * POST /pedido/confirmar
     * Convierte el carrito de la sesión en un pedido + líneas en BD.
     * Vacía el carrito tras éxito. Usa transacción atómica.
     */
    public function confirmar(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->json(['ok' => false, 'error' => 'No hay sesión de mesa activa.'], 401);
            return;
        }

        $csrfRecibido = $_POST['csrf_token'] ?? '';
        $csrfEsperado = $_SESSION['csrf_token'] ?? '';
        if ($csrfEsperado === '' || !hash_equals($csrfEsperado, $csrfRecibido)) {
            $this->json(['ok' => false, 'error' => 'Token de seguridad inválido.'], 403);
            return;
        }

        if (empty($_SESSION['carrito'])) {
            $this->json(['ok' => false, 'error' => 'El carrito está vacío.'], 400);
            return;
        }

        require_once BASE_PATH . '/app/core/Model.php';
        require_once BASE_PATH . '/app/models/Pedido.php';
        require_once BASE_PATH . '/app/models/LineaPedido.php';

        $pedidoModel = new Pedido();
        $lineaModel  = new LineaPedido();
        $sesionId    = (int) $_SESSION['sesion_mesa_id'];

        // Database es Singleton: ambos modelos comparten conexión PDO,
        // por lo que la transacción cubre las inserciones de ambos.
        $pdo = Database::getConnection();

        try {
            $pdo->beginTransaction();

            $pedidoId = $pedidoModel->crear($sesionId);

            $lineasCreadas = 0;
            foreach ($_SESSION['carrito'] as $platoId => $linea) {
                $lineaModel->crear(
                    $pedidoId,
                    (int) $platoId,
                    (int) $linea['cantidad'],
                    (float) $linea['snapshot']['precio']
                );
                $lineasCreadas++;
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Error al confirmar pedido: ' . $e->getMessage());
            $this->json([
                'ok'    => false,
                'error' => 'No se pudo registrar el pedido. Inténtelo de nuevo.',
            ], 500);
            return;
        }

        // Limpiar carrito tras éxito.
        $_SESSION['carrito'] = [];

        $this->json([
            'ok'        => true,
            'pedido_id' => $pedidoId,
            'lineas'    => $lineasCreadas,
            'mensaje'   => 'Pedido enviado a cocina.',
        ]);
    }
}
