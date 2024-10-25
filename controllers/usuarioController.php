<?php

session_start();

include_once '../data/usuariobd.php';

$usuariobd = new UsuarioBD();

function redirigirConMensaje($url, $success, $mensaje){
    //almacena el resultado en la sesion
    $_SESSION['success'] = $success;
    $_SESSION['message'] = $mensaje;

    //realiza la redirección
    header("Location: $url");
    exit();
}

//registro usuario
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['registro'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $resultado = $usuariobd->registrarUsuario($email, $password);

    redirigirConMensaje('../index.php', $resultado['success'], $resultado['message']);
}

//Inicio de sesión
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $resultado = $usuariobd->inicioSesion($email, $password);

    if($resultado['success'] == "success"){
        $_SESSION['user_id'] = $resultado['id'];
    }
    redirigirConMensaje('../index.php', $resultado['success'], $resultado['message']);
}

//Recuperación de contraseña
if($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['recuperar'])){
    $email = $_POST['email'];

    $resultado = $usuariobd->recuperarPassword($email);
    redirigirConMensaje('../index.php', $resultado['success'], $resultado['message']);
}