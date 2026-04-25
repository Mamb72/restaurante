<?php
/**
 * app/models/Mesa.php
 * Modelo de la tabla `mesas`. Solo lectura desde la perspectiva del cliente.
 */

declare(strict_types=1);

final class Mesa extends Model
{
    /**
     * Busca una mesa por su token QR.
     * Devuelve null si el token no existe o la mesa está desactivada.
     *
     * @return array{id:int, numero:int, token_qr:string, activa:int}|null
     */
    public function obtenerPorToken(string $token): ?array
    {
        $sql = '
            SELECT id, numero, token_qr, activa
            FROM mesas
            WHERE token_qr = :token
              AND activa = TRUE
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['token' => $token]);
        $mesa = $stmt->fetch();

        return $mesa === false ? null : $mesa;
    }

    /**
     * Busca una mesa por su ID. Útil para validar sesiones.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $sql = 'SELECT id, numero, token_qr, activa FROM mesas WHERE id = :id LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $mesa = $stmt->fetch();

        return $mesa === false ? null : $mesa;
    }
}
