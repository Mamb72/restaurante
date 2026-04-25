<?php
/**
 * app/controllers/MesaController.php
 * Controlador de las acciones ligadas a una mesa concreta.
 *
 * Punto de entrada del cliente: escanea el QR pegado en la mesa,
 * el navegador abre /mesa/{token}, y desde aquí se gestiona todo.
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
        require_once BASE_PATH . '/app/core/Model.php';
        require_once BASE_PATH . '/app/models/Mesa.php';
        require_once BASE_PATH . '/app/models/SesionMesa.php';

        $token = $params['token'] ?? '';

        // Validación básica del formato del token
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

        // Abrir o recuperar sesión activa
        $sesionMesaModel = new SesionMesa();
        $sesion = $sesionMesaModel->obtenerOAbrir((int) $mesa['id']);

        // Iniciar sesión PHP del navegador si aún no está iniciada
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Guardar datos de la mesa en la sesión PHP del navegador
        $_SESSION['mesa_id']        = (int) $mesa['id'];
        $_SESSION['mesa_numero']    = (int) $mesa['numero'];
        $_SESSION['sesion_mesa_id'] = (int) $sesion['id'];

        // Inicializar carrito vacío si no existe
        if (!isset($_SESSION['carrito'])) {
            $_SESSION['carrito'] = [];
        }
	// Generar token CSRF si no existe (válido durante toda la sesión de mesa)
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        // Redirigir a la página de bienvenida de mesa
        $this->redirigir('/mi-mesa');
    }

    /**
     * Vista temporal para esta fase: muestra que la sesión está abierta.
     * En la fase 6.4.6 esto mostrará el pedido en curso.
     */
    public function miMesa(array $params): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Si no hay sesión de mesa activa, redirigir a la home
        if (!isset($_SESSION['sesion_mesa_id'])) {
            $this->redirigir('/');
            return;
        }

        $this->vista('cliente/mi_mesa', [
            'titulo'         => 'Mi mesa',
            'mesa_numero'    => $_SESSION['mesa_numero'],
            'sesion_mesa_id' => $_SESSION['sesion_mesa_id'],
            'carrito'        => $_SESSION['carrito'] ?? [],
        ]);
    }
}
