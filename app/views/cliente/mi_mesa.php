<?php
/**
 * @var string $titulo
 * @var int    $mesa_numero
 * @var int    $sesion_mesa_id
 * @var array  $carrito
 * @var string $csrf_token
 * @var int    $items_carrito
 * @var float  $total_carrito
 * @var array  $lineas_confirmadas
 * @var float  $total_confirmado
 */

function fmtPrecio(float $precio): string {
    return number_format($precio, 2, ',', '.') . ' €';
}

function badgeEstado(string $estado): string {
    return match ($estado) {
        'PENDIENTE'      => '<span class="estado pendiente">⏳ Pendiente</span>',
        'EN_PREPARACION' => '<span class="estado preparando">👨‍🍳 Preparando</span>',
        'LISTO'          => '<span class="estado listo">✅ Listo</span>',
        'SERVIDO'        => '<span class="estado servido">🍽️ Servido</span>',
        'ANULADO'        => '<span class="estado anulado">❌ Anulado</span>',
        default          => '<span class="estado">' . htmlspecialchars($estado) . '</span>',
    };
}

$totalMesa = $total_carrito + $total_confirmado;
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
            --color-aviso: #f9a825;
            --color-info: #1565c0;
            --color-fondo: #fafafa;
            --color-tarjeta: #ffffff;
            --color-texto: #222;
            --color-texto-suave: #666;
            --radio: 8px;
            --sombra: 0 1px 3px rgba(0,0,0,0.05);
        }
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 0 1rem 7rem 1rem; max-width: 800px; margin: 0 auto; color: var(--color-texto); background: var(--color-fondo); }

        .cabecera {
            position: sticky; top: 0; z-index: 10;
            background: var(--color-primario); color: white;
            padding: 0.8rem 1rem; margin: 0 -1rem 1rem -1rem;
            display: flex; justify-content: space-between; align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cabecera-info strong { font-size: 1.1rem; }
        .boton-cabecera {
            background: white; color: var(--color-primario);
            border: none; padding: 0.5rem 1rem; border-radius: 20px;
            font-weight: 600; text-decoration: none; font-size: 0.9rem;
        }

        h1 { color: var(--color-primario); margin-top: 1rem; }
        h2 { color: var(--color-primario); border-bottom: 2px solid var(--color-primario); padding-bottom: 0.3rem; margin-top: 2rem; }

        .seccion-vacia {
            background: var(--color-tarjeta); padding: 2rem; border-radius: var(--radio);
            text-align: center; color: var(--color-texto-suave); font-style: italic;
            box-shadow: var(--sombra);
        }

        .linea {
            background: var(--color-tarjeta); padding: 0.8rem 1rem; margin-bottom: 0.6rem;
            border-radius: var(--radio); box-shadow: var(--sombra);
            display: flex; justify-content: space-between; align-items: center; gap: 1rem;
        }
        .linea-info { flex: 1; min-width: 0; }
        .linea-nombre { font-weight: 600; margin: 0; }
        .linea-detalle { color: var(--color-texto-suave); font-size: 0.9rem; margin: 0.2rem 0 0 0; }
        .linea-precio { font-weight: 700; color: var(--color-primario); white-space: nowrap; }

        .controles-cantidad {
            display: flex; align-items: center; gap: 0.4rem;
        }
        .boton-cantidad {
            background: #eee; border: none; width: 28px; height: 28px;
            border-radius: 50%; font-weight: 700; cursor: pointer;
            font-size: 1rem; line-height: 1;
        }
        .boton-cantidad:hover { background: #ddd; }
        .cantidad { font-weight: 600; min-width: 1.5rem; text-align: center; }

        .estado {
            display: inline-block; padding: 2px 10px; border-radius: 12px;
            font-size: 0.75rem; font-weight: 600;
        }
        .estado.pendiente   { background: #fff8e1; color: #e65100; }
        .estado.preparando  { background: #e3f2fd; color: var(--color-info); }
        .estado.listo       { background: #e8f5e9; color: var(--color-primario); }
        .estado.servido     { background: #f3e5f5; color: #6a1b9a; }
        .estado.anulado     { background: #ffebee; color: var(--color-error); }

        .resumen-total {
            background: var(--color-tarjeta); padding: 1rem 1.5rem; border-radius: var(--radio);
            box-shadow: var(--sombra); margin-top: 1.5rem;
        }
        .resumen-fila {
            display: flex; justify-content: space-between; padding: 0.4rem 0;
            color: var(--color-texto-suave);
        }
        .resumen-fila.total {
            border-top: 2px solid #eee; margin-top: 0.5rem; padding-top: 0.8rem;
            color: var(--color-texto); font-weight: 700; font-size: 1.2rem;
        }
        .resumen-fila.total span:last-child { color: var(--color-primario); }

        .barra-confirmar {
            position: fixed; bottom: 0; left: 0; right: 0;
            background: white; border-top: 1px solid #eee;
            padding: 0.8rem 1rem;
            box-shadow: 0 -2px 8px rgba(0,0,0,0.05);
            display: flex; justify-content: center; z-index: 20;
        }
        .boton-confirmar {
            background: var(--color-primario); color: white; border: none;
            padding: 0.9rem 2rem; border-radius: 30px; font-size: 1.05rem;
            font-weight: 700; cursor: pointer; max-width: 500px; width: 100%;
            box-shadow: 0 2px 8px rgba(42,122,42,0.3);
        }
        .boton-confirmar:hover { background: #1f5e1f; }
        .boton-confirmar:disabled { background: #aaa; cursor: not-allowed; box-shadow: none; }

        .toast {
            position: fixed; bottom: 6rem; left: 50%;
            transform: translateX(-50%) translateY(150%);
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
        <div class="cabecera-info">🪑 <strong>Mesa <?= (int) $mesa_numero ?></strong></div>
        <a href="/carta-mesa" class="boton-cabecera">📖 Ver carta</a>
    </div>

    <h1>Mi pedido</h1>

    <h2>🛒 En curso (sin enviar)</h2>
    <?php if (empty($carrito)): ?>
        <div class="seccion-vacia">
            No has añadido nada todavía.<br>
            <a href="/carta-mesa" style="color: var(--color-primario); font-weight: 600;">Ir a la carta</a>
        </div>
    <?php else: ?>
        <?php foreach ($carrito as $platoId => $linea): ?>
            <?php $subtotal = $linea['cantidad'] * $linea['snapshot']['precio']; ?>
            <div class="linea">
                <div class="linea-info">
                    <p class="linea-nombre"><?= htmlspecialchars($linea['snapshot']['nombre']) ?></p>
                    <p class="linea-detalle">
                        <?= fmtPrecio((float) $linea['snapshot']['precio']) ?> × unidad
                    </p>
                </div>
                <div class="controles-cantidad">
                    <button class="boton-cantidad boton-quitar" data-plato-id="<?= (int) $platoId ?>" title="Quitar uno">−</button>
                    <span class="cantidad"><?= (int) $linea['cantidad'] ?></span>
                    <button class="boton-cantidad boton-anadir-uno" data-plato-id="<?= (int) $platoId ?>" title="Añadir uno">+</button>
                </div>
                <span class="linea-precio"><?= fmtPrecio($subtotal) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2>📜 Pedidos enviados</h2>
    <?php if (empty($lineas_confirmadas)): ?>
        <div class="seccion-vacia">
            Aún no has enviado ningún pedido a cocina.
        </div>
    <?php else: ?>
        <?php foreach ($lineas_confirmadas as $l): ?>
            <?php $subtotal = (float) $l['precio_unitario'] * (int) $l['cantidad']; ?>
            <div class="linea">
                <div class="linea-info">
                    <p class="linea-nombre">
                        <?= (int) $l['cantidad'] ?>× <?= htmlspecialchars($l['plato_nombre']) ?>
                    </p>
                    <p class="linea-detalle">
                        <?= fmtPrecio((float) $l['precio_unitario']) ?> × ud · <?= badgeEstado($l['estado']) ?>
                    </p>
                </div>
                <span class="linea-precio"><?= fmtPrecio($subtotal) ?></span>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div class="resumen-total">
        <div class="resumen-fila">
            <span>Pedidos enviados</span>
            <span><?= fmtPrecio($total_confirmado) ?></span>
        </div>
        <div class="resumen-fila">
            <span>En el carrito</span>
            <span><?= fmtPrecio($total_carrito) ?></span>
        </div>
        <div class="resumen-fila total">
            <span>Total mesa</span>
            <span><?= fmtPrecio($totalMesa) ?></span>
        </div>
    </div>

    <?php if (!empty($carrito)): ?>
    <div class="barra-confirmar">
        <button class="boton-confirmar" id="btn-confirmar">
            Confirmar pedido (<?= (int) $items_carrito ?> productos · <?= fmtPrecio($total_carrito) ?>)
        </button>
    </div>
    <?php endif; ?>

    <div class="toast" id="toast"></div>

    <script>
    (function() {
        'use strict';
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const toast = document.getElementById('toast');
        let toastTimer = null;

        function mostrarToast(msg, esError = false) {
            toast.textContent = msg;
            toast.classList.toggle('error', esError);
            toast.classList.add('visible');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => toast.classList.remove('visible'), 2200);
        }

        async function postJSON(url, formData) {
            formData.append('csrf_token', csrfToken);
            const r = await fetch(url, { method: 'POST', body: formData });
            return await r.json();
        }

        document.body.addEventListener('click', async (e) => {
            const btnQuitar = e.target.closest('.boton-quitar');
            const btnAnadir = e.target.closest('.boton-anadir-uno');
            const btnConfirmar = e.target.closest('#btn-confirmar');

            if (btnQuitar) {
                const fd = new FormData();
                fd.append('plato_id', btnQuitar.dataset.platoId);
                const r = await postJSON('/pedido/quitar', fd);
                if (r.ok) location.reload();
                else mostrarToast(r.error || 'Error', true);
            }
            else if (btnAnadir) {
                const fd = new FormData();
                fd.append('plato_id', btnAnadir.dataset.platoId);
                const r = await postJSON('/pedido/anadir', fd);
                if (r.ok) location.reload();
                else mostrarToast(r.error || 'Error', true);
            }
            else if (btnConfirmar) {
                if (!confirm('¿Confirmar este pedido y enviarlo a cocina?')) return;
                btnConfirmar.disabled = true;
                btnConfirmar.textContent = 'Enviando...';
                const r = await postJSON('/pedido/confirmar', new FormData());
                if (r.ok) {
                    mostrarToast(`✓ Pedido #${r.pedido_id} enviado a cocina`);
                    setTimeout(() => location.reload(), 1200);
                } else {
                    mostrarToast(r.error || 'Error al enviar', true);
                    btnConfirmar.disabled = false;
                }
            }
        });
    })();
    </script>
</body>
</html>
