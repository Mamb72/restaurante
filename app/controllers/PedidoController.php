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
     *
     * Body esperado (form-urlencoded):
     *   plato_id    int   (obligatorio)
     *   cantidad    int   (opcional, default 1)
     *   csrf_token  string (obligatorio)
     *
     * Respuesta JSON:
     *   { ok: bool, error?: string, items_carrito?: int, total?: string }
     */
    public function anadir(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // 1. Verificar que hay sesión de mesa activa
        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->json(['ok' => false, 'error' => 'No hay sesión de mesa activa.'], 401);
            return;
        }

        // 2. Verificar token CSRF
        $csrfRecibido = $_POST['csrf_token'] ?? '';
        $csrfEsperado = $_SESSION['csrf_token'] ?? '';

        if ($csrfEsperado === '' || !hash_equals($csrfEsperado, $csrfRecibido)) {
            $this->json(['ok' => false, 'error' => 'Token de seguridad inválido.'], 403);
            return;
        }

        // 3. Validar plato_id
        $platoId = filter_input(INPUT_POST, 'plato_id', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($platoId === false || $platoId === null) {
            $this->json(['ok' => false, 'error' => 'Plato no válido.'], 400);
            return;
        }

        // 4. Validar cantidad (default 1, máximo 20 por seguridad)
        $cantidad = filter_input(INPUT_POST, 'cantidad', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 20, 'default' => 1],
        ]);
        if ($cantidad === false || $cantidad === null) {
            $cantidad = 1;
        }

        // 5. Verificar que el plato existe, está activo y disponible
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

        // 6. Añadir al carrito en sesión
        // Estructura: $_SESSION['carrito'][plato_id] = ['cantidad' => N, 'snapshot' => [...]]
        // Guardamos snapshot para que aunque cambie el plato en BD, el carrito mantenga
        // el precio y nombre que vio el cliente al añadirlo (defensa contra cambios en BD).
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

        // 7. Calcular totales y devolver
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
}
