<?php
/**
 * app/models/Usuario.php
 * Modelo de usuario interno del restaurante (admin y cocina).
 *
 * Responsabilidades:
 *  - Buscar usuarios por email.
 *  - Verificar credenciales (email + contraseña).
 *  - Devolver datos públicos de un usuario por su id.
 *
 * NO gestiona sesiones PHP ni redirecciones: eso es responsabilidad
 * del AuthController.
 */

declare(strict_types=1);

final class Usuario extends Model
{
    /**
     * Busca un usuario activo por su email.
     * Devuelve el array completo (incluye hash_password) o null si no existe.
     *
     * IMPORTANTE: este método devuelve el hash de la contraseña porque
     * lo necesita verificarCredenciales(). NO se debe pasar el resultado
     * directamente a una vista.
     *
     * @return array<string, mixed>|null
     */
    public function buscarPorEmail(string $email): ?array
    {
        $sql = '
            SELECT id, nombre, email, hash_password, rol, activo, creado_en
            FROM usuarios
            WHERE email = :email
              AND activo = TRUE
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * Busca un usuario activo por su id.
     * NO devuelve el hash de la contraseña: pensado para usar en vistas
     * y para repoblar datos del usuario en sesión.
     *
     * @return array<string, mixed>|null
     */
    public function buscarPorId(int $id): ?array
    {
        $sql = '
            SELECT id, nombre, email, rol, activo, creado_en
            FROM usuarios
            WHERE id = :id
              AND activo = TRUE
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();

        return $fila === false ? null : $fila;
    }

    /**
     * Verifica las credenciales del usuario.
     * Devuelve los datos del usuario (sin el hash) si el email + password
     * son correctos. Devuelve null en cualquier otro caso.
     *
     * Usa password_verify() (bcrypt) para comparar la contraseña en claro
     * con el hash almacenado. Resistente a ataques de timing.
     *
     * @return array<string, mixed>|null
     */
    public function verificarCredenciales(string $email, string $passwordEnClaro): ?array
    {
        $usuario = $this->buscarPorEmail($email);

        if ($usuario === null) {
            // Aún así calculamos un password_verify falso para que el
            // tiempo de respuesta sea similar entre "email no existe"
            // y "email existe pero password incorrecta". Mitiga la
            // enumeración de usuarios por timing.
            password_verify(
                $passwordEnClaro,
                '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalido'
            );
            return null;
        }

        if (!password_verify($passwordEnClaro, $usuario['hash_password'])) {
            return null;
        }

        // Credenciales correctas: devolvemos los datos sin el hash.
        unset($usuario['hash_password']);
        return $usuario;
    }
}
