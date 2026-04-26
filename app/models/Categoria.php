<?php
/**
 * app/models/Categoria.php
 * Modelo de la tabla `categorias`.
 *
 * Una categoría agrupa platos en la carta (ej: Entrantes, Bebidas, Postres).
 * Tiene un campo `orden` para forzar el orden de aparición y un flag
 * `activa` para soft-delete (no se borra para preservar integridad
 * referencial con platos históricos).
 */

declare(strict_types=1);

final class Categoria extends Model
{
    /**
     * Devuelve todas las categorías ACTIVAS, ordenadas por `orden` y nombre.
     * Lo usa la carta pública del cliente.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodasActivas(): array
    {
        $sql = '
            SELECT id, nombre, orden, activa
            FROM categorias
            WHERE activa = TRUE
            ORDER BY orden ASC, nombre ASC
        ';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Devuelve TODAS las categorías (activas e inactivas).
     * Lo usa el panel de administración.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodas(): array
    {
        $sql = '
            SELECT id, nombre, orden, activa
            FROM categorias
            ORDER BY activa DESC, orden ASC, nombre ASC
        ';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Busca una categoría por su id (activa o no).
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = '
            SELECT id, nombre, orden, activa
            FROM categorias
            WHERE id = :id
            LIMIT 1
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila === false ? null : $fila;
    }

    /**
     * Crea una nueva categoría. Devuelve el id generado.
     */
    public function crear(string $nombre, int $orden): int
    {
        $sql = '
            INSERT INTO categorias (nombre, orden, activa)
            VALUES (:nombre, :orden, TRUE)
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'orden'  => $orden,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Actualiza nombre y orden de una categoría existente.
     * El estado activa/inactiva se cambia con cambiarEstado(), no aquí.
     */
    public function actualizar(int $id, string $nombre, int $orden): void
    {
        $sql = '
            UPDATE categorias
            SET nombre = :nombre,
                orden  = :orden
            WHERE id = :id
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'nombre' => $nombre,
            'orden'  => $orden,
            'id'     => $id,
        ]);
    }

    /**
     * Cambia el flag activa de la categoría (soft-delete reversible).
     */
    public function cambiarEstado(int $id, bool $activa): void
    {
        $sql = '
            UPDATE categorias
            SET activa = :activa
            WHERE id = :id
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'activa' => $activa ? 1 : 0,
            'id'     => $id,
        ]);
    }

    /**
     * Cuenta cuántos platos activos hay en una categoría.
     * Lo usa el admin antes de permitir desactivar la categoría.
     */
    public function contarPlatosActivos(int $id): int
    {
        $sql = '
            SELECT COUNT(*) AS n
            FROM platos
            WHERE categoria_id = :id
              AND activo = TRUE
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetch()['n'];
    }

    /**
     * Cuenta el total de platos de una categoría (activos e inactivos).
     * Útil para mostrar en el listado del admin.
     */
    public function contarPlatosTotales(int $id): int
    {
        $sql = '
            SELECT COUNT(*) AS n
            FROM platos
            WHERE categoria_id = :id
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetch()['n'];
    }
}
