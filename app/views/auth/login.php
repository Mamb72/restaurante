<?php
/**
 * app/views/auth/login.php
 * Formulario de acceso del personal.
 *
 * Variables esperadas:
 *  - $titulo (string)
 *  - $csrf_token (string)
 *  - $error (?string)
 *  - $email_prev (string)
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
        body {
            font-family: system-ui, sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }
        .caja {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 12px rgba(0,0,0,.1);
            width: 320px;
        }
        h1 {
            margin: 0 0 1rem 0;
            font-size: 1.4rem;
        }
        label {
            display: block;
            margin-top: .8rem;
            font-size: .9rem;
            color: #333;
        }
        input[type=email], input[type=password] {
            width: 100%;
            padding: .5rem;
            margin-top: .25rem;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            margin-top: 1.2rem;
            width: 100%;
            padding: .6rem;
            background: #2c5282;
            color: #fff;
            border: 0;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background: #2a4365;
        }
        .error {
            background: #fed7d7;
            color: #742a2a;
            padding: .6rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: .9rem;
        }
        .pista {
            margin-top: 1rem;
            font-size: .8rem;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="caja">
        <h1>Acceso del personal</h1>

        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/login">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

            <label for="email">Email</label>
            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($email_prev) ?>"
                required
                autofocus
            >

            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <button type="submit">Entrar</button>
        </form>

        <p class="pista">Solo personal autorizado</p>
    </div>
</body>
</html>
