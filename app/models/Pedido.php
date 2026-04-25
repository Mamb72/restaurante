<?php
/**
 * app/models/Pedido.php
 * Modelo de la tabla `pedidos`.
 *
 * Un pedido agrupa varias líneas (platos) realizadas en una misma "ronda"
 * dentro de una sesión de mesa. Cada sesión puede tener N pedidos.
 *
 * Estados: PENDIENTE → EN_PREPARACION → LISTO → SERVIDO
 *          (ANULADO en cualquier momento)
 */

declare(strict_types=1);

final class Pedido extends Model
{
    /** Estados válidos del enum (en sincronía con schema.sql). */
    public const ESTADOS = ['PENDIENTE', 'EN_PREPARACION', 'LISTO', 'SERVIDO', 'ANULADO'];

    /**
     * Crea un nuevo pedido vacío en estado PENDIENTE para la sesión dada.
     * Devuelve el ID del pedido recién creado.
     */
    public function crear(int $sesionMesaId): int
    {
        $sql = "
            INSERT INTO pedidos (sesion_mesa_id, estado)
            VALUES (:sesion_mesa_id, 'PENDIENTE')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sesion_mesa_id' => $sesionMesaId]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = '
            SELECT id, sesion_mesa_id, creado_en, estado
            FROM pedidos
            WHERE id = :id
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $pedido = $stmt->fetch();

        return $pedido === false ? null : $pedido;
    }

    /**
     * Devuelve todos los pedidos de una sesión de mesa, en orden cronológico.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerPorSesion(int $sesionMesaId): array
    {
        $sql = '
            SELECT id, sesion_mesa_id, creado_en, estado
            FROM pedidos
            WHERE sesion_mesa_id = :sesion_mesa_id
            ORDER BY creado_en ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sesion_mesa_id' => $sesionMesaId]);
        return $stmt->fetchAll();
    }

    /**
     * Cambia el estado de un pedido. Valida contra la lista permitida.
     *
     * @throws InvalidArgumentException si el estado no es válido.
     */
    public function cambiarEstado(int $pedidoId, string $nuevoEstado): void
    {
        if (!in_array($nuevoEstado, self::ESTADOS, true)) {
            throw new InvalidArgumentException("Estado de pedido no válido: $nuevoEstado");
        }

        $sql = '
            UPDATE pedidos
            SET estado = :estado
            WHERE id = :id
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'estado' => $nuevoEstado,
            'id'     => $pedidoId,
        ]);
    }

    /**
     * Devuelve los pedidos que cocina debe gestionar (no servidos ni anulados).
     * Incluye número de mesa y un total calculado para mostrar en el panel.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerActivosParaCocina(): array
    {
        $sql = "
            SELECT p.id, p.sesion_mesa_id, p.creado_en, p.estado,
                   m.numero AS mesa_numero
            FROM pedidos p
            INNER JOIN sesiones_mesa s ON s.id = p.sesion_mesa_id
            INNER JOIN mesas m ON m.id = s.mesa_id
            WHERE p.estado IN ('PENDIENTE', 'EN_PREPARACION', 'LISTO')
            ORDER BY p.creado_en ASC
        ";

        return $this->db->query($sql)->fetchAll();
    }
}
