<?php
/**
 * app/core/Database.php
 * Wrapper de PDO que gestiona la conexión a MariaDB.
 *
 * Usa el patrón Singleton: se instancia una sola vez y se reutiliza
 * en todos los modelos durante la petición.
 */

declare(strict_types=1);

final class Database
{
    private static ?PDO $instance = null;

    /**
     * Constructor privado para impedir que se instancie desde fuera.
     */
    private function __construct() {}

    /**
     * Devuelve la instancia única de PDO.
     * La crea la primera vez que se llama.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $config = require BASE_PATH . '/config/db.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
            ];

            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['user'],
                    $config['password'],
                    $opciones
                );
            } catch (PDOException $e) {
                // Si estamos en modo debug, mostramos el error completo.
                // Si no, un mensaje genérico y lo mandamos a log.
                if (DEBUG_MODE) {
                    throw $e;
                }
                error_log('Database error: ' . $e->getMessage());
                throw new RuntimeException('Error de conexión a la base de datos.');
            }
        }

        return self::$instance;
    }

    /**
     * Impide la clonación de la instancia (parte del patrón Singleton).
     */
    private function __clone() {}
}
