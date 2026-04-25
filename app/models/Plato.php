<?php
/**
 * app/models/Plato.php
 * Modelo de la tabla `platos`, con joins a categorías, alérgenos y etiquetas.
 */

declare(strict_types=1);

final class Plato extends Model
{
    /**
     * Devuelve los platos activos y disponibles de una categoría dada,
     * ordenados por `orden` y `nombre`. Incluye sus alérgenos y etiquetas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorCategoria(int $categoriaId): array
    {
        $sql = '
            SELECT id, nombre, descripcion, precio, ruta_foto,
                   disponible, destacado, destacado_hasta, orden
            FROM platos
            WHERE categoria_id = :categoria_id
              AND activo = TRUE
            ORDER BY orden ASC, nombre ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['categoria_id' => $categoriaId]);
        $platos = $stmt->fetchAll();

        // Enriquecer cada plato con sus alérgenos y etiquetas
        foreach ($platos as &$plato) {
            $plato['alergenos'] = $this->obtenerAlergenosDePlato((int) $plato['id']);
            $plato['etiquetas'] = $this->obtenerEtiquetasDePlato((int) $plato['id']);
        }

        return $platos;
    }

    /**
     * Devuelve los platos destacados que aún no han caducado.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerDestacados(): array
    {
        $sql = '
            SELECT id, nombre, descripcion, precio, ruta_foto,
                   destacado_hasta
            FROM platos
            WHERE activo = TRUE
              AND disponible = TRUE
              AND destacado = TRUE
              AND (destacado_hasta IS NULL OR destacado_hasta >= CURDATE())
            ORDER BY orden ASC, nombre ASC
        ';

        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Devuelve un plato por su ID, o null si no existe.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = '
            SELECT id, categoria_id, nombre, descripcion, precio, ruta_foto,
                   disponible, destacado, destacado_hasta, activo
            FROM platos
            WHERE id = :id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $plato = $stmt->fetch();

        return $plato === false ? null : $plato;
    }

    /**
     * @return array<int, array{codigo:string, nombre:string}>
     */
    private function obtenerAlergenosDePlato(int $platoId): array
    {
        $sql = '
            SELECT a.codigo, a.nombre
            FROM alergenos a
            INNER JOIN plato_alergeno pa ON pa.alergeno_id = a.id
            WHERE pa.plato_id = :plato_id
            ORDER BY a.nombre
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['plato_id' => $platoId]);
        return $stmt->fetchAll();
    }

    /**
     * @return array<int, array{codigo:string, nombre:string}>
     */
    private function obtenerEtiquetasDePlato(int $platoId): array
    {
        $sql = '
            SELECT e.codigo, e.nombre
            FROM etiquetas_dieteticas e
            INNER JOIN plato_etiqueta pe ON pe.etiqueta_id = e.id
            WHERE pe.plato_id = :plato_id
            ORDER BY e.nombre
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['plato_id' => $platoId]);
        return $stmt->fetchAll();
    }
}
