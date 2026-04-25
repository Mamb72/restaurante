<?php
/**
 * @var string $titulo
 * @var int    $mesa_numero
 * @var array  $categorias
 * @var array  $platosPorCategoria
 * @var array  $destacados
 * @var string $csrf_token
 * @var int    $items_carrito
 */

function fmtPrecio(string|float $precio): string {
    return number_format((float) $precio, 2, ',', '.') . ' €';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token) ?>">
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        :root {
            --color-primario: #2a7a2a;
            --color-acento: #e65100;
            --color-error: #c62828;
            --color-fondo: #fafafa;
            --color-tarjeta: #ffffff;
            --color-texto: #222;
            --color-texto-suave: #666;
            --radio: 8px;
            --sombra: 0 1px 3px rgba(0,0,0,0.05);
        }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 0 1rem 6rem 1rem; max-width: 800px; margin: 0 auto; color: var(--color-texto); background: var(--color-fondo); }

        /* Cabecera fija con info de mesa y botón Mi Pedido */
        .cabecera {
            position: sticky; top: 0; z-index: 10;
            background: var(--color-primario); color: white;
            padding: 0.8rem 1rem; margin: 0 -1rem 1rem -1rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cabecera-info { font-size: 0.95rem; }
        .cabecera-info strong { font-size: 1.1rem; }
        .boton-mi-pedido {
            background: white; color: var(--color-primario);
            border: none; padding: 0.5rem 1rem; border-radius: 20px;
            font-weight: 600; text-decoration: none; font-size: 0.9rem;
            display: flex; align-items: center; gap: 0.4rem;
        }
        .contador-items {
            background: var(--color-acento); color: white;
            min-width: 1.5rem; height: 1.5rem; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 0.8rem; font-weight: 700; padding: 0 0.4rem;
        }

        h1 { color: var(--color-primario); margin-top: 1rem; }
        h2 { color: var(--color-primario); border-bottom: 2px solid var(--color-primario); padding-bottom: 0.3rem; margin-top: 2rem; }

        .destacados { background: linear-gradient(135deg, #fff8e1, #ffe082); padding: 1rem 1.5rem; border-radius: 12px; margin: 1.5rem 0; border: 2px dashed #f9a825; }
        .destacados h2 { border: none; color: var(--color-acento); margin-top: 0; }

        .plato { background: var(--color-tarjeta); padding: 1rem; margin-bottom: 0.8rem; border-radius: var(--radio); box-shadow: var(--sombra); display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
        .plato-info { flex: 1; min-width: 0; }
        .plato-nombre { font-weight: 600; font-size: 1.05rem; margin: 0; }
        .plato-desc { color: var(--color-texto-suave); font-size: 0.92rem; margin: 0.3rem 0; }
        .plato-precio { font-weight: 700; color: var(--color-primario); font-size: 1.1rem; white-space: nowrap; margin-bottom: 0.5rem; display: block; text-align: right; }
        .plato-acciones { display: flex; flex-direction: column; align-items: flex-end; gap: 0.4rem; }

        .badges { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.4rem; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-alergeno { background: #ffebee; color: var(--color-error); }
        .badge-etiqueta { background: #e8f5e9; color: var(--color-primario); }
        .badge-destacado { background: #fff3e0; color: var(--color-acento); margin-left: 0.5rem; }

        /* Botón añadir */
        .boton-anadir {
            background: var(--color-primario); color: white;
            border: none; padding: 0.5rem 1rem; border-radius: 20px;
            font-weight: 600; cursor: pointer; font-size: 0.9rem;
            transition: all 0.15s ease;
            min-width: 90px;
        }
        .boton-anadir:hover { background: #1f5e1f; }
        .boton-anadir:disabled { background: #aaa; cursor: not-allowed; }
        .boton-anadir.exito { background: var(--color-acento); }

        .agotado { opacity: 0.5; }
        .agotado-tag { color: #999; font-size: 0.8rem; font-style: italic; }

        /* Toast de notificación */
        .toast {
            position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%) translateY(150%);
            background: var(--color-primario); color: white;
            padding: 0.8rem 1.5rem; border-radius: 30px;
            font-weight: 600; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
            z-index: 100; max-width: 90%;
        }
        .toast.visible { transform: translateX(-50%) translateY(0); }
        .toast.error { background: var(--color-error); }
    </style>
</head>
<body>
    <div class="cabecera">
        <div class="cabecera-info">
            🪑 <strong>Mesa <?= (int) $mesa_numero ?></strong>
        </div>
        <a href="/mi-mesa" class="boton-mi-pedido">
            🛒 Mi pedido
            <span class="contador-items" id="contador-items"><?= (int) $items_carrito ?></span>
        </a>
    </div>

    <h1>Nuestra carta</h1>

    <?php if (!empty($destacados)): ?>
        <div class="destacados">
            <h2>⭐ Recomendaciones de hoy</h2>
            <?php foreach ($destacados as $d): ?>
                <div class="plato">
                    <div class="plato-info">
                        <p class="plato-nombre"><?= htmlspecialchars($d['nombre']) ?></p>
                        <?php if (!empty($d['descripcion'])): ?>
                            <p class="plato-desc"><?= htmlspecialchars($d['descripcion']) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="plato-acciones">
                        <span class="plato-precio"><?= fmtPrecio($d['precio']) ?></span>
                        <button class="boton-anadir" data-plato-id="<?= (int) $d['id'] ?>">+ Añadir</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($categorias as $cat): ?>
        <h2><?= htmlspecialchars($cat['nombre']) ?></h2>
        <?php foreach ($platosPorCategoria[$cat['id']] ?? [] as $plato): ?>
            <div class="plato <?= !$plato['disponible'] ? 'agotado' : '' ?>">
                <div class="plato-info">
                    <p class="plato-nombre">
                        <?= htmlspecialchars($plato['nombre']) ?>
                        <?php if ($plato['destacado']): ?><span class="badge badge-destacado">⭐ destacado</span><?php endif; ?>
                        <?php if (!$plato['disponible']): ?><span class="agotado-tag">(agotado)</span><?php endif; ?>
                    </p>
                    <?php if (!empty($plato['descripcion'])): ?>
                        <p class="plato-desc"><?= htmlspecialchars($plato['descripcion']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($plato['etiquetas']) || !empty($plato['alergenos'])): ?>
                        <div class="badges">
                            <?php foreach ($plato['etiquetas'] as $et): ?>
                                <span class="badge badge-etiqueta"><?= htmlspecialchars($et['nombre']) ?></span>
                            <?php endforeach; ?>
                            <?php foreach ($plato['alergenos'] as $al): ?>
                                <span class="badge badge-alergeno"><?= htmlspecialchars($al['nombre']) ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="plato-acciones">
                    <span class="plato-precio"><?= fmtPrecio($plato['precio']) ?></span>
                    <?php if ($plato['disponible']): ?>
                        <button class="boton-anadir" data-plato-id="<?= (int) $plato['id'] ?>">+ Añadir</button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <div class="toast" id="toast"></div>

    <script>
    (function() {
        'use strict';

        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const contador = document.getElementById('contador-items');
        const toast = document.getElementById('toast');
        let toastTimer = null;

        function mostrarToast(mensaje, esError = false) {
            toast.textContent = mensaje;
            toast.classList.toggle('error', esError);
            toast.classList.add('visible');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('visible'), 2000);
        }

        async function anadirPlato(platoId, boton) {
            const textoOriginal = boton.textContent;
            boton.disabled = true;
            boton.textContent = '...';

            try {
                const formData = new FormData();
                formData.append('plato_id', platoId);
                formData.append('csrf_token', csrfToken);

                const respuesta = await fetch('/pedido/anadir', {
                    method: 'POST',
                    body: formData,
                });

                const datos = await respuesta.json();

                if (datos.ok) {
                    contador.textContent = datos.items_carrito;
                    boton.textContent = '✓ Añadido';
                    boton.classList.add('exito');
                    mostrarToast(`✓ ${datos.plato_anadido}`);
                    setTimeout(() => {
                        boton.textContent = textoOriginal;
                        boton.classList.remove('exito');
                        boton.disabled = false;
                    }, 1200);
                } else {
                    mostrarToast(datos.error || 'Error al añadir', true);
                    boton.textContent = textoOriginal;
                    boton.disabled = false;
                }
            } catch (err) {
                mostrarToast('Error de conexión', true);
                boton.textContent = textoOriginal;
                boton.disabled = false;
            }
        }

        // Delegación de eventos: un solo listener para todos los botones
        document.body.addEventListener('click', (e) => {
            const boton = e.target.closest('.boton-anadir');
            if (!boton) return;
            const platoId = parseInt(boton.dataset.platoId, 10);
            if (Number.isInteger(platoId) && platoId > 0) {
                anadirPlato(platoId, boton);
            }
        });
    })();
    </script>
</body>
</html>
