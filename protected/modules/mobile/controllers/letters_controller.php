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
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => [
                    'view', 'detail', 'remove', 'complete'
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

        $result = ['success' => 0, 'Data gagal dihapus'];
        $model = \ExtensionsModel\RequestSuratModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $model->status = 3;
            $update = \ExtensionsModel\RequestSuratModel::model()->update($model);
            if ($update) {
                $result['success'] = 1;
                $result['message'] = 'Data telah berhasil dihapus';
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
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $model->status = 1;
            $model->processed_at = date("Y-m-d H:i:s");
            $model->processed_by = $this->_user->id;
            $update = \ExtensionsModel\RequestSuratModel::model()->update($model);
            if ($update) {
                $result['success'] = 1;
                $result['message'] = 'Data telah berhasil disimpan';
            }
        }

        return $response->withJson($result);
    }
}