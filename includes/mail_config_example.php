<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require __DIR__ . '/../libs/PHPMailer/Exception.php';
require __DIR__ . '/../libs/PHPMailer/PHPMailer.php';
require __DIR__ . '/../libs/PHPMailer/SMTP.php';

function sendNotificationEmail($toEmail, $userName) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your@gmail.com'; 
        $mail->Password   = 'App_Password'; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('your@gmail.com', 'Olympiad Admin');
        $mail->addAddress($toEmail); 

        $mail->isHTML(true);
        $mail->Subject = 'Ваш акаунт підтверджено!';
        $mail->Body    = "
            <h2>Вітаємо, {$userName}!</h2>
            <p>Адміністратор підтвердив ваш обліковий запис на платформі змагань.</p>
            <p>Тепер ви можете увійти в систему і брати участь у змаганнях.</p>
            <br>
            <a href='http://localhost/olympiad_platform/login.php'>Увійти в кабінет</a>
        ";
        $mail->AltBody = "Вітаємо, {$userName}! Ваш акаунт підтверджено. Тепер ви можете увійти в систему.";
        $mail->Port       = 465;
        $mail->CharSet    = 'UTF-8';

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

        $mail->setFrom('jaroslav0805@gmail.com', 'Olympiad Admin');
        $mail->send();
        return true;
    } catch (Exception $e) {

        echo "<div style='color: red; background: #fff; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "<strong>PHPMailer Error:</strong> " . $mail->ErrorInfo;
        echo "</div>";
        
        return false;
    }
}
?>