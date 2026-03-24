<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Demasiados intentos | Consorcios Villegas EIRL</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <link rel="stylesheet" href="{{ asset('css/adminlte.css') }}">
    <link rel="stylesheet" href="{{ asset('bootstrap-icons-1.13.1/bootstrap-icons.min.css') }}">

    <style>
        body {
            background: #f4f6f9;
        }
        .error-box {
            max-width: 520px;
            margin: 10vh auto;
            background: #fff;
            border-radius: 12px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
        }
        .error-code {
            font-size: 3rem;
            font-weight: 700;
            margin-top: .5rem;
        }
        .error-message {
            margin-top: 1rem;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="error-box">
        <div class="error-icon">
            <i class="bi bi-shield-exclamation"></i>
        </div>

        <div class="error-code">429</div>

        <h3>Demasiados intentos</h3>

        <p class="error-message">
            Has realizado demasiados intentos de acceso en un corto período de tiempo.
            Por razones de seguridad, el acceso ha sido temporalmente restringido.
        </p>

        <p class="text-muted">
            Por favor, espera unos minutos e inténtalo nuevamente.
        </p>

        <div class="mt-4">
            <a href="{{ route('login') }}" class="btn btn-primary">
                <i class="bi bi-arrow-left-circle"></i>
                Volver al inicio de sesión
            </a>
        </div>
    </div>
</body>
</html>