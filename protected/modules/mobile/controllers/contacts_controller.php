<?php

namespace Mobile\Controllers;

use Components\BaseController as BaseController;

class ContactsController extends BaseController
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
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => [
                    'view', 'detail', 'remove'
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

        $model = new \ExtensionsModel\ContactModel();

        return $this->_container->module->render(
            $response,
            'contacts/view.html',
            [
                'model' => $model
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

        $model = new \ExtensionsModel\ContactModel();
        $data = $model->getItem($args['id']);
        if ($data['status'] == 0) {
            $cmodel = \ExtensionsModel\ContactModel::model()->findByPk($data['id']);
            $cmodel->status = 1;
            $update = \ExtensionsModel\ContactModel::model()->update($cmodel);
        }

        return $this->_container->module->render(
            $response,
            'contacts/detail.html',
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
        $model = \ExtensionsModel\ContactModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $delete = \ExtensionsModel\ContactModel::model()->delete($model);
            if ($delete) {
                $result['success'] = 1;
                $result['message'] = 'Data telah berhasil dihapus';
            }
        }

        return $response->withJson($result);
    }

}