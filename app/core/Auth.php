<?php
/**
 * app/core/Auth.php
 * Helper de autenticación para controladores protegidos.
 *
 * Centraliza la comprobación de sesión activa y de rol del personal,
 * para no repetir esa lógica en cada controlador.
 *
 * Convención: las claves de $_SESSION se llaman 'usuario_id',
 * 'usuario_nombre', 'usuario_email' y 'usuario_rol' (las pone
 * AuthController al iniciar sesión).
 */

declare(strict_types=1);

final class Auth
{
    /**
     * Inicia la sesión PHP si todavía no está activa, con cookies seguras.
     * Mismas opciones que AuthController para que la cookie sea coherente.
     */
    public static function iniciarSesion(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure'   => false,
            ]);
            session_start();
        }
    }

    /**
     * ¿Hay un usuario autenticado en esta petición?
     */
    public static function estaAutenticado(): bool
    {
        self::iniciarSesion();
        return isset($_SESSION['usuario_id']);
    }

    /**
     * Devuelve los datos del usuario en sesión, o null si no hay sesión.
     */
    public static function usuarioActual(): ?array
    {
        if (!self::estaAutenticado()) {
            return null;
        }
        return [
            'id'     => (int) $_SESSION['usuario_id'],
            'nombre' => (string) ($_SESSION['usuario_nombre'] ?? ''),
            'email'  => (string) ($_SESSION['usuario_email']  ?? ''),
            'rol'    => (string) ($_SESSION['usuario_rol']    ?? ''),
        ];
    }

    /**
     * Exige que el usuario esté autenticado y tenga uno de los roles dados.
     * Si no, redirige a /login y termina la ejecución.
     *
     * @param array<int,string> $rolesPermitidos  Ej: ['cocina','admin']
     */
    public static function exigirRol(array $rolesPermitidos): void
    {
        self::iniciarSesion();

        if (!isset($_SESSION['usuario_id'])) {
            header('Location: ' . BASE_URL . '/login');
            exit;
        }

        $rol = (string) ($_SESSION['usuario_rol'] ?? '');
        if (!in_array($rol, $rolesPermitidos, true)) {
            // Usuario logueado pero con rol incorrecto: 403.
            http_response_code(403);
            echo '<h1>403 — Acceso denegado</h1>';
            echo '<p>Tu rol no tiene permiso para acceder a esta sección.</p>';
            exit;
        }
    }

    /**
     * Devuelve el token CSRF actual de la sesión, generándolo si no existe.
     */
    public static function tokenCsrf(): string
    {
        self::iniciarSesion();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Verifica un token CSRF enviado contra el de la sesión.
     */
    public static function verificarCsrf(string $tokenEnviado): bool
    {
        self::iniciarSesion();
        $tokenEsperado = $_SESSION['csrf_token'] ?? '';
        if ($tokenEsperado === '' || $tokenEnviado === '') {
            return false;
        }
        return hash_equals($tokenEsperado, $tokenEnviado);
    }
}
