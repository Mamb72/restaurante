<?php
/**
 * app/views/cocina/panel.php
 * Panel de cocina con polling AJAX y botones de cambio de estado.
 *
 * Variables esperadas:
 *  - $titulo (string)
 *  - $usuario (array)
 *  - $pedidos (array)         primer render desde el servidor
 *  - $csrf_token (string)     se usa en cada petición POST de cambio
 *
 * Funcionamiento del polling:
 *  - Cada INTERVALO_MS milisegundos, fetch a /cocina/pedidos.json
 *  - Se compara con el estado anterior y se redibuja si hay cambios
 *  - Si entra un pedido nuevo, se muestra un toast y suena un beep
 *  - Los botones envían POST a /cocina/linea/estado con CSRF
 */

declare(strict_types=1);
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
        header h1 { margin: 0; font-size: 1.3rem; }
        header .meta {
            display: flex; gap: 1.2rem; align-items: center;
            font-size: .9rem; color: #a0aec0;
        }
        header a { color: #63b3ed; text-decoration: none; }
        header a:hover { text-decoration: underline; }

        #estado-conexion {
            display: inline-block;
            width: .7rem; height: .7rem;
            border-radius: 50%;
            background: #48bb78;
            margin-right: .3rem;
            transition: background .3s;
        }
        #estado-conexion.error { background: #f56565; }

        main { padding: 1.5rem; }

        .vacio {
            text-align: center; padding: 3rem;
            color: #a0aec0; font-size: 1.1rem;
        }

        .grid-pedidos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.2rem;
        }
        .pedido {
            background: #2d3748; border-radius: 8px; padding: 1rem;
            border-top: 4px solid #4299e1;
            transition: border-color .4s, transform .2s;
        }
        .pedido.estado-PENDIENTE      { border-top-color: #ecc94b; }
        .pedido.estado-EN_PREPARACION { border-top-color: #ed8936; }
        .pedido.estado-LISTO          { border-top-color: #48bb78; }
        .pedido.entrando {
            animation: entrar .6s ease-out;
        }
        @keyframes entrar {
            from { transform: scale(.9); opacity: 0; }
            to   { transform: scale(1);  opacity: 1; }
        }

        .pedido-cabecera {
            display: flex; justify-content: space-between;
            align-items: baseline; margin-bottom: .8rem;
            padding-bottom: .6rem; border-bottom: 1px solid #4a5568;
        }
        .pedido-mesa { font-size: 1.4rem; font-weight: bold; }
        .pedido-hora { font-size: .85rem; color: #a0aec0; }
        .pedido-id   { font-size: .75rem; color: #718096; }

        .lineas { list-style: none; margin: 0; padding: 0; }
        .linea {
            padding: .6rem .4rem;
            border-bottom: 1px solid #4a5568;
        }
        .linea:last-child { border-bottom: none; }
        .linea-superior {
            display: flex; justify-content: space-between;
            align-items: center; gap: .5rem;
        }
        .linea-titulo { font-weight: 500; }
        .linea-cantidad {
            background: #4a5568; color: #fff;
            padding: .15rem .5rem; border-radius: 4px;
            font-size: .85rem; margin-right: .4rem;
        }
        .linea-nota {
            font-size: .85rem; color: #fbd38d;
            font-style: italic; margin-top: .25rem;
        }

        .badge {
            display: inline-block; padding: .2rem .6rem;
            border-radius: 12px; font-size: .75rem;
            font-weight: 600; text-transform: uppercase;
        }
        .estado-pendiente   { background: #ecc94b; color: #744210; }
        .estado-preparando  { background: #ed8936; color: #7b341e; }
        .estado-listo       { background: #48bb78; color: #22543d; }
        .estado-servido     { background: #4a5568; color: #cbd5e0; }
        .estado-anulado     { background: #718096; color: #1a202c; }

        .acciones {
            display: flex; gap: .4rem;
            margin-top: .5rem; flex-wrap: wrap;
        }
        .btn {
            padding: .35rem .7rem; border: 0; border-radius: 4px;
            cursor: pointer; font-size: .8rem; font-weight: 500;
            color: #fff; background: #4a5568;
            transition: opacity .15s;
        }
        .btn:hover:not(:disabled) { opacity: .85; }
        .btn-preparar { background: #ed8936; }
        .btn-listo    { background: #48bb78; }
        .btn-servir   { background: #4299e1; }
        .btn:disabled { opacity: .35; cursor: not-allowed; }

        /* Toast de aviso */
        #toast {
            position: fixed; top: 1rem; right: 1rem;
            background: #2c5282; color: #fff;
            padding: .9rem 1.3rem; border-radius: 6px;
            box-shadow: 0 4px 16px rgba(0,0,0,.4);
            font-weight: 500;
            opacity: 0; pointer-events: none;
            transform: translateY(-20px);
            transition: opacity .3s, transform .3s;
            z-index: 1000;
        }
        #toast.visible {
            opacity: 1; transform: translateY(0);
        }
    </style>
</head>
<body data-csrf="<?= htmlspecialchars($csrf_token) ?>">

    <header>
        <h1>🍳 Panel de cocina</h1>
        <div class="meta">
            <span>
                <span id="estado-conexion" title="Polling activo"></span>
                <span id="ultima-actualizacion">conectando…</span>
            </span>
            <span>
                <?= htmlspecialchars($usuario['nombre']) ?>
                (<?= htmlspecialchars($usuario['rol']) ?>)
            </span>
            <a href="<?= BASE_URL ?>/logout">Cerrar sesión</a>
        </div>
    </header>

    <main>
        <div id="contenido"></div>
    </main>

    <div id="toast"></div>

    <script>
    (function () {
        'use strict';

        const URL_PEDIDOS = '<?= BASE_URL ?>/cocina/pedidos.json';
        const URL_ESTADO  = '<?= BASE_URL ?>/cocina/linea/estado';
        const CSRF_TOKEN  = document.body.dataset.csrf;
        const INTERVALO_MS = 4000;

        // Datos iniciales renderizados en servidor (para evitar parpadeo
        // al cargar la página).
        const datosIniciales = <?= json_encode(
            array_map(function (array $p): array {
                return [
                    'id'          => (int) $p['id'],
                    'mesa_numero' => (int) $p['mesa_numero'],
                    'hora'        => date('H:i', strtotime((string) $p['creado_en'])),
                    'estado'      => (string) $p['estado'],
                    'lineas'      => array_map(function (array $l): array {
                        return [
                            'id'           => (int) $l['id'],
                            'plato_nombre' => (string) $l['plato_nombre'],
                            'cantidad'     => (int) $l['cantidad'],
                            'nota'         => $l['nota'] !== null ? (string) $l['nota'] : null,
                            'estado'       => (string) $l['estado'],
                        ];
                    }, $p['lineas'] ?? []),
                ];
            }, $pedidos),
            JSON_UNESCAPED_UNICODE
        ) ?>;

        let pedidosPrevios = datosIniciales;
        let idsPrevios = new Set(datosIniciales.map(p => p.id));

        // ---------- Renderizado ----------
        const ETIQUETAS = {
            PENDIENTE: 'Pendiente',
            EN_PREPARACION: 'Preparando',
            LISTO: 'Listo',
            SERVIDO: 'Servido',
            ANULADO: 'Anulado'
        };
        const CLASES = {
            PENDIENTE: 'estado-pendiente',
            EN_PREPARACION: 'estado-preparando',
            LISTO: 'estado-listo',
            SERVIDO: 'estado-servido',
            ANULADO: 'estado-anulado'
        };

        function escapar(texto) {
            const div = document.createElement('div');
            div.textContent = texto ?? '';
            return div.innerHTML;
        }

        function botonesParaLinea(linea) {
            // Reglas de transición permitidas a cocina.
            const transiciones = {
                PENDIENTE:      { siguiente: 'EN_PREPARACION', etiqueta: '▶ Preparar', clase: 'btn-preparar' },
                EN_PREPARACION: { siguiente: 'LISTO',          etiqueta: '✓ Listo',    clase: 'btn-listo' },
                LISTO:          { siguiente: 'SERVIDO',        etiqueta: '🍽 Servido', clase: 'btn-servir' }
            };
            const t = transiciones[linea.estado];
            if (!t) return ''; // SERVIDO o ANULADO: sin acciones
            return `
                <div class="acciones">
                    <button class="btn ${t.clase}"
                            data-linea-id="${linea.id}"
                            data-nuevo-estado="${t.siguiente}">
                        ${t.etiqueta}
                    </button>
                </div>
            `;
        }

        function renderLinea(linea) {
            const claseEstado = CLASES[linea.estado] || '';
            const etiqueta    = ETIQUETAS[linea.estado] || linea.estado;
            const nota = linea.nota
                ? `<div class="linea-nota">✏ ${escapar(linea.nota)}</div>`
                : '';
            return `
                <li class="linea" data-linea-id="${linea.id}">
                    <div class="linea-superior">
                        <div class="linea-titulo">
                            <span class="linea-cantidad">×${linea.cantidad}</span>
                            ${escapar(linea.plato_nombre)}
                        </div>
                        <span class="badge ${claseEstado}">${escapar(etiqueta)}</span>
                    </div>
                    ${nota}
                    ${botonesParaLinea(linea)}
                </li>
            `;
        }

        function renderPedido(pedido, esNuevo) {
            const lineasHtml = pedido.lineas.map(renderLinea).join('');
            const claseEntrando = esNuevo ? ' entrando' : '';
            return `
                <article class="pedido estado-${pedido.estado}${claseEntrando}"
                         data-pedido-id="${pedido.id}">
                    <div class="pedido-cabecera">
                        <div>
                            <div class="pedido-mesa">Mesa ${pedido.mesa_numero}</div>
                            <div class="pedido-id">Pedido #${pedido.id}</div>
                        </div>
                        <div class="pedido-hora">${escapar(pedido.hora)}</div>
                    </div>
                    <ul class="lineas">${lineasHtml}</ul>
                </article>
            `;
        }

        function render(pedidos, idsNuevos) {
            const cont = document.getElementById('contenido');
            if (pedidos.length === 0) {
                cont.innerHTML = '<div class="vacio">✅ No hay pedidos activos en este momento.</div>';
                return;
            }
            cont.innerHTML = '<div class="grid-pedidos">'
                + pedidos.map(p => renderPedido(p, idsNuevos.has(p.id))).join('')
                + '</div>';
        }

        // ---------- Toast ----------
        let toastTimer = null;
        function mostrarToast(texto) {
            const t = document.getElementById('toast');
            t.textContent = texto;
            t.classList.add('visible');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => t.classList.remove('visible'), 3500);
        }

        // Beep sintético (sin archivos externos) usando WebAudio.
        function beep() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(0.001, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.15, ctx.currentTime + 0.01);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } catch (e) { /* navegador no soporta WebAudio */ }
        }

        // ---------- Polling ----------
        async function refrescar() {
            const indicador = document.getElementById('estado-conexion');
            const sello     = document.getElementById('ultima-actualizacion');
            try {
                const r = await fetch(URL_PEDIDOS, { credentials: 'same-origin' });
                if (!r.ok) throw new Error('HTTP ' + r.status);
                const data = await r.json();
                if (!data.ok) throw new Error('Respuesta no OK');

                const pedidos = data.pedidos;
                const idsActuales = new Set(pedidos.map(p => p.id));

                // Detectar pedidos nuevos
                const idsNuevos = new Set();
                for (const id of idsActuales) {
                    if (!idsPrevios.has(id)) idsNuevos.add(id);
                }

                if (idsNuevos.size > 0) {
                    mostrarToast('🔔 ' + idsNuevos.size + ' pedido(s) nuevo(s)');
                    beep();
                }

                render(pedidos, idsNuevos);

                pedidosPrevios = pedidos;
                idsPrevios = idsActuales;
                indicador.classList.remove('error');
                sello.textContent = 'actualizado ' + new Date().toLocaleTimeString();
            } catch (err) {
                indicador.classList.add('error');
                sello.textContent = 'sin conexión — reintentando…';
                console.error('[polling]', err);
            }
        }

        // ---------- Botones de cambio de estado ----------
        document.addEventListener('click', async (ev) => {
            const btn = ev.target.closest('.btn[data-linea-id]');
            if (!btn) return;
            ev.preventDefault();

            const lineaId      = btn.dataset.lineaId;
            const nuevoEstado  = btn.dataset.nuevoEstado;
            btn.disabled = true;

            try {
                const fd = new FormData();
                fd.append('csrf_token', CSRF_TOKEN);
                fd.append('linea_id', lineaId);
                fd.append('nuevo_estado', nuevoEstado);

                const r = await fetch(URL_ESTADO, {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });
                const data = await r.json();
                if (!data.ok) throw new Error(data.error || 'Error');

                // Refrescamos al instante en lugar de esperar al siguiente tick.
                await refrescar();
            } catch (err) {
                btn.disabled = false;
                mostrarToast('⚠ ' + err.message);
                console.error(err);
            }
        });

        // ---------- Arranque ----------
        // Render inicial con los datos del servidor (sin marca de "nuevo").
        render(datosIniciales, new Set());
        document.getElementById('ultima-actualizacion').textContent = 'cargado';

        // Lanzar polling.
        refrescar();
        setInterval(refrescar, INTERVALO_MS);
    })();
    </script>
</body>
</html>
