<?php
// config/mail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/* ===========================================================
   Cargar .env (robusto, ignora comentarios y líneas inválidas)
   =========================================================== */
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = $v; 
        putenv("$k=$v");
    }
}

/* ===========================================================
   Helper para leer variables de entorno con fallback
   =========================================================== */
if (!function_exists('envOr')) {
    function envOr($keys, $default = null) {
        foreach ((array)$keys as $k) {
            $val = getenv($k);
            if ($val !== false && $val !== '') return $val;
        }
        return $default;
    }
}

/* ===========================================================
   Configurar y devolver un PHPMailer listo
   =========================================================== */
if (!function_exists('getMailer')) {
    function getMailer(): PHPMailer {
        $mail = new PHPMailer(true);

        // Debug SMTP (0 = producción, 2 = verbose)
        $debug = (int) envOr(['MAIL_DEBUG'], 0);
        $mail->SMTPDebug   = $debug === 1 ? 2 : 0;
        $mail->Debugoutput = 'html';

        // Configuración del servidor
        $host   = envOr(['MAIL_HOST','SMTP_HOST'], 'smtp.gmail.com');
        $port   = (int) envOr(['MAIL_PORT','SMTP_PORT'], 587);
        $secure = strtolower((string) envOr(['MAIL_SECURE','SMTP_SECURE'], 'tls')); // tls|ssl

        $user   = envOr(['MAIL_USERNAME','SMTP_USER']);
        $pass   = envOr(['MAIL_PASSWORD','SMTP_PASS']);

        if (!$user || !$pass) {
            throw new Exception("❌ Faltan credenciales SMTP en .env");
        }

        $from   = envOr(['MAIL_FROM','SMTP_FROM_EMAIL'], $user);
        $fname  = envOr(['MAIL_FROM_NAME','SMTP_FROM_NAME'], 'LumiSpace');
        $reply  = envOr(['MAIL_REPLY_TO','SMTP_REPLY_TO'], $from ?: $user);

        // SMTP básico
        $mail->isSMTP();
        $mail->Host       = $host;
        $mail->Port       = $port;
        $mail->SMTPAuth   = true;
        $mail->Username   = $user;
        $mail->Password   = $pass;

        if ($secure === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // 465
        } elseif ($secure === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // 587
        }
        $mail->SMTPAutoTLS = true;

        // Opciones adicionales
        $mail->Timeout       = 15;
        $mail->SMTPKeepAlive = false;

        // Charset y remitente
        $mail->CharSet  = 'UTF-8';
        $mail->Encoding = 'base64';

        try { 
            $mail->setFrom($from ?: $user, $fname); 
        } catch (Exception $e) { 
            $mail->setFrom($user, $fname); 
        }

        if ($reply) { 
            try { $mail->addReplyTo($reply, $fname); } 
            catch (Exception $e) {} 
        }

        $mail->Sender = $from ?: $user; // envelope sender

        return $mail;
    }
}

/* ===========================================================
   Helper de envío de correo
   =========================================================== */
if (!function_exists('enviarCorreo')) {
    /**
     * @param string|array $to destinatario(s). Ej: "Juan <juan@mail.com>" o ["a@mail.com","b@mail.com"]
     * @param string $subject Asunto
     * @param string $body    Cuerpo en HTML
     * @param string $altBody Cuerpo alternativo (texto plano)
     */
    function enviarCorreo($to, string $subject, string $body, string $altBody = '') {
        try {
            $mail = getMailer();
            $mail->clearAllRecipients();

            // Destinatarios
            $destinatarios = is_array($to) ? $to : [$to];
            foreach ($destinatarios as $dest) {
                if (preg_match('/^(.*)<(.+@.+)>$/', $dest, $m)) {
                    $toName  = trim($m[1], "\"' ");
                    $toEmail = trim($m[2]);
                    $mail->addAddress($toEmail, $toName);
                } else {
                    $mail->addAddress($dest);
                }
            }

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = $altBody !== '' ? $altBody : strip_tags($body);

            $mail->send();
            return true;
        } catch (Exception $e) {
            return "Error al enviar correo: {$e->getMessage()} | {$e->getCode()}";
        }
    }
}
