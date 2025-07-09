<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Vendedor</title>
    <style>
        /* Estilos generales del cuerpo */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        /* Encabezado superior */
        .header {
            background-color: #232f3e;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
        }

        /* Contenedor principal */
        .container {
            max-width: 900px;
            margin: 40px auto;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            text-align: center;
        }

        .container h2 {
            margin-bottom: 20px;
        }

        /* Botones de acciones */
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }

        .actions a {
            text-decoration: none;
        }

        .actions button {
            padding: 15px 25px;
            font-size: 16px;
            background-color: #f0c14b;
            border: 1px solid #a88734;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s ease;
            width: 200px;
        }

        .actions button:hover {
            background-color: #e2b33d;
        }

        /* Pie de página */
        .footer {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 50px;
        }
    </style>
</head>
<body>

    <!-- Encabezado de bienvenida -->
    <div class="header">
        <h1>Bienvenido, Vendedor</h1>
    </div>

    <!-- Contenedor principal con acciones -->
    <div class="container">
        <h2>¿Qué deseas hacer hoy?</h2>

        <div class="actions">
            <!-- Botón para ver productos -->
            <a href="productos.php"><button>📦 Ver productos</button></a>
            <!-- Botón para agregar producto -->
            <a href="agregarProducto.php"><button>➕ Agregar producto</button></a>
            <!-- Botón para cerrar sesión (sin funcionalidad aún) -->
            <a href="#"><button style="background:#e74c3c;border:none;">🚪 Cerrar sesión</button></a>
        </div>
    </div>

    <!-- Pie de página con derechos reservados y año actual -->
    <div class="footer">
        &copy; <?= date("Y") ?> Plataforma de Vendedores - Todos los derechos reservados.
    </div>

</body>
</html>
