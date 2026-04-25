<?php
/**
 * app/controllers/CocinaController.php
 * Controlador del panel de cocina.
 *
 * Muestra los pedidos activos agrupados por mesa y permite cambiar
 * el estado de cada línea individualmente. En 6.5.b se añadirán los
 * endpoints AJAX para polling y actualización en tiempo real.
 *
 * Acceso restringido: solo usuarios con rol 'cocina' o 'admin'.
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/models/Pedido.php';
require_once BASE_PATH . '/app/models/LineaPedido.php';

final class CocinaController extends Controller
{
    /**
     * GET /cocina — panel principal con todos los pedidos activos.
     */
    public function panel(array $params): void
    {
        // Middleware de auth: solo cocina y admin.
        Auth::exigirRol(['cocina', 'admin']);

        $usuario = Auth::usuarioActual();

        $modeloPedido = new Pedido();
        $modeloLinea  = new LineaPedido();

        // Traemos los pedidos activos y, para cada uno, sus líneas.
        $pedidos = $modeloPedido->obtenerActivosParaCocina();

        foreach ($pedidos as &$pedido) {
            $pedido['lineas'] = $modeloLinea->obtenerPorPedido((int) $pedido['id']);
        }
        unset($pedido); // Buena práctica al salir de un foreach por referencia.

        $this->vista('cocina/panel', [
            'titulo'     => 'Panel de cocina',
            'usuario'    => $usuario,
            'pedidos'    => $pedidos,
            'csrf_token' => Auth::tokenCsrf(),
        ]);
    }
}
