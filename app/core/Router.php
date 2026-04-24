<?php
/**
 * app/core/Router.php
 * Sistema de rutas de la aplicación.
 *
 * Permite registrar rutas GET/POST y resolverlas contra la URL actual.
 * Soporta parámetros dinámicos con llaves: /mesa/{token}/plato/{id}
 */

declare(strict_types=1);

final class Router
{
    /** @var array<int, array{method: string, pattern: string, handler: array{string, string}}> */
    private array $rutas = [];

    /**
     * Registra una ruta GET.
     * @param string $pattern Patrón de URL, ej: /mesa/{token}
     * @param array{string, string} $handler [NombreControlador, metodo]
     */
    public function get(string $pattern, array $handler): void
    {
        $this->registrar('GET', $pattern, $handler);
    }

    /**
     * Registra una ruta POST.
     */
    public function post(string $pattern, array $handler): void
    {
        $this->registrar('POST', $pattern, $handler);
    }

    private function registrar(string $method, string $pattern, array $handler): void
    {
        $this->rutas[] = [
            'method'  => $method,
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    /**
     * Busca una ruta que coincida con la URL actual y la ejecuta.
     */
    public function resolver(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri    = rtrim($uri, '/') ?: '/';

        foreach ($this->rutas as $ruta) {
            if ($ruta['method'] !== $method) {
                continue;
            }

            $regex = $this->patronARegex($ruta['pattern']);

            if (preg_match($regex, $uri, $matches)) {
                // Los parámetros dinámicos llegan como matches nombrados
                $params = array_filter(
                    $matches,
                    fn($k) => !is_numeric($k),
                    ARRAY_FILTER_USE_KEY
                );

                [$controlador, $accion] = $ruta['handler'];
                $this->ejecutar($controlador, $accion, $params);
                return;
            }
        }

        // Ninguna ruta coincide: 404
        $this->respuesta404();
    }

    /**
     * Convierte un patrón con {llaves} en una regex con grupos nombrados.
     * Ej: /mesa/{token}  ->  #^/mesa/(?P<token>[^/]+)$#
     */
    private function patronARegex(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }

    /**
     * Carga el controlador, lo instancia y llama al método correspondiente.
     */
    private function ejecutar(string $nombreControlador, string $accion, array $params): void
    {
        $ruta = BASE_PATH . '/app/controllers/' . $nombreControlador . '.php';

        if (!is_file($ruta)) {
            throw new RuntimeException("Controlador no encontrado: $nombreControlador");
        }

        require_once $ruta;

        if (!class_exists($nombreControlador)) {
            throw new RuntimeException("La clase $nombreControlador no existe en $ruta");
        }

        $controlador = new $nombreControlador();

        if (!method_exists($controlador, $accion)) {
            throw new RuntimeException("El método $accion no existe en $nombreControlador");
        }

        $controlador->$accion($params);
    }

    private function respuesta404(): void
    {
        http_response_code(404);
        echo '<h1>404 — Página no encontrada</h1>';
        echo '<p>La URL solicitada no existe en este sistema.</p>';
        echo '<p><a href="' . BASE_URL . '">Volver al inicio</a></p>';
    }
}

