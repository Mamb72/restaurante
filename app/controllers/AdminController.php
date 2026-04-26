<?php
/**
 * app/controllers/AdminController.php
 * Controlador del panel de administración.
 *
 * Acceso restringido: solo usuarios con rol 'admin'.
 *
 * Métodos públicos:
 *  - dashboard():           vista principal con tarjetas de resumen.
 *  - categorias():          listado de categorías.
 *  - nuevaCategoria():      formulario de alta.
 *  - crearCategoria():      procesa el alta.
 *  - editarCategoria():     formulario de edición.
 *  - actualizarCategoria(): procesa la edición.
 *  - toggleCategoria():     activa/desactiva una categoría.
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/models/Categoria.php';

final class AdminController extends Controller
{
    // =====================================================================
    // DASHBOARD
    // =====================================================================

    /**
     * GET /admin — dashboard con resumen operativo del día.
     */
    public function dashboard(array $params): void
    {
        Auth::exigirRol(['admin']);

        $usuario = Auth::usuarioActual();
        $db      = Database::getConnection();

        $sqlActivos = "
            SELECT COUNT(*) AS n
            FROM pedidos
            WHERE estado IN ('PENDIENTE', 'EN_PREPARACION', 'LISTO')
        ";
        $pedidosActivos = (int) $db->query($sqlActivos)->fetch()['n'];

        $sqlMesas = "
            SELECT COUNT(*) AS n
            FROM sesiones_mesa
            WHERE estado IN ('ABIERTA', 'PEDIDA_CUENTA')
        ";
        $mesasOcupadas = (int) $db->query($sqlMesas)->fetch()['n'];

        $sqlFacturacion = "
            SELECT COALESCE(SUM(lp.cantidad * lp.precio_unitario), 0) AS total
            FROM lineas_pedido lp
            INNER JOIN pedidos p ON p.id = lp.pedido_id
            WHERE lp.estado != 'ANULADO'
              AND DATE(p.creado_en) = CURDATE()
        ";
        $facturacionHoy = (float) $db->query($sqlFacturacion)->fetch()['total'];

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
            'flash'           => $this->extraerFlash(),
        ]);
    }

    // =====================================================================
    // CRUD DE CATEGORÍAS
    // =====================================================================

    /**
     * GET /admin/categorias — listado completo.
     */
    public function categorias(array $params): void
    {
        Auth::exigirRol(['admin']);

        $modelo = new Categoria();
        $categorias = $modelo->obtenerTodas();

        // Para cada categoría añadimos su contador de platos.
        foreach ($categorias as &$cat) {
            $cat['platos_activos']  = $modelo->contarPlatosActivos((int) $cat['id']);
            $cat['platos_totales']  = $modelo->contarPlatosTotales((int) $cat['id']);
        }
        unset($cat);

        $this->vista('admin/categorias_listado', [
            'titulo'         => 'Categorías',
            'usuario'        => Auth::usuarioActual(),
            'seccion_activa' => 'categorias',
            'categorias'     => $categorias,
            'csrf_token'     => Auth::tokenCsrf(),
            'flash'          => $this->extraerFlash(),
        ]);
    }

    /**
     * GET /admin/categorias/nueva — formulario de alta.
     */
    public function nuevaCategoria(array $params): void
    {
        Auth::exigirRol(['admin']);

        $this->vista('admin/categorias_formulario', [
            'titulo'         => 'Nueva categoría',
            'usuario'        => Auth::usuarioActual(),
            'seccion_activa' => 'categorias',
            'modo'           => 'crear',
            'categoria'      => ['id' => null, 'nombre' => '', 'orden' => 0],
            'csrf_token'     => Auth::tokenCsrf(),
            'errores'        => [],
        ]);
    }

    /**
     * POST /admin/categorias — procesa el alta.
     */
    public function crearCategoria(array $params): void
    {
        Auth::exigirRol(['admin']);

        if (!Auth::verificarCsrf((string) ($_POST['csrf_token'] ?? ''))) {
            $this->ponerFlash('error', 'Token CSRF inválido. Vuelve a intentarlo.');
            $this->redirigir('/admin/categorias');
            return;
        }

        $datos   = $this->leerDatosCategoria();
        $errores = $this->validarCategoria($datos);

        if (!empty($errores)) {
            $this->vista('admin/categorias_formulario', [
                'titulo'         => 'Nueva categoría',
                'usuario'        => Auth::usuarioActual(),
                'seccion_activa' => 'categorias',
                'modo'           => 'crear',
                'categoria'      => array_merge(['id' => null], $datos),
                'csrf_token'     => Auth::tokenCsrf(),
                'errores'        => $errores,
            ]);
            return;
        }

        $modelo = new Categoria();
        $modelo->crear($datos['nombre'], $datos['orden']);

        $this->ponerFlash('ok', 'Categoría "' . $datos['nombre'] . '" creada.');
        $this->redirigir('/admin/categorias');
    }

    /**
     * GET /admin/categorias/{id}/editar — formulario de edición.
     */
    public function editarCategoria(array $params): void
    {
        Auth::exigirRol(['admin']);

        $id = (int) ($params['id'] ?? 0);
        $modelo = new Categoria();
        $categoria = $modelo->obtenerPorId($id);

        if ($categoria === null) {
            $this->ponerFlash('error', 'Categoría no encontrada.');
            $this->redirigir('/admin/categorias');
            return;
        }

        $this->vista('admin/categorias_formulario', [
            'titulo'         => 'Editar categoría',
            'usuario'        => Auth::usuarioActual(),
            'seccion_activa' => 'categorias',
            'modo'           => 'editar',
            'categoria'      => $categoria,
            'csrf_token'     => Auth::tokenCsrf(),
            'errores'        => [],
        ]);
    }

    /**
     * POST /admin/categorias/{id} — procesa la edición.
     */
    public function actualizarCategoria(array $params): void
    {
        Auth::exigirRol(['admin']);

        if (!Auth::verificarCsrf((string) ($_POST['csrf_token'] ?? ''))) {
            $this->ponerFlash('error', 'Token CSRF inválido.');
            $this->redirigir('/admin/categorias');
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $modelo = new Categoria();
        $categoria = $modelo->obtenerPorId($id);

        if ($categoria === null) {
            $this->ponerFlash('error', 'Categoría no encontrada.');
            $this->redirigir('/admin/categorias');
            return;
        }

        $datos   = $this->leerDatosCategoria();
        $errores = $this->validarCategoria($datos);

        if (!empty($errores)) {
            $this->vista('admin/categorias_formulario', [
                'titulo'         => 'Editar categoría',
                'usuario'        => Auth::usuarioActual(),
                'seccion_activa' => 'categorias',
                'modo'           => 'editar',
                'categoria'      => array_merge($categoria, $datos),
                'csrf_token'     => Auth::tokenCsrf(),
                'errores'        => $errores,
            ]);
            return;
        }

        $modelo->actualizar($id, $datos['nombre'], $datos['orden']);

        $this->ponerFlash('ok', 'Categoría actualizada.');
        $this->redirigir('/admin/categorias');
    }

    /**
     * POST /admin/categorias/{id}/toggle — activa o desactiva la categoría.
     *
     * Regla de negocio: no se permite desactivar una categoría que tenga
     * platos activos asociados. Hay que retirar o reasignar los platos
     * primero.
     */
    public function toggleCategoria(array $params): void
    {
        Auth::exigirRol(['admin']);

        if (!Auth::verificarCsrf((string) ($_POST['csrf_token'] ?? ''))) {
            $this->ponerFlash('error', 'Token CSRF inválido.');
            $this->redirigir('/admin/categorias');
            return;
        }

        $id = (int) ($params['id'] ?? 0);
        $modelo = new Categoria();
        $categoria = $modelo->obtenerPorId($id);

        if ($categoria === null) {
            $this->ponerFlash('error', 'Categoría no encontrada.');
            $this->redirigir('/admin/categorias');
            return;
        }

        $estabaActiva = (bool) $categoria['activa'];

        // Si está activa y queremos desactivarla, comprobar platos.
        if ($estabaActiva) {
            $platosActivos = $modelo->contarPlatosActivos($id);
            if ($platosActivos > 0) {
                $this->ponerFlash(
                    'error',
                    'No se puede desactivar "' . $categoria['nombre']
                    . '": tiene ' . $platosActivos . ' plato(s) activo(s) asociado(s).'
                );
                $this->redirigir('/admin/categorias');
                return;
            }
        }

        $modelo->cambiarEstado($id, !$estabaActiva);

        $msg = $estabaActiva
            ? 'Categoría "' . $categoria['nombre'] . '" desactivada.'
            : 'Categoría "' . $categoria['nombre'] . '" reactivada.';
        $this->ponerFlash('ok', $msg);
        $this->redirigir('/admin/categorias');
    }

    // =====================================================================
    // HELPERS PRIVADOS
    // =====================================================================

    /**
     * Lee los datos del formulario de categoría desde $_POST.
     *
     * @return array{nombre:string, orden:int}
     */
    private function leerDatosCategoria(): array
    {
        return [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'orden'  => (int)        ($_POST['orden']  ?? 0),
        ];
    }

    /**
     * Valida los datos de una categoría.
     * Devuelve un array asociativo campo => mensaje de error.
     * Si está vacío, los datos son válidos.
     *
     * @param array{nombre:string, orden:int} $datos
     * @return array<string, string>
     */
    private function validarCategoria(array $datos): array
    {
        $errores = [];

        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($datos['nombre']) > 80) {
            $errores['nombre'] = 'El nombre no puede pasar de 80 caracteres.';
        }

        if ($datos['orden'] < 0 || $datos['orden'] > 999) {
            $errores['orden'] = 'El orden debe estar entre 0 y 999.';
        }

        return $errores;
    }

    /**
     * Pone un mensaje flash en la sesión, que se mostrará una vez en la
     * siguiente vista renderizada.
     *
     * @param 'ok'|'error' $tipo
     */
    private function ponerFlash(string $tipo, string $mensaje): void
    {
        Auth::iniciarSesion();
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }

    /**
     * Lee y elimina el mensaje flash de la sesión.
     *
     * @return array{tipo:string, mensaje:string}|null
     */
    private function extraerFlash(): ?array
    {
        Auth::iniciarSesion();
        if (!isset($_SESSION['flash'])) {
            return null;
        }
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
}
