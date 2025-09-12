<?php
// index.php

// Include Configuration File
include('config.php');

$login_button = '';

// Si viene el "code" en la URL, es porque Google devolvió respuesta
if (isset($_GET["code"])) {
    $token = $google_client->fetchAccessTokenWithAuthCode($_GET["code"]);

    if (!isset($token['error'])) {
        // Guardar token
        $google_client->setAccessToken($token['access_token']);
        $_SESSION['access_token'] = $token['access_token'];

        // Obtener información del usuario
        $google_service = new Google_Service_Oauth2($google_client);
        $data = $google_service->userinfo->get();

        if (!empty($data['given_name'])) {
            $_SESSION['user_first_name'] = $data['given_name'];
        }
        if (!empty($data['family_name'])) {
            $_SESSION['user_last_name'] = $data['family_name'];
        }
        if (!empty($data['email'])) {
            $_SESSION['user_email_address'] = $data['email'];
        }
        if (!empty($data['gender'])) {
            $_SESSION['user_gender'] = $data['gender'];
        }
        if (!empty($data['picture'])) {
            $_SESSION['user_image'] = $data['picture'];
        }
    }
}

// Si no hay sesión, mostrar botón de login
if (!isset($_SESSION['access_token'])) {
    $login_button = '<a class="btn btn-danger" href="' . $google_client->createAuthUrl() . '">
                        <img src="https://developers.google.com/identity/images/g-logo.png" width="20" style="margin-right:8px;">
                        Iniciar sesión con Google
                     </a>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>PHP Login usando Google</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <!-- Bootstrap -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css">
</head>
<body>
  <div class="container" style="margin-top:50px;">
    <div class="card">
      <div class="card-body text-center">
        <h3>Login con Google</h3>
        <br>
        <?php
        if ($login_button == '') {
            echo '<div class="alert alert-success">Bienvenido, ' . $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'] . '!</div>';
            echo '<img src="' . $_SESSION["user_image"] . '" class="rounded-circle img-thumbnail" width="120"><br><br>';
            echo '<h4>' . $_SESSION['user_email_address'] . '</h4>';
            echo '<a href="logout.php" class="btn btn-secondary mt-3">Cerrar sesión</a>';
        } else {
            echo $login_button;
        }
        ?>
      </div>
    </div>
  </div>
</body>
</html>
