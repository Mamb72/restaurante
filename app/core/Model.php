<?php
/**
 * app/core/Model.php
 * Clase base de la que heredan todos los modelos.
 *
 * Ofrece acceso compartido a la conexión PDO mediante Database::getConnection().
 */

declare(strict_types=1);

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}
