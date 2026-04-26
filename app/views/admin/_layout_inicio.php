<?php
/**
 * app/views/admin/_layout_inicio.php
 * Apertura del layout común de las vistas del admin.
 *
 * Variables esperadas en cada vista que lo incluya:
 *  - $titulo (string)         título de la página, se concatena en <title>
 *  - $usuario (array)         datos del admin en sesión
 *  - $seccion_activa (string) clave de la sección actual; se usa para
 *                             marcar como activo el enlace correspondiente.
 *
 * Cada vista del admin tiene la siguiente estructura:
 *   <?php require ... '_layout_inicio.php'; ?>
 *   ...HTML específico de la vista...
 *   <?php require ... '_layout_fin.php'; ?>
 */

declare(strict_types=1);

/** Devuelve 'activo' si la clave dada coincide con la sección actual. */
function clasePorSeccion(string $clave, string $activa): string
{
    return $clave === $activa ? 'activo' : '';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($titulo) ?> — Administración</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            margin: 0;
            background: #f4f6f8;
            color: #2d3748;
            display: flex;
            min-height: 100vh;
        }

        /* ---------- Sidebar ---------- */
        aside.sidebar {
            width: 240px;
            background: #1a202c;
            color: #e2e8f0;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
        }
        aside.sidebar header {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid #2d3748;
        }
        aside.sidebar header h1 {
            margin: 0;
            font-size: 1.05rem;
            color: #fff;
        }
        aside.sidebar header small {
            color: #a0aec0;
            font-size: .75rem;
        }
        nav.menu {
            flex: 1;
            padding: 0.6rem 0;
        }
        nav.menu a {
            display: block;
            padding: .7rem 1.1rem;
            color: #cbd5e0;
            text-decoration: none;
            font-size: .92rem;
            border-left: 3px solid transparent;
            transition: background .15s, border-color .15s;
        }
        nav.menu a:hover {
            background: #2d3748;
            color: #fff;
        }
        nav.menu a.activo {
            background: #2d3748;
            color: #fff;
            border-left-color: #4299e1;
            font-weight: 500;
        }
        nav.menu .grupo {
            padding: .6rem 1.1rem .2rem 1.1rem;
            font-size: .7rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        aside.sidebar footer {
            padding: 0.9rem 1rem;
            border-top: 1px solid #2d3748;
            font-size: .85rem;
        }
        aside.sidebar footer .nombre {
            color: #fff;
            font-weight: 500;
        }
        aside.sidebar footer .rol {
            color: #a0aec0;
            font-size: .75rem;
        }
        aside.sidebar footer a {
            color: #63b3ed;
            text-decoration: none;
            font-size: .85rem;
            display: inline-block;
            margin-top: .4rem;
        }
        aside.sidebar footer a:hover { text-decoration: underline; }

        /* ---------- Contenido ---------- */
        main.contenido {
            flex: 1;
            padding: 1.8rem 2rem;
            overflow-x: auto;
        }
        main.contenido > h1 {
            margin: 0 0 1.4rem 0;
            font-size: 1.6rem;
            color: #1a202c;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <header>
        <h1>🍽 Restaurante</h1>
        <small>Panel de administración</small>
    </header>

    <nav class="menu">
        <div class="grupo">General</div>
        <a href="<?= BASE_URL ?>/admin"
           class="<?= clasePorSeccion('dashboard', $seccion_activa) ?>">
            📊 Dashboard
        </a>

        <div class="grupo">Carta</div>
        <a href="<?= BASE_URL ?>/admin/categorias"
           class="<?= clasePorSeccion('categorias', $seccion_activa) ?>">
            🗂 Categorías
        </a>
        <a href="<?= BASE_URL ?>/admin/platos"
           class="<?= clasePorSeccion('platos', $seccion_activa) ?>">
            🍕 Platos
        </a>

        <div class="grupo">Operativa</div>
        <a href="<?= BASE_URL ?>/admin/mesas"
           class="<?= clasePorSeccion('mesas', $seccion_activa) ?>">
            🪑 Mesas
        </a>
        <a href="<?= BASE_URL ?>/admin/pedidos"
           class="<?= clasePorSeccion('pedidos', $seccion_activa) ?>">
            🧾 Pedidos del día
        </a>

        <div class="grupo">Sistema</div>
        <a href="<?= BASE_URL ?>/admin/usuarios"
           class="<?= clasePorSeccion('usuarios', $seccion_activa) ?>">
            👥 Usuarios
        </a>

        <div class="grupo">Vistas externas</div>
        <a href="<?= BASE_URL ?>/cocina" target="_blank">
            🍳 Panel de cocina ↗
        </a>
        <a href="<?= BASE_URL ?>/carta" target="_blank">
            📖 Carta pública ↗
        </a>
    </nav>

    <footer>
        <div class="nombre"><?= htmlspecialchars($usuario['nombre']) ?></div>
        <div class="rol"><?= htmlspecialchars($usuario['email']) ?></div>
        <a href="<?= BASE_URL ?>/logout">Cerrar sesión</a>
    </footer>
</aside>

<main class="contenido">
    <h1><?= htmlspecialchars($titulo) ?></h1>
