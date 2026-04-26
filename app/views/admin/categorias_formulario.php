<?php
/**
 * app/views/admin/categorias_formulario.php
 * Formulario reutilizable para crear y editar categorías.
 *
 * Variables esperadas:
 *  - $titulo (string)
 *  - $usuario (array)
 *  - $seccion_activa (string)
 *  - $modo (string)        'crear' o 'editar'
 *  - $categoria (array)    datos a precargar (claves: id, nombre, orden)
 *  - $csrf_token (string)
 *  - $errores (array)      campo => mensaje, vacío si todo OK
 */

declare(strict_types=1);

require BASE_PATH . '/app/views/admin/_layout_inicio.php';

$urlAccion = $modo === 'crear'
    ? BASE_URL . '/admin/categorias'
    : BASE_URL . '/admin/categorias/' . (int) $categoria['id'];

$textoBoton = $modo === 'crear' ? 'Crear categoría' : 'Guardar cambios';
?>

<style>
    .form-card {
        background: #fff;
        padding: 1.8rem 2rem;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,.06);
        max-width: 560px;
    }
    .campo { margin-bottom: 1.2rem; }
    .campo label {
        display: block;
        font-weight: 500;
        margin-bottom: .35rem;
        color: #2d3748;
        font-size: .92rem;
    }
    .campo input[type=text], .campo input[type=number] {
        width: 100%;
        padding: .55rem .7rem;
        border: 1px solid #cbd5e0;
        border-radius: 5px;
        font-size: 1rem;
        font-family: inherit;
    }
    .campo input:focus {
        outline: 2px solid #4299e1;
        outline-offset: -1px;
        border-color: #4299e1;
    }
    .campo .ayuda {
        color: #718096;
        font-size: .82rem;
        margin-top: .3rem;
    }
    .campo.con-error input { border-color: #f56565; }
    .campo .error {
        color: #c53030;
        font-size: .85rem;
        margin-top: .3rem;
    }
    .acciones-form {
        display: flex;
        gap: .6rem;
        margin-top: 1.5rem;
        padding-top: 1.2rem;
        border-top: 1px solid #edf2f7;
    }
    .btn {
        padding: .6rem 1.2rem;
        border-radius: 6px;
        font-size: .95rem;
        font-weight: 500;
        cursor: pointer;
        border: 0;
        text-decoration: none;
        display: inline-block;
    }
    .btn-primario { background: #4299e1; color: #fff; }
    .btn-secund   { background: #edf2f7; color: #2d3748; }
</style>

<div class="form-card">
    <form method="post" action="<?= htmlspecialchars($urlAccion) ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="campo <?= isset($errores['nombre']) ? 'con-error' : '' ?>">
            <label for="nombre">Nombre</label>
            <input
                type="text"
                id="nombre"
                name="nombre"
                value="<?= htmlspecialchars((string) $categoria['nombre']) ?>"
                maxlength="80"
                required
                autofocus
            >
            <?php if (isset($errores['nombre'])): ?>
                <div class="error"><?= htmlspecialchars($errores['nombre']) ?></div>
            <?php else: ?>
                <div class="ayuda">Ej: Entrantes, Bebidas, Postres. Máximo 80 caracteres.</div>
            <?php endif; ?>
        </div>

        <div class="campo <?= isset($errores['orden']) ? 'con-error' : '' ?>">
            <label for="orden">Orden de aparición</label>
            <input
                type="number"
                id="orden"
                name="orden"
                value="<?= (int) $categoria['orden'] ?>"
                min="0"
                max="999"
            >
            <?php if (isset($errores['orden'])): ?>
                <div class="error"><?= htmlspecialchars($errores['orden']) ?></div>
            <?php else: ?>
                <div class="ayuda">
                    Las categorías se muestran de menor a mayor orden en la carta.
                    Usa 0, 10, 20, 30… para dejar hueco a inserciones futuras.
                </div>
            <?php endif; ?>
        </div>

        <div class="acciones-form">
            <button type="submit" class="btn btn-primario">
                <?= htmlspecialchars($textoBoton) ?>
            </button>
            <a href="<?= BASE_URL ?>/admin/categorias" class="btn btn-secund">
                Cancelar
            </a>
        </div>
    </form>
</div>

<?php require BASE_PATH . '/app/views/admin/_layout_fin.php'; ?>
