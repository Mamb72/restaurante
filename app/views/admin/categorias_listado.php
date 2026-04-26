<?php
/**
 * app/views/admin/categorias_listado.php
 * Listado completo de categorías con acciones.
 *
 * Variables esperadas:
 *  - $titulo (string)
 *  - $usuario (array)
 *  - $seccion_activa (string)
 *  - $categorias (array)   con 'platos_activos' y 'platos_totales' añadidos
 *  - $csrf_token (string)
 *  - $flash (?array)
 */

declare(strict_types=1);

require BASE_PATH . '/app/views/admin/_layout_inicio.php';
?>

<style>
    .barra-acciones {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.2rem;
    }
    .btn {
        display: inline-block;
        padding: .55rem 1rem;
        border-radius: 6px;
        font-size: .9rem;
        font-weight: 500;
        cursor: pointer;
        border: 0;
        text-decoration: none;
        transition: opacity .15s;
    }
    .btn:hover { opacity: .85; }
    .btn-primario  { background: #4299e1; color: #fff; }
    .btn-secund    { background: #edf2f7; color: #2d3748; }
    .btn-peligro   { background: #f56565; color: #fff; }
    .btn-exito     { background: #48bb78; color: #fff; }
    .btn-pequeño   { padding: .3rem .7rem; font-size: .8rem; }

    .flash {
        padding: .8rem 1.1rem;
        border-radius: 6px;
        margin-bottom: 1.2rem;
        font-size: .95rem;
    }
    .flash-ok    { background: #c6f6d5; color: #22543d; border-left: 4px solid #48bb78; }
    .flash-error { background: #fed7d7; color: #742a2a; border-left: 4px solid #f56565; }

    table.tabla {
        width: 100%;
        background: #fff;
        border-collapse: collapse;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
    .tabla thead {
        background: #2d3748;
        color: #fff;
    }
    .tabla th, .tabla td {
        padding: .8rem 1rem;
        text-align: left;
        border-bottom: 1px solid #edf2f7;
    }
    .tabla th { font-size: .8rem; text-transform: uppercase; letter-spacing: .03em; }
    .tabla tbody tr:last-child td { border-bottom: 0; }
    .tabla tbody tr.inactiva { background: #fafafa; color: #a0aec0; }
    .tabla tbody tr.inactiva td { font-style: italic; }

    .tabla .acciones {
        display: flex;
        gap: .4rem;
        flex-wrap: wrap;
    }

    .badge {
        display: inline-block;
        padding: .15rem .55rem;
        border-radius: 12px;
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .badge-activa   { background: #c6f6d5; color: #22543d; }
    .badge-inactiva { background: #fed7d7; color: #742a2a; }

    .vacio {
        background: #fff;
        padding: 2.5rem;
        border-radius: 8px;
        text-align: center;
        color: #718096;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
    }
</style>

<?php if ($flash !== null): ?>
    <div class="flash flash-<?= htmlspecialchars($flash['tipo']) ?>">
        <?= htmlspecialchars($flash['mensaje']) ?>
    </div>
<?php endif; ?>

<div class="barra-acciones">
    <p style="color:#718096; margin:0;">
        <?= count($categorias) ?> categoría(s) en total.
    </p>
    <a href="<?= BASE_URL ?>/admin/categorias/nueva" class="btn btn-primario">
        + Nueva categoría
    </a>
</div>

<?php if (empty($categorias)): ?>
    <div class="vacio">
        Aún no hay categorías. Crea la primera para empezar a montar la carta.
    </div>
<?php else: ?>
    <table class="tabla">
        <thead>
            <tr>
                <th style="width: 60px;">Orden</th>
                <th>Nombre</th>
                <th style="width: 120px;">Platos</th>
                <th style="width: 100px;">Estado</th>
                <th style="width: 280px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($categorias as $cat): ?>
            <tr class="<?= $cat['activa'] ? '' : 'inactiva' ?>">
                <td><?= (int) $cat['orden'] ?></td>
                <td><strong><?= htmlspecialchars($cat['nombre']) ?></strong></td>
                <td>
                    <?= (int) $cat['platos_activos'] ?> activos
                    <?php if ($cat['platos_totales'] > $cat['platos_activos']): ?>
                        <span style="color:#a0aec0;">
                            (<?= (int) $cat['platos_totales'] ?> total)
                        </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($cat['activa']): ?>
                        <span class="badge badge-activa">Activa</span>
                    <?php else: ?>
                        <span class="badge badge-inactiva">Inactiva</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="acciones">
                        <a href="<?= BASE_URL ?>/admin/categorias/<?= (int) $cat['id'] ?>/editar"
                           class="btn btn-secund btn-pequeño">
                            Editar
                        </a>
                        <form method="post"
                              action="<?= BASE_URL ?>/admin/categorias/<?= (int) $cat['id'] ?>/toggle"
                              style="display:inline; margin:0;"
                              onsubmit="return confirm('<?= $cat['activa'] ? '¿Desactivar' : '¿Reactivar' ?> la categoría \'<?= htmlspecialchars(addslashes($cat['nombre'])) ?>\'?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                            <button type="submit"
                                    class="btn btn-pequeño <?= $cat['activa'] ? 'btn-peligro' : 'btn-exito' ?>">
                                <?= $cat['activa'] ? 'Desactivar' : 'Reactivar' ?>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require BASE_PATH . '/app/views/admin/_layout_fin.php'; ?>
