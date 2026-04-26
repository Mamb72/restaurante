<?php
/**
 * app/controllers/AdminController.php
 * Controlador del panel de administración.
 *
 * Acceso restringido: solo usuarios con rol 'admin'.
 *
 * Métodos:
 *  - dashboard(): vista principal con tarjetas de resumen del día.
 *
 * En siguientes sub-bloques (7.2 → 7.7) se ampliará con los CRUDs
 * de categorías, platos, mesas, usuarios y el módulo de estadísticas.
 */

declare(strict_types=1);

final class AdminController extends Controller
{
    /**
     * GET /admin — dashboard con resumen operativo del día.
     */
    public function dashboard(array $params): void
    {
        Auth::exigirRol(['admin']);

        $usuario = Auth::usuarioActual();
        $db      = Database::getConnection();

        // 1. Pedidos activos: los que cocina aún tiene en cola.
        $sqlActivos = "
            SELECT COUNT(*) AS n
            FROM pedidos
            WHERE estado IN ('PENDIENTE', 'EN_PREPARACION', 'LISTO')
        ";
        $pedidosActivos = (int) $db->query($sqlActivos)->fetch()['n'];

        // 2. Mesas ocupadas: sesiones ABIERTA o PEDIDA_CUENTA.
        $sqlMesas = "
            SELECT COUNT(*) AS n
            FROM sesiones_mesa
            WHERE estado IN ('ABIERTA', 'PEDIDA_CUENTA')
        ";
        $mesasOcupadas = (int) $db->query($sqlMesas)->fetch()['n'];

        // 3. Facturación de hoy: suma de líneas no anuladas de pedidos
        //    creados en la fecha actual del servidor.
        $sqlFacturacion = "
            SELECT COALESCE(SUM(lp.cantidad * lp.precio_unitario), 0) AS total
            FROM lineas_pedido lp
            INNER JOIN pedidos p ON p.id = lp.pedido_id
            WHERE lp.estado != 'ANULADO'
              AND DATE(p.creado_en) = CURDATE()
        ";
        $facturacionHoy = (float) $db->query($sqlFacturacion)->fetch()['total'];

        // 4. Total de mesas dadas de alta (para contexto: "X de Y mesas").
        $sqlTotalMesas = "SELECT COUNT(*) AS n FROM mesas WHERE activa = TRUE";
        $totalMesas = (int) $db->query($sqlTotalMesas)->fetch()['n'];

        $this->vista('admin/dashboard', [
            'titulo'          => 'Dashboard',
            'usuario'         => $usuario,
            'seccion_activa'  => 'dashboard',
            'pedidos_activos' => $pedidosActivos,
            'mesas_ocupadas'  => $mesasOcupadas,
            'total_mesas'     => $totalMesas,
            'facturacion_hoy' => $facturacionHoy,
        ]);
    }
}
