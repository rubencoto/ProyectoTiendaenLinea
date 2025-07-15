<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Verificar Cuenta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f2f2f2;
            min-height: 100vh;
        }
        .header {
            background-color: #232f3e;
            color: white;
            padding: 15px;
            text-align: center;
            letter-spacing: 1px;
            font-size: 1.5rem;
            font-weight: 600;
        }
        .container-verificacion {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
        }
        .card {
            background: white;
            padding: 32px 28px 24px 28px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 370px;
            width: 100%;
            margin: 0 auto;
        }
        .card h2 {
            margin-bottom: 18px;
            color: #232f3e;
            font-weight: 700;
        }
        .form-label {
            color: #232f3e;
            font-weight: 500;
            text-align: left;
            display: block;
        }
        .form-control {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            margin-bottom: 16px;
            padding: 10px;
            font-size: 1rem;
        }
        .form-control:focus {
            border-color: #007185;
            box-shadow: 0 0 0 0.15rem rgba(0,113,133,0.08);
        }
        .btn-success {
            background-color: #007185;
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 6px;
            width: 100%;
            padding: 10px 0;
            transition: background 0.2s;
        }
        .btn-success:hover {
            background-color: #232f3e;
        }
    </style>
</head>
<body>
    <div class="header">
        Verificación de cuenta
    </div>
    <div class="container-verificacion">
        <div class="card">
            <h2>Ingresa tu código</h2>
            <form action="../controlador/verificarCuenta.php" method="POST">
                <div class="mb-3 text-start">
                    <label for="correo" class="form-label">Correo electrónico</label>
                    <input type="email" class="form-control" name="correo" required>
                </div>
                <div class="mb-3 text-start">
                    <label for="codigo" class="form-label">Código de verificación</label>
                    <input type="text" class="form-control" name="codigo" required>
                </div>
                <button type="submit" class="btn btn-success">Verificar</button>
            </form>
        </div>
    </div>
</body>
</html>