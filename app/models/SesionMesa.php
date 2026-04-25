<?php
/**
 * app/models/SesionMesa.php
 * Modelo de la tabla `sesiones_mesa`.
 *
 * Una sesión representa el periodo de tiempo en que un grupo de comensales
 * está sentado en una mesa. Estados: ABIERTA → PEDIDA_CUENTA → CERRADA.
 */

declare(strict_types=1);

final class SesionMesa extends Model
{
    /**
     * Devuelve la sesión activa (no cerrada) de una mesa, si existe.
     * Una mesa solo puede tener una sesión activa a la vez.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerActivaPorMesa(int $mesaId): ?array
    {
        $sql = "
            SELECT id, mesa_id, abierta_en, cerrada_en, estado
            FROM sesiones_mesa
            WHERE mesa_id = :mesa_id
              AND estado IN ('ABIERTA', 'PEDIDA_CUENTA')
            ORDER BY abierta_en DESC
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mesa_id' => $mesaId]);
        $sesion = $stmt->fetch();

        return $sesion === false ? null : $sesion;
    }

    /**
     * Devuelve una sesión por su ID.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = '
            SELECT id, mesa_id, abierta_en, cerrada_en, estado
            FROM sesiones_mesa
            WHERE id = :id
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $sesion = $stmt->fetch();

        return $sesion === false ? null : $sesion;
    }

    /**
     * Abre una nueva sesión en la mesa indicada.
     * Devuelve el ID de la sesión recién creada.
     */
    public function abrir(int $mesaId): int
    {
        $sql = "
            INSERT INTO sesiones_mesa (mesa_id, estado)
            VALUES (:mesa_id, 'ABIERTA')
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['mesa_id' => $mesaId]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Marca la sesión como CERRADA y registra la fecha de cierre.
     */
    public function cerrar(int $sesionId): void
    {
        $sql = "
            UPDATE sesiones_mesa
            SET estado = 'CERRADA', cerrada_en = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $sesionId]);
    }

    /**
     * Cambia el estado de la sesión a PEDIDA_CUENTA.
     * El cliente pulsa "pedir cuenta" y la cocina/camarero verán la alerta.
     */
    public function pedirCuenta(int $sesionId): void
    {
        $sql = "
            UPDATE sesiones_mesa
            SET estado = 'PEDIDA_CUENTA'
            WHERE id = :id
              AND estado = 'ABIERTA'
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $sesionId]);
    }

    /**
     * Obtiene o crea una sesión activa para la mesa.
     * Si ya hay una abierta, la devuelve. Si no, abre una nueva.
     *
     * @return array<string, mixed>
     */
    public function obtenerOAbrir(int $mesaId): array
    {
        $sesion = $this->obtenerActivaPorMesa($mesaId);

        if ($sesion !== null) {
            return $sesion;
        }

        $nuevoId = $this->abrir($mesaId);
        return $this->obtenerPorId($nuevoId);
    }
}
