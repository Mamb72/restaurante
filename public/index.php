<?php
/**
 * public/index.php
 * Punto de entrada de la aplicación.
 *
 * En esta fase 6.1 es una versión MUY básica: solo carga la configuración,
 * abre la conexión a BD y lista las categorías.
 * En la fase 6.2 se convertirá en un router completo.
 */

declare(strict_types=1);

// 1. Cargar configuración global
require dirname(__DIR__) . '/config/config.php';

// 2. Cargar clase Database
require BASE_PATH . '/app/core/Database.php';

// 3. Obtener conexión y consultar categorías
try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query('
        SELECT id, nombre, orden, activa
        FROM categorias
        ORDER BY orden, nombre
    ');
    $categorias = $stmt->fetchAll();
} catch (Throwable $e) {
    http_response_code(500);
    exit('Error: ' . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restaurante — Fase 6.1</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #222; }
        h1 { color: #2a7a2a; }
        .card { background: #f5f5f5; padding: 1rem 1.5rem; border-radius: 8px; margin-top: 1rem; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { text-align: left; padding: 8px 12px; border-bottom: 1px solid #ddd; }
        th { background: #2a7a2a; color: white; }
        .info { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>✓ Fase 6.1 completada</h1>
    <p>La aplicación ya utiliza una clase <code>Database</code> profesional para conectar con MariaDB.</p>

    <div class="card">
        <strong>Arquitectura en funcionamiento:</strong>
        <ol>
            <li><code>config/config.php</code> cargado correctamente.</li>
            <li><code>config/db.php</code> leído (credenciales fuera del código).</li>
            <li><code>Database::getConnection()</code> devuelve una instancia PDO singleton.</li>
            <li>Consulta preparada ejecutada sobre la tabla <code>categorias</code>.</li>
        </ol>
    </div>

    <h2>Categorías en la base de datos</h2>
    <?php if (empty($categorias)): ?>
        <p class="info">Todavía no hay categorías. Las añadiremos en la siguiente fase.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Orden</th>
                    <th>Activa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $cat['id']) ?></td>
                        <td><?= htmlspecialchars($cat['nombre']) ?></td>
                        <td><?= htmlspecialchars((string) $cat['orden']) ?></td>
                        <td><?= $cat['activa'] ? '✓' : '✗' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <p class="info">Fecha del servidor: <?= date('Y-m-d H:i:s') ?> (zona horaria: <?= date_default_timezone_get() ?>)</p>
</body>
</html>
