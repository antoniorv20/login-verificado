<?php
include_once 'data/usuariobd.php';

$usuarioBD = new UsuarioBD();

//comprobar si se ha recibido un token
if(isset($_GET['token'])){
    $token = $_GET['token'];
    $resultado = $usuarioBD->verificarToken($token);
    $mensaje = $resultado['message'];
}else{
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de cuenta</title>
    <link rel="stylesheet" href="css/estilos.css">
</head>
<body>
    <div class="container">
        <h1>Verificación de cuenta</h1>
        <p class="mensaje"><?php echo $mensaje; ?></p>
        <a href="index.php" class="boton">Ir a Iniciar Sesión</a>
    </div>
</body>
</html>