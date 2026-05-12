<?php
session_start();
if (!isset($_SESSION['rol'])) {
    header("Location: login.php");
    exit();
}

$rol = $_SESSION['rol']; // admin o empleado
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>💎 Joyería Sahori - Panel de Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #faf6f0;
            color: #333;
            margin: 0;
            padding: 0;
            transition: background 0.3s, color 0.3s;
        }

        /* 🌙 Modo oscuro */
        .dark-mode {
            background-color: #1e1e1e;
            color: white;
        }
        .dark-mode .card {
            background-color: #2e2e2e;
        }
        .dark-mode .btn {
            background-color: #d4af37;
            color: black;
        }

        /* Encabezado */
        .header {
            background: linear-gradient(to right, #d4af37, #b8860b);
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header span {
            font-size: 18px;
        }

        /* Contenedor principal */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            text-align: center;
        }

        .container p {
            font-size: 1.1em;
            margin-bottom: 25px;
        }

        /* Tarjetas */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            justify-content: center;
        }

        .card {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 18px rgba(0,0,0,0.2);
        }

        .card i {
            font-size: 2em;
            color: #b8860b;
            margin-bottom: 10px;
        }

        .card h3 {
            font-size: 1.5em;
            color: #b8860b;
            margin: 10px 0;
        }

        .card p {
            color: #555;
        }

        /* Botones */
        .btn {
            display: inline-block;
            background-color: #b8860b;
            color: white;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 10px;
            font-weight: bold;
            transition: 0.3s;
        }

        .btn:hover {
            background-color: #8b6508;
            transform: scale(1.05);
        }

        .btn.logout {
            background-color: #b02a37;
            display: block;
            width: 220px;
            text-align: center;
            margin: 40px auto 0;
        }

        .btn.logout:hover {
            background-color: #7a1f27;
        }

        /* Pie de página */
        .footer {
            background-color: #b8860b;
            color: white;
            text-align: center;
            padding: 15px;
            margin-top: 50px;
            font-size: 14px;
        }

        /* Gráfico de ventas */
        .chart-container {
            width: 80%;
            margin: 40px auto;
        }
    </style>
</head>
<body>

    <div class="header">
        <div>Panel de Control - Joyería de Plata Sahori</div>
        <span>💎 Bienvenido, <?= htmlspecialchars($rol) ?> 💎</span>
    </div>

    <div class="container">
        <p>Bienvenido al sistema de administración de la <strong>Joyería de Plata Sahori</strong>.</p>

        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-ring"></i>
                <h3>Productos</h3>
                <p>Administra el inventario de joyas disponibles.</p>
                <a href="productos.php" class="btn">Gestionar Productos</a>
            </div>

            <div class="card">
                <i class="fas fa-user-friends"></i>
                <h3>Clientes</h3>
                <p>Consulta y gestiona la información de los clientes.</p>
                <a href="clientes.php" class="btn">Gestionar Clientes</a>
            </div>

            <div class="card">
                <i class="fas fa-hand-holding-heart"></i>
                <h3>Apartados</h3>
                <p>Registra y controla los apartados de productos.</p>
                <a href="apartados.php" class="btn">Gestionar Apartados</a>
            </div>

            <div class="card">
                <i class="fas fa-box-open"></i>
                <h3>Pedidos Especiales</h3>
                <p>Controla pedidos personalizados de los clientes.</p>
                <a href="pedidos.php" class="btn">Gestionar Pedidos</a>
            </div>

            <div class="card">
                <i class="fas fa-cash-register"></i>
                <h3>Ventas</h3>
                <p>Consulta y registra las ventas realizadas.</p>
                <a href="ventas.php" class="btn">Gestionar Ventas</a>
            </div>

            <!-- TARJETA SOLO PARA ADMINISTRADOR -->
            <?php if ($rol === 'admin'): ?>
            <div class="card">
            <i class="fas fa-user-shield"></i>
            <h3>Usuarios</h3>
            <p>Administrar cuentas de empleados y administradores.</p>
            <a href="usuarios.php" class="btn">Gestionar Usuarios</a>
</div>
<?php endif; ?>

        </div>

        <div class="chart-container">
            <canvas id="ventasChart"></canvas>
        </div>

        <a href="logout.php" class="btn logout">🚪 Cerrar Sesión</a>
    </div>

    <div class="footer">
        &copy; 2025 Joyería de Plata Sahori. Todos los derechos reservados.
    </div>
</body>
</html>
