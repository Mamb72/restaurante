<?php
/**
 * app/core/Controller.php
 * Clase base de la que heredan todos los controladores.
 *
 * Ofrece utilidades comunes: renderizar vistas, redirigir,
 * responder JSON, etc.
 */

declare(strict_types=1);

abstract class Controller
{
    /**
     * Renderiza una vista PHP desde app/views/.
     *
     * @param string $vista  Ruta de la vista relativa a app/views/ (sin .php)
     *                       Ej: 'cliente/carta'
     * @param array  $datos  Variables que se exponen a la vista.
     */
    protected function vista(string $vista, array $datos = []): void
    {
        $ruta = BASE_PATH . '/app/views/' . $vista . '.php';

        if (!is_file($ruta)) {
            throw new RuntimeException("Vista no encontrada: $vista");
        }

        // Expone cada clave de $datos como variable en la vista
        extract($datos, EXTR_SKIP);

        require $ruta;
    }

    /**
     * Redirige a otra URL y termina la ejecución.
     */
    protected function redirigir(string $ruta): void
    {
        $destino = str_starts_with($ruta, 'http') ? $ruta : BASE_URL . $ruta;
        header('Location: ' . $destino);
        exit;
    }

    /**
     * Responde con JSON y termina la ejecución.
     */
    protected function json(mixed $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
