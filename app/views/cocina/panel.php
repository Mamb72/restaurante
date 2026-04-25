<?php
/**
 * app/views/cocina/panel.php
 * Panel de cocina — vista estática (sin polling AJAX todavía).
 *
 * Variables esperadas:
 *  - $titulo (string)
 *  - $usuario (array)         datos del cocinero/admin en sesión
 *  - $pedidos (array)         pedidos activos con sus líneas anidadas
 *  - $csrf_token (string)     se usará en 6.5.b para los botones AJAX
 */

declare(strict_types=1);

/**
 * Devuelve la clase CSS asociada a un estado de línea.
 */
function claseEstado(string $estado): string
{
    return match ($estado) {
        'PENDIENTE'      => 'estado-pendiente',
        'EN_PREPARACION' => 'estado-preparando',
        'LISTO'          => 'estado-listo',
        'SERVIDO'        => 'estado-servido',
        'ANULADO'        => 'estado-anulado',
        default          => '',
    };
}

/**
 * Etiqueta humana del estado.
 */
function etiquetaEstado(string $estado): string
{
    return match ($estado) {
        'PENDIENTE'      => 'Pendiente',
        'EN_PREPARACION' => 'Preparando',
        'LISTO'          => 'Listo',
        'SERVIDO'        => 'Servido',
        'ANULADO'        => 'Anulado',
        default          => $estado,
    };
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, sans-serif;
            background: #1a202c;
            color: #e2e8f0;
            margin: 0;
            padding: 0;
        }
        header {
            background: #2d3748;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #4a5568;
        }
        header h1 {
            margin: 0;
            font-size: 1.3rem;
        }
        header .meta {
            display: flex;
            gap: 1.2rem;
            align-items: center;
            font-size: .9rem;
            color: #a0aec0;
        }
        header a {
            color: #63b3ed;
            text-decoration: none;
        }
        header a:hover { text-decoration: underline; }

        main {
            padding: 1.5rem;
        }
        .vacio {
            text-align: center;
            padding: 3rem;
            color: #a0aec0;
            font-size: 1.1rem;
        }
        .grid-pedidos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.2rem;
        }
        .pedido {
            background: #2d3748;
            border-radius: 8px;
            padding: 1rem;
            border-top: 4px solid #4299e1;
        }
        .pedido.estado-PENDIENTE      { border-top-color: #ecc94b; }
        .pedido.estado-EN_PREPARACION { border-top-color: #ed8936; }
        .pedido.estado-LISTO          { border-top-color: #48bb78; }

        .pedido-cabecera {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: .8rem;
            padding-bottom: .6rem;
            border-bottom: 1px solid #4a5568;
        }
        .pedido-mesa {
            font-size: 1.4rem;
            font-weight: bold;
        }
        .pedido-hora {
            font-size: .85rem;
            color: #a0aec0;
        }
        .pedido-id {
            font-size: .75rem;
            color: #718096;
        }

        .lineas {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .linea {
            padding: .6rem .4rem;
            border-bottom: 1px solid #4a5568;
        }
        .linea:last-child { border-bottom: none; }

        .linea-superior {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: .5rem;
        }
        .linea-titulo {
            font-weight: 500;
        }
        .linea-cantidad {
            background: #4a5568;
            color: #fff;
            padding: .15rem .5rem;
            border-radius: 4px;
            font-size: .85rem;
            margin-right: .4rem;
        }
        .linea-nota {
            font-size: .85rem;
            color: #fbd38d;
            font-style: italic;
            margin-top: .25rem;
        }
        .badge {
            display: inline-block;
            padding: .2rem .6rem;
            border-radius: 12px;
            font-size: .75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .estado-pendiente   { background: #ecc94b; color: #744210; }
        .estado-preparando  { background: #ed8936; color: #7b341e; }
        .estado-listo       { background: #48bb78; color: #22543d; }
        .estado-servido     { background: #4a5568; color: #cbd5e0; }
        .estado-anulado     { background: #718096; color: #1a202c; }

        .acciones {
            display: flex;
            gap: .4rem;
            margin-top: .5rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: .35rem .7rem;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
            font-size: .8rem;
            font-weight: 500;
            color: #fff;
            background: #4a5568;
        }
        .btn:hover { opacity: .85; }
        .btn-preparar { background: #ed8936; }
        .btn-listo    { background: #48bb78; }
        .btn-servir   { background: #4299e1; }
        .btn:disabled {
            opacity: .4;
            cursor: not-allowed;
        }

        .nota-dev {
            background: #2d3748;
            border-left: 4px solid #ecc94b;
            padding: .6rem 1rem;
            margin: 1rem 1.5rem;
            font-size: .85rem;
            color: #cbd5e0;
        }
    </style>
</head>
<body>

    <header>
        <h1>🍳 Panel de cocina</h1>
        <div class="meta">
            <span>
                <?= htmlspecialchars($usuario['nombre']) ?>
                (<?= htmlspecialchars($usuario['rol']) ?>)
            </span>
            <a href="<?= BASE_URL ?>/logout">Cerrar sesión</a>
        </div>
    </header>

    <div class="nota-dev">
        ⚠ Vista provisional sin polling AJAX. En la siguiente sub-pieza
        (6.5.b) se añadirá refresco automático cada 4&nbsp;s y los botones
        de cambio de estado funcionarán por AJAX.
    </div>

    <main>
        <?php if (empty($pedidos)): ?>
            <div class="vacio">
                ✅ No hay pedidos activos en este momento.
            </div>
        <?php else: ?>
            <div class="grid-pedidos">
                <?php foreach ($pedidos as $pedido): ?>
                    <article class="pedido estado-<?= htmlspecialchars($pedido['estado']) ?>">
                        <div class="pedido-cabecera">
                            <div>
                                <div class="pedido-mesa">Mesa <?= (int) $pedido['mesa_numero'] ?></div>
                                <div class="pedido-id">Pedido #<?= (int) $pedido['id'] ?></div>
                            </div>
                            <div class="pedido-hora">
                                <?= htmlspecialchars(
                                    date('H:i', strtotime((string) $pedido['creado_en']))
                                ) ?>
                            </div>
                        </div>

                        <ul class="lineas">
                            <?php foreach ($pedido['lineas'] as $linea): ?>
                                <li class="linea" data-linea-id="<?= (int) $linea['id'] ?>">
                                    <div class="linea-superior">
                                        <div class="linea-titulo">
                                            <span class="linea-cantidad">×<?= (int) $linea['cantidad'] ?></span>
                                            <?= htmlspecialchars((string) $linea['plato_nombre']) ?>
                                        </div>
                                        <span class="badge <?= claseEstado((string) $linea['estado']) ?>">
                                            <?= htmlspecialchars(etiquetaEstado((string) $linea['estado'])) ?>
                                        </span>
                                    </div>

                                    <?php if (!empty($linea['nota'])): ?>
                                        <div class="linea-nota">
                                            ✏ <?= htmlspecialchars((string) $linea['nota']) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="acciones">
                                        <button class="btn btn-preparar" disabled>
                                            ▶ Preparando
                                        </button>
                                        <button class="btn btn-listo" disabled>
                                            ✓ Listo
                                        </button>
                                        <button class="btn btn-servir" disabled>
                                            🍽 Servido
                                        </button>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

</body>
</html>
