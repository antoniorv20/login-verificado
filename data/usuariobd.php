<?php

include_once 'config.php';

class UsuarioBD
{
    private $conn;
    private $url = 'http://localhost/login-verificado';

    public function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            die("Error en la conexion: " . $this->conn->connect_error);
        }
    }

    //funcion para enviar correo simulado
    public function enviarCorreoSimulado($destinatario, $asunto, $mensaje)
    {
        $archivo_log = __DIR__ . '/correos_simulados.log';
        $contenido = "Fecha: " . date('Y-m-d H:i:s' . "\n");
        $contenido .= "Para:  $destinatario\n";
        $contenido .= "Asunto: $asunto\n";
        $contenido .= "Mensaje:\n$mensaje\n";
        $contenido .= "___________________________\n\n";

        file_put_contents($archivo_log, $contenido, FILE_APPEND);

        return ["success" => true, "message" => "Registrado con exito, por favor revise su correo"];
    }

    //generar un token aleatorio
    public function generarToken()
    {
        return bin2hex(random_bytes(32));
    }

    public function registrarUsuario($email, $password, $verificado = 0)
    {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $token = $this->generarToken();

        $sql = "INSERT INTO usuarios (email, password, token, verificado) VALUES (?,?,?,?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssi", $email, $password, $token, $verificado);

        if ($stmt->execute()) {
            $mensaje = "Por favor, verifica tu cuenta haciendo clic en este enlace: $this->url/verificar.php?token=$token";
            return $this->enviarCorreoSimulado($email, "Verificacion de cuenta", $mensaje);
        } else {
            return ["success" => false, "message" => "Error en el registro: " . $stmt->error];
        }
    }

    public function verificarToken($token)
    {
        //buscar al usuario con el token recibido
        $sql = "SELECT id FROM usuarios where token = ? AND verificado = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            $update_sql = "UPDATE usuarios set verificado = 1, token = null where id= ?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user_id);

            $resultado = ["success" => 'error', "message" => "Hubo un error al verificar tu cuenta"];

            if ($update_stmt->execute()) {
                $resultado = ["success" => 'success', "message" => "Tu cuenta ha sido verificada, Ahora puedes iniciar sesion"];
            }
        } else {
            $resultado = ["success" => 'error', "message" => "Token no valido"];
        }
        return $resultado;

    }

    public function inicioSesion($email, $password)
    {
        $sql = "SELECT id, email, password, verificado from usuarios where email  =?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultado = ["success" => 'info', "message" => "Usuario no encontrado"];

        if ($row = $result->fetch_assoc()) {
            if ($row['verificado'] == 1 && password_verify($password, $row['password'])) {
                $resultado = ["success" => "success", "message" => "Has iniciado sesion con " . $email, "id" => $row['id']];
                //actualiza la fecha del ultimo inicio de sesion
                $sql = "UPDATE usuarios set ultima_conexion = CURRENT_TIMESTAMP WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
            }
        } else {
            $resultado = ["success" => "error", "message" => "Credenciales invalidas o cuenta no verificada"];
        }
        return $resultado;
    }

    public function recuperarPassword($email)
    {
        //verificar si exsite el correo en la bd
        $check_sql = "SELECT id from usuarios where email = ?";
        $check_stmt = $this->conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();

        $result = $check_stmt->get_result();

        $resultado = ["success" => 'info', "message" => "El correo electronico proporcionado no corresponde a ningun usuario registrado"];

        if ($result->num_rows > 0) {
            $token = $this->generarToken();

            $sql = "UPDATE usuarios set token_recuperacion = ? where email = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ss", $token, $email);

            //ejecuta la consulta
            if ($stmt->execute()) {
                $mensaje = "Para restablecer tu contraseña, haz click en este enlace: $this->url/restablecer.php?token=$token";

                $this->enviarCorreoSimulado($email, "Recuperacion de contraseña", $mensaje);
                $resultado = ["success" => 'success', "message" => "Se ha eviado un enlace de recuperacion a tu correo"];

            } else {
                $resultado = ["success" => 'success', "message" => "Error al procesar la solicitud"];
            }
        }
        return $resultado;
    }

    public function restablecerPassword($token, $nueva_password)
    {
        $password = password_hash($nueva_password, PASSWORD_DEFAULT);

        //buscamos al usuario en el token proporcionado
        $sql = "SELECT id from usuarios where token_recuperacion = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        $resultado = ["success" => 'info', "message" => "El token de recuperacion no es valido o ya a sido utilizado"];

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $user_id = $row['id'];

            //actualizart la contraseña y eliminar el token de recuperacion
            $update_sql = "UPDATE usuarios set password =?, token_recuperacion = null where id =?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("si", $password, $user_id);


            if ($update_stmt->execute()) {
                $resultado = ["success" => 'success', "message" => "Tu contraseña ha sido actualizada correctamente"];
            } else {
                $resultado = ["success" => 'error', "message" => "Hubo un problema al actualizar tu contraseña. Intentelo de nuevo"];
            }
        }
        return $resultado;
    }
}
