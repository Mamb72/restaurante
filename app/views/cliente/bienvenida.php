<?php /** @var string $titulo */ ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 600px; margin: 4rem auto; padding: 0 1rem; text-align: center; color: #222; }
        h1 { color: #2a7a2a; }
        .card { background: #f5f5f5; padding: 2rem; border-radius: 12px; margin-top: 2rem; }
        code { background: #eee; padding: 2px 6px; border-radius: 3px; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>Bienvenido</h1>
    <p>Este es un sistema de pedidos para restaurantes.</p>

    <div class="card">
        <h2>¿Cómo pedir?</h2>
        <p>Escanea el código <strong>QR de tu mesa</strong> con la cámara del móvil para acceder a la carta y realizar tu pedido.</p>
    </div>

    <div class="card">
        <h2>✓ Fase 6.2 completada</h2>
        <p>El router MVC ya está funcionando:</p>
        <ol style="text-align: left;">
            <li>La URL <code>/</code> se resuelve por el <code>Router</code>.</li>
            <li>Se invoca <code>ClienteController::inicio()</code>.</li>
            <li>El controlador renderiza esta vista (<code>cliente/bienvenida.php</code>).</li>
        </ol>
    </div>
</body>
</html>
