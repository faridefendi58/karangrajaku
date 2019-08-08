<?php

$app->post('/kontak-kami', function ($request, $response, $args) {
    $message = 'Pesan Anda gagal dikirimkan.';
    $success = false;
    $settings = $this->get('settings');

    $errors = [];
    $request_params = $request->getParams();
    $params = [];
    if (isset($_POST['Contact'])){

        $params = $request_params['Contact'];
        $tools = new \Components\Tool();
        if (isset($_POST['captcha'])) {
            $hashed = $tools->rpHash($_POST['captcha']);
            if ($hashed != $_POST['captchaHash']) {
                $message = 'Kode verifikasi yang Anda masukkan salah.';
                array_push($errors, $message);
            }
        }

        if (count($errors) == 0) {
            $model = new \ExtensionsModel\ContactModel();
            $model->name = $_POST['Contact']['name'];
            $model->email = $_POST['Contact']['email'];
            $model->phone = $_POST['Contact']['phone'];
            $model->message = $_POST['Contact']['message'];
            $model->created_at = date("Y-m-d H:i:s");
            $save = \ExtensionsModel\ContactModel::model()->save($model);
            if ($save) {
                $success = true;
                $params = [];
                $message = 'Pesan Anda berhasil dikirim. Kami akan segera merespon pesan Anda.';

                //send mail to admin
                /*$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    //Server settings
                    $mail->SMTPDebug = 0;
                    $mail->isSMTP();
                    $mail->Host = $settings['params']['smtp_host'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $settings['params']['admin_email'];
                    $mail->Password = $settings['params']['smtp_secret'];
                    $mail->SMTPSecure = $settings['params']['smtp_secure'];
                    $mail->Port = $settings['params']['smtp_port'];

                    //Recipients
                    $mail->setFrom( $settings['params']['admin_email'], 'Admin slightSite' );
                    $mail->addAddress( $settings['params']['admin_email'], 'Farid Efendi' );
                    $mail->addReplyTo( $_POST['Contact']['email'], $_POST['Contact']['name'] );

                    //Content
                    $mail->isHTML(true);
                    $mail->Subject = '[slightSite] Kontak Kami';
                    $mail->Body = "Halo Admin,
                    <br/><br/>
                    Ada pesan baru dari pengunjung dengan data berikut:
                    <br/><br/>
                    <b>Judul pesan</b> : ".$_POST['Contact']['subject']." <br/>
                    <b>Nama pengunjung</b> : ".$_POST['Contact']['name']." <br/>
                    <b>Alamat Email</b> : ".$_POST['Contact']['email']." <br/>
                    <br/>
                    <b>Isi Pesan</b> :<br/> ".$_POST['Contact']['message']."";

                    $mail->send();
                } catch (Exception $e) {
                    echo 'Message could not be sent.';
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                    exit;
                }*/
            } else {
                $success = false;
                $errors = \ExtensionsModel\ContactModel::model()->getErrors(true, true);
            }
        }
    }

    return $this->view->render($response, 'kontak-kami.phtml', [
        'success' => $success,
        'message' => $message,
        'params' => $params,
        'errors' => $errors
    ]);
});

foreach (glob(__DIR__.'/*_controller.php') as $controller) {
	$cname = basename($controller, '.php');
	if (!empty($cname)) {
		require_once $controller;
	}
}

$app->group('/contact', function () use ($user) {
    $this->group('/messages', function() use ($user) {
        new Extensions\Controllers\MessagesController($this, $user);
    });
});

?>
