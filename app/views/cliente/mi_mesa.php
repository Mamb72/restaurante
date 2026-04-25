<?php
/**
 * @var string $titulo
 * @var int    $mesa_numero
 * @var int    $sesion_mesa_id
 * @var array  $carrito
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 700px; margin: 0 auto; padding: 1rem; color: #222; background: #fafafa; }
        h1 { color: #2a7a2a; }
        .info { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 1rem; }
        .label { color: #666; font-size: 0.9rem; }
        .valor { font-weight: 600; font-size: 1.1rem; }
        .placeholder { background: #e3f2fd; color: #1565c0; padding: 1rem; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <h1>👋 Bienvenido a la Mesa <?= (int) $mesa_numero ?></h1>

    <div class="info">
        <p class="label">ID de sesión de mesa:</p>
        <p class="valor">#<?= (int) $sesion_mesa_id ?></p>
        <p class="label">Productos en su pedido:</p>
        <p class="valor"><?= count($carrito) ?></p>
    </div>

    <div class="placeholder">
        🍽️ Aquí aparecerá la carta y su pedido en cuanto se complete la fase 6.4.
    </div>
</body>
</html>
