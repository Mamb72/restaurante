<?php
/**
 * app/models/LineaPedido.php
 * Modelo de la tabla `lineas_pedido`.
 *
 * Cada línea representa un plato concreto dentro de un pedido, con su
 * cantidad y precio unitario congelado en el momento de pedir.
 *
 * Las líneas tienen estado propio: cada plato puede progresar en cocina
 * de forma independiente al resto del pedido.
 */

declare(strict_types=1);

final class LineaPedido extends Model
{
    /** Estados válidos del enum (en sincronía con schema.sql). */
    public const ESTADOS = ['PENDIENTE', 'EN_PREPARACION', 'LISTO', 'SERVIDO', 'ANULADO'];

    /**
     * Crea una línea de pedido. Devuelve el ID generado.
     *
     * El `precio_unitario` debe ser el precio CONGELADO en el momento de
     * la confirmación (proviene del snapshot del carrito), no consultado
     * dinámicamente desde `platos.precio`.
     */
    public function crear(
        int $pedidoId,
        int $platoId,
        int $cantidad,
        float $precioUnitario,
        ?string $nota = null
    ): int {
        if ($cantidad < 1) {
            throw new InvalidArgumentException('La cantidad debe ser ≥ 1');
        }
        if ($precioUnitario < 0) {
            throw new InvalidArgumentException('El precio unitario no puede ser negativo');
        }

        $sql = "
            INSERT INTO lineas_pedido
                (pedido_id, plato_id, cantidad, precio_unitario, nota, estado)
            VALUES
                (:pedido_id, :plato_id, :cantidad, :precio_unitario, :nota, 'PENDIENTE')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'pedido_id'       => $pedidoId,
            'plato_id'        => $platoId,
            'cantidad'        => $cantidad,
            'precio_unitario' => $precioUnitario,
            'nota'            => $nota,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Devuelve todas las líneas de un pedido, con el nombre del plato
     * resuelto desde la tabla `platos` mediante JOIN.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorPedido(int $pedidoId): array
    {
        $sql = '
            SELECT lp.id, lp.pedido_id, lp.plato_id, lp.cantidad,
                   lp.precio_unitario, lp.nota, lp.estado,
                   p.nombre AS plato_nombre
            FROM lineas_pedido lp
            INNER JOIN platos p ON p.id = lp.plato_id
            WHERE lp.pedido_id = :pedido_id
            ORDER BY lp.id ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['pedido_id' => $pedidoId]);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve TODAS las líneas activas de una sesión de mesa,
     * uniéndolas con sus pedidos. Útil para "Mi mesa" (resumen total).
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorSesion(int $sesionMesaId): array
    {
        $sql = "
            SELECT lp.id, lp.pedido_id, lp.plato_id, lp.cantidad,
                   lp.precio_unitario, lp.nota, lp.estado,
                   p.nombre AS plato_nombre,
                   pe.creado_en AS pedido_creado_en
            FROM lineas_pedido lp
            INNER JOIN pedidos pe ON pe.id = lp.pedido_id
            INNER JOIN platos p   ON p.id  = lp.plato_id
            WHERE pe.sesion_mesa_id = :sesion_mesa_id
              AND lp.estado != 'ANULADO'
            ORDER BY pe.creado_en ASC, lp.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sesion_mesa_id' => $sesionMesaId]);
        return $stmt->fetchAll();
    }

    /**
     * Cambia el estado de una línea individual.
     */
    public function cambiarEstado(int $lineaId, string $nuevoEstado): void
    {
        if (!in_array($nuevoEstado, self::ESTADOS, true)) {
            throw new InvalidArgumentException("Estado de línea no válido: $nuevoEstado");
        }

        $sql = '
            UPDATE lineas_pedido
            SET estado = :estado
            WHERE id = :id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'estado' => $nuevoEstado,
            'id'     => $lineaId,
        ]);
    }
}

