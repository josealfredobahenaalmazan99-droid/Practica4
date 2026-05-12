<?php
// register_action.php
session_start();
include 'db.php';

// Recibir JSON
$input = json_decode(file_get_contents('php://input'), true);
header('Content-Type: application/json');

if (!$input) {
    echo json_encode(['success'=>false,'message'=>'Solicitud inválida.']);
    exit;
}

$admin_user = trim($input['admin_user'] ?? '');
$admin_pass = $input['admin_pass'] ?? '';
$new_user = trim($input['new_user'] ?? '');
$new_pass = $input['new_pass'] ?? '';
$new_role = in_array($input['new_role'] ?? 'empleado', ['admin','empleado']) ? $input['new_role'] : 'empleado';

// validaciones básicas
if ($admin_user === '' || $admin_pass === '' || $new_user === '' || $new_pass === '') {
    echo json_encode(['success'=>false,'message'=>'Todos los campos son obligatorios.']);
    exit;
}

// 1) ¿Existe algún admin en la tabla?
$countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE rol = 'admin'");
$countStmt->execute();
$res = $countStmt->get_result()->fetch_assoc();
$countStmt->close();

$admins_exist = ($res['total'] > 0);

// Si NO hay admin, permitir crear el primer admin sin validar credenciales administrativas
if (!$admins_exist) {
    // sólo permitir si new_role es admin (para crear primer admin) o empleado (si no quieres admin)
    // insertar directamente
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    $ins = $conn->prepare("INSERT INTO usuarios (username, password, rol) VALUES (?, ?, ?)");
    $ins->bind_param("sss", $new_user, $hash, $new_role);
    if ($ins->execute()) {
        echo json_encode(['success'=>true,'message'=>'Primer administrador/usuario creado correctamente.']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Error al crear usuario (DB).']);
    }
    $ins->close();
    exit;
}

// Si hay admins, validar credenciales del admin que intenta autorizar
$check = $conn->prepare("SELECT id, password FROM usuarios WHERE username = ? AND rol = 'admin' LIMIT 1");
$check->bind_param("s", $admin_user);
$check->execute();
$result = $check->get_result();

if ($result->num_rows !== 1) {
    echo json_encode(['success'=>false,'message'=>'Credenciales de admin inválidas.']);
    $check->close();
    exit;
}

$row = $result->fetch_assoc();
$check->close();

if (!password_verify($admin_pass, $row['password'])) {
    echo json_encode(['success'=>false,'message'=>'Contraseña de admin incorrecta.']);
    exit;
}

// Admin validado: crear nuevo usuario (verificar que no exista)
$exists = $conn->prepare("SELECT id FROM usuarios WHERE username = ? LIMIT 1");
$exists->bind_param("s", $new_user);
$exists->execute();
$exists->store_result();
if ($exists->num_rows > 0) {
    echo json_encode(['success'=>false,'message'=>'El nombre de usuario ya existe.']);
    $exists->close();
    exit;
}
$exists->close();

// Insertar nuevo usuario
$hash_new = password_hash($new_pass, PASSWORD_DEFAULT);
$ins = $conn->prepare("INSERT INTO usuarios (username, password, rol) VALUES (?, ?, ?)");
$ins->bind_param("sss", $new_user, $hash_new, $new_role);
if ($ins->execute()) {
    echo json_encode(['success'=>true,'message'=>'Usuario creado correctamente.']);
} else {
    echo json_encode(['success'=>false,'message'=>'Error al guardar usuario.']);
}
$ins->close();
exit;
