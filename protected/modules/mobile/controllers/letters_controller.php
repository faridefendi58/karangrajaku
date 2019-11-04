<?php

namespace Mobile\Controllers;

use Components\BaseController as BaseController;

class LettersController extends BaseController
{
    protected $_login_url = '/mobile/default/login';

    public function __construct($app, $user)
    {
        parent::__construct($app, $user);
    }

    public function register($app)
    {
        $app->map(['GET'], '/view', [$this, 'view']);
        $app->map(['POST', 'GET'], '/detail/[{id}]', [$this, 'detail']);
        $app->map(['POST'], '/remove/[{id}]', [$this, 'remove']);
        $app->map(['POST'], '/complete/[{id}]', [$this, 'complete']);
        $app->map(['POST'], '/remove-permanent/[{id}]', [$this, 'remove_permanent']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => [
                    'view', 'detail', 'remove', 'complete', 'remove-permanent'
                ],
                'users'=> ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function view($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\RequestSuratModel();
        $params = $request->getParams();

        return $this->_container->module->render(
            $response,
            'letters/view.html',
            [
                'model' => $model,
                'params' => $params
            ]);
    }

    public function detail($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\RequestSuratModel();
        $data = $model->getItem($args['id']);

        return $this->_container->module->render(
            $response,
            'letters/detail.html',
            [
                'model' => $model,
                'data' => $data
            ]);
    }

    public function remove($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $result = ['success' => 0, 'Data gagal dibatalkan'];
        $model = \ExtensionsModel\RequestSuratModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $model->status = 2;
            $update = \ExtensionsModel\RequestSuratModel::model()->update($model);
            if ($update) {
                $result['success'] = 1;
                $result['message'] = 'Data telah berhasil dibatalkan';
            }
        }

        return $response->withJson($result);
    }

    public function complete($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $result = ['success' => 0, 'Data gagal disimpan'];
        $model = \ExtensionsModel\RequestSuratModel::model()->findByPk($args['id']);
        $settings = $this->_settings;
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $model->status = 1;
            $model->processed_at = date("Y-m-d H:i:s");
            $model->processed_by = $this->_user->id;
            $update = \ExtensionsModel\RequestSuratModel::model()->update($model);
            if ($update) {
                $result['success'] = 1;
                $result['message'] = 'Data telah berhasil disimpan';

                if (!empty($model->email)) {
                    $smodel = \ExtensionsModel\SuratPermohonanModel::model()->findByPk($model->surat_permohonan_id);

                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                    try {
                        //Server settings
                        $mail->SMTPDebug = 0;
                        $mail->isSMTP();
                        $mail->Host = $settings['params']['smtp_host'];
                        $mail->SMTPAuth = true;
                        $mail->Username = $settings['params']['smtp_email'];
                        $mail->Password = $settings['params']['smtp_secret'];
                        $mail->SMTPSecure = $settings['params']['smtp_secure'];
                        $mail->Port = $settings['params']['smtp_port'];

                        //Recipients
                        $mail->setFrom($settings['params']['admin_email'], 'Pemdes Karang Raja');
                        $mail->addAddress($model->email, $model->name);
                        $mail->addReplyTo($settings['params']['admin_email'], 'Pemdes Karang Raja');

                        //Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Permohonan ' . $smodel->title;
                        $mail->Body = "Halo ". $model->name .",
                    <br/><br/>
                    Pengajuan permohonan <b>" . $smodel->title . "</b> di Pemerintah Desa Karang Raja telah diproses.
                    <br/><br/>Mohon kesediaannya untuk hadir di kantor desa Karang Raja guna mengambil surat keterangan tersebut.
					<br/><br/>Hormat Kami,<br/><br/>Admin";

                        $mail->send();
                    } catch (\PHPMailer\PHPMailer\Exception $e) {
                        echo 'Message could not be sent.';
                        echo 'Mailer Error: ' . $mail->ErrorInfo;
                    }
                }
            }
        }

        return $response->withJson($result);
    }

    public function remove_permanent($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if (!$isAllowed) {
            return $this->notAllowedAction();
        }

        if (!isset($args['id'])) {
            return false;
        }

        $result = ['success' => 0, 'Data gagal dihapus'];
        $model = \ExtensionsModel\RequestSuratModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $delete = \ExtensionsModel\RequestSuratModel::model()->delete($model);
            if ($delete) {
                $result['success'] = 1;
                $result['message'] = 'Data telah berhasil dihapus';
            }
        }

        return $response->withJson($result);
    }
}
