<?php
/**
 * app/models/Categoria.php
 * Modelo de la tabla `categorias`.
 */

declare(strict_types=1);

final class Categoria extends Model
{
    /**
     * Devuelve todas las categorías activas, ordenadas por su campo `orden`.
     *
     * @return array<int, array{id:int, nombre:string, orden:int}>
     */
    public function obtenerTodasActivas(): array
    {
        $sql = '
            SELECT id, nombre, orden
            FROM categorias
            WHERE activa = TRUE
            ORDER BY orden ASC, nombre ASC
        ';

        return $this->db->query($sql)->fetchAll();
    }
}
