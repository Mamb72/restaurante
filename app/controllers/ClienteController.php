<?php
/**
 * app/controllers/ClienteController.php
 * Controlador de las acciones accesibles al cliente/comensal.
 */

declare(strict_types=1);

final class ClienteController extends Controller
{
    public function inicio(array $params): void
    {
        $this->vista('cliente/bienvenida', [
            'titulo' => 'Restaurante — Bienvenido',
        ]);
    }

    /**
     * Muestra la carta pública: categorías + platos por categoría + destacados.
     */
    public function carta(array $params): void
    {
        require_once BASE_PATH . '/app/core/Model.php';
        require_once BASE_PATH . '/app/models/Categoria.php';
        require_once BASE_PATH . '/app/models/Plato.php';

        $categoriaModel = new Categoria();
        $platoModel     = new Plato();

        $categorias = $categoriaModel->obtenerTodasActivas();

        // Agrupar platos por categoría
        $platosPorCategoria = [];
        foreach ($categorias as $cat) {
            $platosPorCategoria[$cat['id']] = $platoModel->obtenerPorCategoria((int) $cat['id']);
        }

        $destacados = $platoModel->obtenerDestacados();

        $this->vista('cliente/carta', [
            'titulo'              => 'Carta del restaurante',
            'categorias'          => $categorias,
            'platosPorCategoria'  => $platosPorCategoria,
            'destacados'          => $destacados,
        ]);
    }
}
