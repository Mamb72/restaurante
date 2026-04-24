<?php
/**
 * app/controllers/ClienteController.php
 * Controlador de las acciones accesibles al cliente/comensal.
 */

declare(strict_types=1);

final class ClienteController extends Controller
{
    /**
     * Página de inicio genérica.
     * En una instalación real, si el cliente llega aquí sin QR,
     * se le muestra una pantalla de bienvenida con las instrucciones.
     */
    public function inicio(array $params): void
    {
        $this->vista('cliente/bienvenida', [
            'titulo' => 'Restaurante — Bienvenido',
        ]);
    }
}
