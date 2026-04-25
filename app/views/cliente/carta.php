<?php
/**
 * @var string $titulo
 * @var array  $categorias
 * @var array  $platosPorCategoria
 * @var array  $destacados
 */

/**
 * Helper local para mostrar precio en formato europeo: 9,50 €
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
    <title><?= htmlspecialchars($titulo) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; max-width: 800px; margin: 0 auto; padding: 1rem; color: #222; background: #fafafa; }
        h1 { color: #2a7a2a; margin-bottom: 0.2rem; }
        h2 { color: #2a7a2a; border-bottom: 2px solid #2a7a2a; padding-bottom: 0.3rem; margin-top: 2.5rem; }
        .subtitulo { color: #666; margin-top: 0; }
        .destacados { background: linear-gradient(135deg, #fff8e1, #ffe082); padding: 1rem 1.5rem; border-radius: 12px; margin: 1.5rem 0; border: 2px dashed #f9a825; }
        .destacados h2 { border: none; color: #e65100; margin-top: 0; }
        .plato { background: white; padding: 1rem; margin-bottom: 0.8rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; }
        .plato-info { flex: 1; }
        .plato-nombre { font-weight: 600; font-size: 1.05rem; margin: 0; }
        .plato-desc { color: #666; font-size: 0.92rem; margin: 0.3rem 0; }
        .plato-precio { font-weight: 700; color: #2a7a2a; font-size: 1.1rem; white-space: nowrap; }
        .badges { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.4rem; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; }
        .badge-alergeno { background: #ffebee; color: #c62828; }
        .badge-etiqueta { background: #e8f5e9; color: #2e7d32; }
        .badge-destacado { background: #fff3e0; color: #ef6c00; margin-left: 0.5rem; }
        .agotado { opacity: 0.5; }
        .agotado-tag { color: #999; font-size: 0.8rem; font-style: italic; }
        .vacio { color: #999; padding: 1rem; text-align: center; font-style: italic; }
    </style>
</head>
<body>
    <h1><?= htmlspecialchars($titulo) ?></h1>
    <p class="subtitulo">Bienvenido. Esta es nuestra carta digital.</p>

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
                    <span class="plato-precio"><?= fmtPrecio($d['precio']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php foreach ($categorias as $cat): ?>
        <h2><?= htmlspecialchars($cat['nombre']) ?></h2>

        <?php $platos = $platosPorCategoria[$cat['id']] ?? []; ?>
        <?php if (empty($platos)): ?>
            <p class="vacio">Sin platos en esta categoría.</p>
        <?php else: ?>
            <?php foreach ($platos as $plato): ?>
                <div class="plato <?= !$plato['disponible'] ? 'agotado' : '' ?>">
                    <div class="plato-info">
                        <p class="plato-nombre">
                            <?= htmlspecialchars($plato['nombre']) ?>
                            <?php if ($plato['destacado']): ?>
                                <span class="badge badge-destacado">⭐ destacado</span>
                            <?php endif; ?>
                            <?php if (!$plato['disponible']): ?>
                                <span class="agotado-tag">(agotado)</span>
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($plato['descripcion'])): ?>
                            <p class="plato-desc"><?= htmlspecialchars($plato['descripcion']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($plato['etiquetas']) || !empty($plato['alergenos'])): ?>
                            <div class="badges">
                                <?php foreach ($plato['etiquetas'] as $et): ?>
                                    <span class="badge badge-etiqueta">
                                        <?= htmlspecialchars($et['nombre']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php foreach ($plato['alergenos'] as $al): ?>
                                    <span class="badge badge-alergeno" title="Alérgeno">
                                        <?= htmlspecialchars($al['nombre']) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <span class="plato-precio"><?= fmtPrecio($plato['precio']) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <p style="text-align: center; color: #999; margin-top: 3rem; font-size: 0.85rem;">
        Fase 6.3 completada · MVC funcionando con datos reales de MariaDB
    </p>
</body>
</html>
