<?php
/**
 * app/controllers/AuthController.php
 * Controlador de autenticación del personal (admin y cocina).
 *
 * Gestiona el inicio y cierre de sesión usando la sesión PHP nativa.
 * No incluye registro: los usuarios se crean por el administrador
 * desde el panel correspondiente o por SQL directo (datos demo).
 */

declare(strict_types=1);

require_once BASE_PATH . '/app/models/Usuario.php';

final class AuthController extends Controller
{
    /**
     * GET /login — muestra el formulario de acceso.
     * Si el usuario ya está autenticado, lo redirige al panel
     * que le corresponda según su rol.
     */
    public function mostrarLogin(array $params): void
    {
        $this->iniciarSesionPhp();

        if (isset($_SESSION['usuario_id'])) {
            $this->redirigirSegunRol($_SESSION['usuario_rol'] ?? '');
            return;
        }

        // Generamos un token CSRF si aún no existe
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->vista('auth/login', [
            'titulo'      => 'Acceso del personal',
            'csrf_token'  => $_SESSION['csrf_token'],
            'error'       => $_SESSION['login_error'] ?? null,
            'email_prev'  => $_SESSION['login_email_prev'] ?? '',
        ]);

        // Limpiamos los mensajes flash para que no se muestren dos veces
        unset($_SESSION['login_error'], $_SESSION['login_email_prev']);
    }

    /**
     * POST /login — procesa el formulario.
     * Valida CSRF, comprueba credenciales y crea la sesión PHP.
     */
    public function procesarLogin(array $params): void
    {
        $this->iniciarSesionPhp();

        // Validación CSRF
        $tokenEnviado    = $_POST['csrf_token']    ?? '';
        $tokenEsperado   = $_SESSION['csrf_token'] ?? '';
        if ($tokenEnviado === '' || !hash_equals($tokenEsperado, $tokenEnviado)) {
            $_SESSION['login_error'] = 'La sesión ha caducado. Inténtalo de nuevo.';
            $this->redirigir('/login');
            return;
        }

        $email    = trim((string) ($_POST['email']    ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['login_error']      = 'Introduce email y contraseña.';
            $_SESSION['login_email_prev'] = $email;
            $this->redirigir('/login');
            return;
        }

        $modelo  = new Usuario();
        $usuario = $modelo->verificarCredenciales($email, $password);

        if ($usuario === null) {
            $_SESSION['login_error']      = 'Email o contraseña incorrectos.';
            $_SESSION['login_email_prev'] = $email;
            $this->redirigir('/login');
            return;
        }

        // Login OK: regenerar id de sesión para evitar session fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id']     = (int) $usuario['id'];
        $_SESSION['usuario_nombre'] = $usuario['nombre'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['usuario_rol']    = $usuario['rol'];
        $_SESSION['csrf_token']     = bin2hex(random_bytes(32));

        $this->redirigirSegunRol($usuario['rol']);
    }

    /**
     * GET /logout — cierra la sesión y vuelve al login.
     */
    public function logout(array $params): void
    {
        $this->iniciarSesionPhp();

        // Vacía las variables y destruye la sesión completamente
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cookieParams = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $cookieParams['path'],
                $cookieParams['domain'],
                $cookieParams['secure'],
                $cookieParams['httponly']
            );
        }
        session_destroy();

        $this->redirigir('/login');
    }

    /**
     * Inicia la sesión PHP con cookies seguras si todavía no está activa.
     */
    private function iniciarSesionPhp(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                // 'secure' lo dejamos en false en local sin HTTPS;
                // en producción se activaría.
                'secure'   => false,
            ]);
            session_start();
        }
    }

    /**
     * Redirige al destino que corresponda según el rol del usuario.
     */
    private function redirigirSegunRol(string $rol): void
    {
        $destino = match ($rol) {
            'cocina' => '/cocina',
            'admin'  => '/admin',
            default  => '/login',
        };
        $this->redirigir($destino);
    }
}
