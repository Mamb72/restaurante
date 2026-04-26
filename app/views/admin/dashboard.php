<?php
/**
 * app/views/admin/dashboard.php
 * Panel principal del admin: resumen operativo del día.
 *
 * Variables esperadas:
 *  - $titulo (string)
 *  - $usuario (array)
 *  - $seccion_activa (string)
 *  - $pedidos_activos (int)
 *  - $mesas_ocupadas (int)
 *  - $total_mesas (int)
 *  - $facturacion_hoy (float)
 */

declare(strict_types=1);

require BASE_PATH . '/app/views/admin/_layout_inicio.php';

function fmtEuros(float $v): string
{
    return number_format($v, 2, ',', '.') . ' €';
}
?>

<style>
    .grid-tarjetas {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.2rem;
        margin-bottom: 2rem;
    }
    .tarjeta {
        background: #fff;
        padding: 1.4rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        border-left: 4px solid #4299e1;
    }
    .tarjeta.amarilla { border-left-color: #ecc94b; }
    .tarjeta.naranja  { border-left-color: #ed8936; }
    .tarjeta.verde    { border-left-color: #48bb78; }

    .tarjeta .etiqueta {
        font-size: .8rem;
        color: #718096;
        text-transform: uppercase;
        letter-spacing: .03em;
        margin-bottom: .4rem;
    }
    .tarjeta .valor {
        font-size: 2rem;
        font-weight: 700;
        color: #1a202c;
        line-height: 1.1;
    }
    .tarjeta .pista {
        font-size: .85rem;
        color: #718096;
        margin-top: .4rem;
    }

    .seccion-info {
        background: #fff;
        padding: 1.4rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        margin-bottom: 1.2rem;
    }
    .seccion-info h2 {
        margin: 0 0 .6rem 0;
        font-size: 1.05rem;
        color: #1a202c;
    }
    .seccion-info p {
        margin: 0;
        color: #4a5568;
        font-size: .92rem;
        line-height: 1.5;
    }
    .seccion-info ul {
        margin: .6rem 0 0 0;
        padding-left: 1.2rem;
        color: #4a5568;
        font-size: .92rem;
    }
    .seccion-info ul li { margin-bottom: .25rem; }
</style>

<div class="grid-tarjetas">
    <div class="tarjeta amarilla">
        <div class="etiqueta">Pedidos activos</div>
        <div class="valor"><?= (int) $pedidos_activos ?></div>
        <div class="pista">en cola de cocina</div>
    </div>

    <div class="tarjeta naranja">
        <div class="etiqueta">Mesas ocupadas</div>
        <div class="valor">
            <?= (int) $mesas_ocupadas ?>
            <span style="font-size:1rem; color:#718096; font-weight:500;">
                / <?= (int) $total_mesas ?>
            </span>
        </div>
        <div class="pista">con sesión abierta</div>
    </div>

    <div class="tarjeta verde">
        <div class="etiqueta">Facturación de hoy</div>
        <div class="valor"><?= fmtEuros($facturacion_hoy) ?></div>
        <div class="pista">líneas confirmadas no anuladas</div>
    </div>
</div>

<div class="seccion-info">
    <h2>Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?></h2>
    <p>
        Desde aquí puedes gestionar la carta del restaurante, las mesas,
        los usuarios del personal y consultar la operativa del día.
    </p>
</div>

<div class="seccion-info">
    <h2>Próximas funcionalidades</h2>
    <p>El panel está en construcción. Estas son las secciones que se irán habilitando:</p>
    <ul>
        <li>🗂 Categorías y 🍕 Platos — gestión completa de la carta.</li>
        <li>🪑 Mesas — alta de mesas con generación de QR.</li>
        <li>🧾 Pedidos del día — listado operativo.</li>
        <li>👥 Usuarios — alta y gestión del personal.</li>
        <li>📈 Estadísticas — facturación, plato más pedido, hora punta, valoraciones.</li>
    </ul>
</div>

<?php require BASE_PATH . '/app/views/admin/_layout_fin.php'; ?>
