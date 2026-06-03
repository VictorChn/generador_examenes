<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'config/conexion.php';

if (!$conexion) {
    die("Error: No se pudo conectar a la base de datos.");
}

$correo = 'admin@instituto.edu.mx';
$password_plana = 'Admin123#';
$nuevo_hash = password_hash($password_plana, PASSWORD_DEFAULT);

// Verificar si existe el admin
$stmt_check = mysqli_prepare($conexion, "SELECT id_admin FROM administradores WHERE correo = ?");
mysqli_stmt_bind_param($stmt_check, "s", $correo);
mysqli_stmt_execute($stmt_check);
mysqli_stmt_store_result($stmt_check);

if (mysqli_stmt_num_rows($stmt_check) > 0) {
    mysqli_stmt_close($stmt_check);
    
    // Actualizar hash
    $stmt_update = mysqli_prepare($conexion, "UPDATE administradores SET password = ? WHERE correo = ?");
    mysqli_stmt_bind_param($stmt_update, "ss", $nuevo_hash, $correo);
    if (mysqli_stmt_execute($stmt_update)) {
        echo "<p style='color:green; font-weight:bold; font-size:16px;'>✓ La contraseña de '$correo' se ha actualizado con éxito a '$password_plana'.</p>";
        echo "<p>Ya puedes iniciar sesión en <a href='login_admin.php'>login_admin.php</a>.</p>";
    } else {
        echo "<p style='color:red;'>Error al actualizar la contraseña: " . mysqli_error($conexion) . "</p>";
    }
    mysqli_stmt_close($stmt_update);
} else {
    mysqli_stmt_close($stmt_check);
    
    // Crear el admin si no existe
    $stmt_insert = mysqli_prepare($conexion, "INSERT INTO administradores (nombre, correo, password) VALUES (?, ?, ?)");
    $nombre = 'Administrador';
    mysqli_stmt_bind_param($stmt_insert, "sss", $nombre, $correo, $nuevo_hash);
    if (mysqli_stmt_execute($stmt_insert)) {
        echo "<p style='color:green; font-weight:bold; font-size:16px;'>✓ Se ha creado la cuenta de administrador '$correo' con la contraseña '$password_plana'.</p>";
        echo "<p>Ya puedes iniciar sesión en <a href='login_admin.php'>login_admin.php</a>.</p>";
    } else {
        echo "<p style='color:red;'>Error al registrar el administrador: " . mysqli_error($conexion) . "</p>";
    }
    mysqli_stmt_close($stmt_insert);
}
?>
