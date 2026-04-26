<?php
/**
 * app/controllers/CocinaController.php
 * Controlador del panel de cocina.
 *
 * Acceso restringido: solo usuarios con rol 'cocina' o 'admin'.
 *
 * Métodos:
 *  - panel():                vista HTML del panel.
 *  - pedidosActivosJson():   endpoint AJAX para polling.
 *  - actualizarEstadoLinea(): endpoint AJAX para cambiar estado.
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/models/Pedido.php';
require_once BASE_PATH . '/app/models/LineaPedido.php';

final class CocinaController extends Controller
{
    /** Estados que cocina puede asignar a una línea. */
    private const ESTADOS_COCINA = ['EN_PREPARACION', 'LISTO', 'SERVIDO'];

    /**
     * GET /cocina — panel principal con todos los pedidos activos.
     */
    public function panel(array $params): void
    {
        Auth::exigirRol(['cocina', 'admin']);

        $usuario = Auth::usuarioActual();

        $pedidos = $this->cargarPedidosActivos();

        $this->vista('cocina/panel', [
            'titulo'     => 'Panel de cocina',
            'usuario'    => $usuario,
            'pedidos'    => $pedidos,
            'csrf_token' => Auth::tokenCsrf(),
        ]);
    }

    /**
     * GET /cocina/pedidos.json — endpoint de polling.
     * Devuelve los pedidos activos en JSON. El JS del panel lo
     * consulta cada pocos segundos para refrescar la vista.
     */
    public function pedidosActivosJson(array $params): void
    {
        Auth::exigirRol(['cocina', 'admin']);

        $pedidos = $this->cargarPedidosActivos();

        // Normalizamos los datos para JSON: aseguramos enteros y limpiamos.
        $salida = array_map(function (array $p): array {
            return [
                'id'          => (int) $p['id'],
                'mesa_numero' => (int) $p['mesa_numero'],
                'creado_en'   => (string) $p['creado_en'],
                'hora'        => date('H:i', strtotime((string) $p['creado_en'])),
                'estado'      => (string) $p['estado'],
                'lineas'      => array_map(function (array $l): array {
                    return [
                        'id'           => (int) $l['id'],
                        'plato_nombre' => (string) $l['plato_nombre'],
                        'cantidad'     => (int) $l['cantidad'],
                        'nota'         => $l['nota'] !== null ? (string) $l['nota'] : null,
                        'estado'       => (string) $l['estado'],
                    ];
                }, $p['lineas']),
            ];
        }, $pedidos);

        $this->json([
            'ok'      => true,
            'pedidos' => $salida,
            'ts'      => time(),
        ]);
    }

    /**
     * POST /cocina/linea/estado — cambia el estado de una línea.
     *
     * Recibe (form-urlencoded o JSON):
     *  - csrf_token
     *  - linea_id
     *  - nuevo_estado (EN_PREPARACION | LISTO | SERVIDO)
     *
     * Reglas:
     *  - El estado debe ser uno de los permitidos a cocina.
     *  - No se permite "retroceder" a PENDIENTE desde aquí.
     *  - Si todas las líneas del pedido pasan a SERVIDO, el pedido
     *    también se marca SERVIDO.
     *  - Si hay alguna línea en LISTO y ninguna en PENDIENTE/EN_PREPARACION,
     *    el pedido se marca LISTO.
     *  - Si alguna línea está EN_PREPARACION, el pedido se marca EN_PREPARACION.
     */
    public function actualizarEstadoLinea(array $params): void
    {
        Auth::exigirRol(['cocina', 'admin']);

        // Aceptamos tanto form-urlencoded como JSON.
        $datos = $this->leerEntradaPost();

        $tokenEnviado = (string) ($datos['csrf_token'] ?? '');
        if (!Auth::verificarCsrf($tokenEnviado)) {
            $this->json(['ok' => false, 'error' => 'Token CSRF inválido'], 403);
            return;
        }

        $lineaId      = (int)    ($datos['linea_id']      ?? 0);
        $nuevoEstado  = (string) ($datos['nuevo_estado']  ?? '');

        if ($lineaId <= 0) {
            $this->json(['ok' => false, 'error' => 'ID de línea inválido'], 400);
            return;
        }
        if (!in_array($nuevoEstado, self::ESTADOS_COCINA, true)) {
            $this->json(['ok' => false, 'error' => 'Estado no permitido'], 400);
            return;
        }

        $modeloLinea  = new LineaPedido();
        $modeloPedido = new Pedido();

        try {
            // 1. Cambiamos el estado de la línea.
            $modeloLinea->cambiarEstado($lineaId, $nuevoEstado);

            // 2. Recuperamos a qué pedido pertenece para recalcular el estado global.
            $sql = 'SELECT pedido_id FROM lineas_pedido WHERE id = :id LIMIT 1';
            $stmt = Database::getConnection()->prepare($sql);
            $stmt->execute(['id' => $lineaId]);
            $fila = $stmt->fetch();

            if ($fila === false) {
                $this->json(['ok' => false, 'error' => 'Línea no encontrada'], 404);
                return;
            }

            $pedidoId = (int) $fila['pedido_id'];

            // 3. Recalculamos el estado del pedido en función de sus líneas.
            $this->recalcularEstadoPedido($pedidoId, $modeloLinea, $modeloPedido);

            $this->json(['ok' => true]);
        } catch (Throwable $e) {
            error_log('[CocinaController::actualizarEstadoLinea] ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Error interno'], 500);
        }
    }

    /**
     * Carga los pedidos activos con sus líneas anidadas.
     *
     * @return array<int, array<string, mixed>>
     */
    private function cargarPedidosActivos(): array
    {
        $modeloPedido = new Pedido();
        $modeloLinea  = new LineaPedido();

        $pedidos = $modeloPedido->obtenerActivosParaCocina();
        foreach ($pedidos as &$pedido) {
            $pedido['lineas'] = $modeloLinea->obtenerPorPedido((int) $pedido['id']);
        }
        unset($pedido);

        return $pedidos;
    }

    /**
     * Lee la entrada POST aceptando JSON o form-urlencoded.
     *
     * @return array<string, mixed>
     */
    private function leerEntradaPost(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $crudo = file_get_contents('php://input') ?: '';
            $datos = json_decode($crudo, true);
            return is_array($datos) ? $datos : [];
        }
        return $_POST;
    }

    /**
     * Recalcula el estado del pedido a partir del estado de sus líneas.
     */
    private function recalcularEstadoPedido(
        int $pedidoId,
        LineaPedido $modeloLinea,
        Pedido $modeloPedido
    ): void {
        $sql = '
            SELECT estado, COUNT(*) AS cuenta
            FROM lineas_pedido
            WHERE pedido_id = :pedido_id
            GROUP BY estado
        ';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute(['pedido_id' => $pedidoId]);
        $contadores = [];
        foreach ($stmt->fetchAll() as $fila) {
            $contadores[(string) $fila['estado']] = (int) $fila['cuenta'];
        }

        $hayPendientes  = ($contadores['PENDIENTE']      ?? 0) > 0;
        $hayPreparando  = ($contadores['EN_PREPARACION'] ?? 0) > 0;
        $hayListos      = ($contadores['LISTO']          ?? 0) > 0;
        $totalNoAnulado =
            ($contadores['PENDIENTE']      ?? 0) +
            ($contadores['EN_PREPARACION'] ?? 0) +
            ($contadores['LISTO']          ?? 0) +
            ($contadores['SERVIDO']        ?? 0);
        $totalServido   = $contadores['SERVIDO'] ?? 0;

        if ($totalNoAnulado === 0) {
            return; // pedido completamente anulado
        }

        if ($totalServido === $totalNoAnulado) {
            $nuevoEstado = 'SERVIDO';
        } elseif ($hayPendientes) {
            $nuevoEstado = $hayPreparando ? 'EN_PREPARACION' : 'PENDIENTE';
        } elseif ($hayPreparando) {
            $nuevoEstado = 'EN_PREPARACION';
        } elseif ($hayListos) {
            $nuevoEstado = 'LISTO';
        } else {
            return;
        }

        $modeloPedido->cambiarEstado($pedidoId, $nuevoEstado);
    }
}
