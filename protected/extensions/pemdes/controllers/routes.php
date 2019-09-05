<?php
foreach (glob(__DIR__.'/*_controller.php') as $controller) {
    $cname = basename($controller, '.php');
    if (!empty($cname)) {
        require_once $controller;
    }
}

foreach (glob(__DIR__.'/../components/*.php') as $component) {
    $cname = basename($component, '.php');
    if (!empty($cname)) {
        require_once $component;
    }
}

$app->get('/feed', function ($request, $response, $args) {
    $tools = new \Components\Tool();
    $params = $request->getParams();
    if (array_key_exists('category', $params)) {
        $rss = $tools->get_rss_category($params);
    } else {
        $rss = $tools->get_rss($params);
    }
    var_dump($rss); exit;
});

$app->get('/feed/[{id}]', function ($request, $response, $args) {
    if (isset($args['id'])) {
        $tools = new \Components\Tool();
        $data = $tools->get_rss($args);

        return $this->view->render($response, 'feed-detail.phtml', [
            'data' => $data
        ]);
    }
});

$app->get('/announcement/[{id}]', function ($request, $response, $args) {
    if (isset($args['id'])) {
        $tools = new \Components\Tool();
        $model = new \ExtensionsModel\PostModel();
        $data = $model->getPostDetail($args['id']);
        $images = $model->getImages(['id' => $args['id']]);

        return $this->view->render($response, 'announcement-detail.phtml', [
            'data' => $data,
            'images' => $images
        ]);
    }
});

$app->post('/surat-permohonan', function ($request, $response, $args){
    $errors = [];
    $success = false;
    $request_params = $request->getParams();
    $settings = $this->get('settings');

    $params = [];
    if (isset($_POST['Permohonan'])){
        $params = $_POST['Permohonan'];
        $tools = new \Components\Tool();
        $hashed = $tools->rpHash($_POST['captcha']);
        if ($hashed != $_POST['captchaHash']) {
            $message = 'Kode verifikasi yang Anda masukkan salah.';
            array_push($errors, $message);
        }

        if (count($errors) == 0) {
            $smodel = \ExtensionsModel\SuratPermohonanModel::model()->findByAttributes(['slug' => $request_params['p']]);

            $model = new \ExtensionsModel\RequestSuratModel();
            if ($smodel instanceof \RedBeanPHP\OODBBean) {
                $model->surat_permohonan_id = $smodel->id;
            }
            $model->name = $_POST['Permohonan']['name'];
            $model->nik = $_POST['Permohonan']['nik'];
            $model->email = $_POST['Permohonan']['email'];
            $model->birth_place = $_POST['Permohonan']['birth_place'];
            $model->birth_date = $_POST['Permohonan']['birth_year'].'-'.$_POST['Permohonan']['birth_month'].'-'.$_POST['Permohonan']['birth_date'];
            $model->handphone = $_POST['Permohonan']['handphone'];
            $model->notes = $_POST['Permohonan']['notes'];
            $model->created_at = date("Y-m-d H:i:s");
            $save = \ExtensionsModel\RequestSuratModel::model()->save($model);
            if ($save) {
                $success = true;
                $params = [];

                //send mail to admin
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
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
                    $mail->setFrom( $settings['params']['admin_email'], 'Admin Karang Raja' );
                    $mail->addAddress( $settings['params']['admin_email'], 'Pemdes Karang Raja' );
                    if (!empty($_POST['Permohonan']['email'])) {
                        $mail->addReplyTo( $_POST['Permohonan']['email'], $_POST['Permohonan']['name'] );
                    } else {
                        $mail->addReplyTo( $_POST['Permohonan']['admin_email'], $_POST['Permohonan']['name'] );
                    }

                    //Content
                    $mail->isHTML(true);
                    $mail->Subject = '[Permohonan] '. $smodel->title;
                    $mail->Body = "Halo Admin,
                    <br/><br/>
                    Ada permohonan <b>". $smodel->title ."</b> dengan data berikut:
                    <br/><br/>
                    <b>Nama Pemohon</b> : ".$_POST['Permohonan']['name']." <br/>
                    <b>NIK Pemohon</b> : ".$_POST['Permohonan']['nik']." <br/>
                    <b>Email Pemohon</b> : ".$_POST['Permohonan']['email']." <br/>
                    <b>Tempat Tgl Lahir</b> : ".$_POST['Permohonan']['birth_place']. ", ". $_POST['Permohonan']['birth_date']. '-'. $_POST['Permohonan']['birth_month'].'-'.$_POST['Permohonan']['birth_year']." <br/>
                    <b>No Handphone</b> : ".$_POST['Permohonan']['handphone']." <br/>
                    <br/>
                    <b>Keperluan</b> :<br/> ".$_POST['Permohonan']['notes']."";

                    $mail->send();
                } catch (Exception $e) {
                    echo 'Message could not be sent.';
                    echo 'Mailer Error: ' . $mail->ErrorInfo;
                }

            } else {
                $errors = \ExtensionsModel\RequestSuratModel::model()->getErrors(true, true);
            }
        }
    }

    return $this->view->render($response, 'surat-permohonan.phtml', [
        'params' => $params,
        'errors' => $errors,
        'success' => $success
    ]);
});
?>
