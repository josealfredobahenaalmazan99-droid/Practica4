<?php
session_start();
include 'db.php';

// --- CONFIGURACIÓN DE ANTI-BRUTE ---
$max_attempts = 3;   // Intentos permitidos
$lockout_time = 30;  // Segundos de bloqueo

// Inicializar variables de sesión
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

$error = "";
$username = "";

// Calcular tiempo restante del bloqueo
$time_remaining = max(0, $lockout_time - (time() - $_SESSION['last_attempt_time']));

// Proceso de login
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if ($time_remaining > 0) {
        $error = "❌ Demasiados intentos. Espere <span id='timer'>$time_remaining</span> segundos.";
    } else {
        if (!empty($username) && !empty($password)) {

            $stmt = $conn->prepare("SELECT id, password, rol FROM usuarios WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();

                if (password_verify($password, $row['password'])) {
                    // LOGIN CORRECTO
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['rol'] = $row['rol'];

                    // Reiniciar intentos
                    $_SESSION['login_attempts'] = 0;

                    header("Location: panel.php");
                    exit;
                }
            }

            // Si llega aquí, fallo
            $_SESSION['login_attempts']++;

            if ($_SESSION['login_attempts'] >= $max_attempts) {
                $_SESSION['last_attempt_time'] = time();
                $error = "❌ Demasiados intentos. Espere <span id='timer'>$lockout_time</span> segundos.";
            } else {
                $remaining = $max_attempts - $_SESSION['login_attempts'];
                $error = "❌ Usuario o contraseña incorrectos. Intentos restantes: $remaining";
            }

        } else {
            $error = "❌ Todos los campos son obligatorios.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>💎 Joyería Arely - Iniciar Sesión</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
*{box-sizing:border-box}
body{font-family:Poppins,sans-serif;background-image:url('img/fondo-joyeria.png');background-size:cover;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.container{background:rgba(255,255,255,.96);padding:30px;border-radius:12px;width:360px;box-shadow:0 6px 20px rgba(0,0,0,.2);text-align:center}
input,select{width:100%;padding:12px;border-radius:8px;border:1px solid #ccc;margin-top:8px}
.btn{background:#b8860b;color:#fff;padding:12px;border-radius:8px;border:none;cursor:pointer;margin-top:12px;width:100%}
.link-btn{display:inline-block;margin-top:12px;color:#b8860b;cursor:pointer;text-decoration:none}
.error{background:#ffd6d6;color:#b40000;padding:10px;border-radius:8px;margin-top:12px}
.modal{position:fixed;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.5);display:none;align-items:center;justify-content:center}
.card{background:white;padding:20px;border-radius:10px;max-width:420px;width:90%}
.success{background:#d4ffd4;color:#087500;padding:10px;border-radius:8px;margin-top:12px}
.small{font-size:.9rem;color:#555}
</style>
</head>
<body>

<div class="container">
    <h1>💎 Joyería Sahori</h1>
    <p class="small">Iniciar Sesión</p>

    <?php if (!empty($error)): ?>
        <div class="error" id="errorMessage"><?= $error ?></div>
    <?php endif; ?>

    <form method="post" novalidate>
        <input type="text" name="username" placeholder="Usuario" required value="<?= htmlspecialchars($username) ?>">
        <input type="password" name="password" placeholder="Contraseña" required>

        <button class="btn" type="submit" name="login_submit" id="loginButton"
            <?= ($time_remaining > 0) ? 'disabled' : '' ?>>Entrar</button>
    </form>

    <a class="link-btn" id="openRegister">➕ Crear Usuario</a>
</div>

<!-- Modal Crear Usuario -->
<div class="modal" id="registerModal">
    <div class="card">
        <h3>Crear Usuario (requiere ADMIN)</h3>

        <form id="registerForm" onsubmit="return doRegister(event)">
            <hr>
            <label class="small">Credenciales ADMIN</label>
            <input type="text" id="admin_user" placeholder="Usuario admin" required>
            <input type="password" id="admin_pass" placeholder="Contraseña admin" required>

            <hr>
            <label class="small">Nuevo usuario</label>
            <input type="text" id="new_user" placeholder="Nuevo usuario" required>
            <input type="password" id="new_pass" placeholder="Contraseña nueva" required>

            <select id="new_role" required>
                <option value="empleado">Empleado</option>
                <option value="admin">Administrador</option>
            </select>

            <button class="btn" type="submit">Crear usuario</button>
            <button type="button" class="btn" style="background:#ccc;color:#000" onclick="closeModal()">Cancelar</button>
        </form>

        <div id="registerResult"></div>
    </div>
</div>

<script>
// Abrir modal
document.getElementById('openRegister').addEventListener('click',() =>{
    document.getElementById('registerModal').style.display='flex';
});

// Cerrar modal
function closeModal(){ document.getElementById('registerModal').style.display='none'; }

// AJAX crear usuario
function doRegister(e){
    e.preventDefault();

    fetch('register_action.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({
            admin_user:admin_user.value,
            admin_pass:admin_pass.value,
            new_user:new_user.value,
            new_pass:new_pass.value,
            new_role:new_role.value
        })
    })
    .then(r=>r.json())
    .then(data=>{
        document.getElementById('registerResult').innerHTML =
            `<div class='${data.success ? "success" : "error"}'>${data.message}</div>`;
        if(data.success){ document.getElementById('registerForm').reset(); }
    });
}

// Temporizador del bloqueo
let timer=document.getElementById('timer');
if(timer){
    let btn=document.getElementById('loginButton');
    let timeLeft=parseInt(timer.textContent);
    function countdown(){
        if(timeLeft>0){
            timeLeft--;
            timer.textContent=timeLeft;
            setTimeout(countdown,1000);
        } else {
            btn.disabled=false;
            document.getElementById('errorMessage').style.display="none";
        }
    }
    countdown();
}
</script>

</body>
</html>
