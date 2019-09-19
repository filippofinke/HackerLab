<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    public static function send($to, $full_name, $subject, $message) {
        $mail = new PHPMailer(true);
        try {
            $mail->SMTPDebug = 0;                                       
            $mail->isSMTP();                                            
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;                                   
            $mail->Username   = 'hackerlab.notify@gmail.com';                     
            $mail->Password   = 'Password&1';                               
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;                
            $mail->setFrom('hackerlab.notify@gmail.com', 'HackerLab');
            $mail->addAddress($to, $full_name);
            $mail->Subject = $subject;
            $mail->msgHTML($message);
        
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

}