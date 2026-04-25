<?php
/**
 * @var string $titulo
 * @var string $mensaje
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 500px; margin: 4rem auto; padding: 1rem; text-align: center; color: #222; }
        h1 { color: #c62828; }
        p { color: #555; font-size: 1.1rem; line-height: 1.5; }
        .icono { font-size: 4rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="icono">⚠️</div>
    <h1><?= htmlspecialchars($titulo) ?></h1>
    <p><?= htmlspecialchars($mensaje) ?></p>
</body>
</html>
