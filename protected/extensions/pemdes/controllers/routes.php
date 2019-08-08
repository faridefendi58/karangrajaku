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
    $rss = $tools->get_rss($params);
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

$app->post('/surat-permohonan', function ($request, $response, $args){
    $errors = [];
    $success = false;
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
            $model = new \ExtensionsModel\RequestSuratModel();
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
