<?php

namespace Mobile\Controllers;

use Components\BaseController as BaseController;

class AnnouncementsController extends BaseController
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
        $app->map(['POST'], '/create', [$this, 'create']);
    }

    public function accessRules()
    {
        return [
            ['allow',
                'actions' => [
                    'create', 'view', 'detail', 'remove'
                ],
                'users'=> ['@'],
            ],
            ['deny',
                'users' => ['*'],
            ],
        ];
    }

    public function create($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\PostModel();

        $message = 'Data gagal disimpan.';
        $success = false;

        $errors = [];
        $request_params = $request->getParams();
        $params = [];
        if (isset($_POST['Post'])){

            $params = $request_params['Post'];

            if (count($errors) == 0) {
                $model->status = \ExtensionsModel\PostModel::STATUS_PUBLISHED;
                $model->post_type = 'post';
                $model->author_id = $this->_user->id;
                $model->created_at = date("Y-m-d H:i:s");

                $imodel = new \ExtensionsModel\PostImagesModel();
                if (isset($_FILES) && !empty($_FILES['Post'])) {
                    $path_info = pathinfo($_FILES['Post']['name']['image']);
                    if (!in_array($path_info['extension'], ['jpg','JPG','jpeg','JPEG','png','PNG'])) {
                        echo json_encode(['status'=>'failed','message'=>'Allowed file type are jpg, png']); exit;
                    }
                    $imodel->file_name = time().'.'.$path_info['extension'];
                }

                $save = \ExtensionsModel\PostModel::model()->save(@$model);
                if ($save) {
                    $imodel->post_id = $model->id;
                    $imodel->upload_folder = 'uploads/posts/';
                    $imodel->created_at = date("Y-m-d H:i:s");
                    $uploadfile = $imodel->upload_folder . $imodel->file_name;
                    if (move_uploaded_file($_FILES['Post']['tmp_name']['image'], $uploadfile)) {
                        $save2 = \ExtensionsModel\PostImagesModel::model()->save($imodel);
                    }

                    // save the post content
                    $pcmodel = new \ExtensionsModel\PostContentModel();
                    $pcmodel->post_id = $model->id;
                    $pcmodel->title = $params['title'];
                    $pcmodel->content = $params['content'];
                    $pcmodel->slug = \ExtensionsModel\PostModel::createSlug($model->id." ".$params['title']);
                    $pcmodel->created_at = date("Y-m-d H:i:s");
                    $save3 = \ExtensionsModel\PostContentModel::model()->save($pcmodel);

                    // save the category
                    $pctmodel = new \ExtensionsModel\PostInCategoryModel();
                    $pctmodel->post_id = $model->id;
                    $pctmodel->category_id = 1;
                    $pctmodel->created_at = date("Y-m-d H:i:s");
                    $save4 = \ExtensionsModel\PostInCategoryModel::model()->save($pctmodel);

                    $success = true;
                    $params = [];
                    $message = 'Data telah berhasil disimpan.';
                } else {
                    $success = false;
                    $errors = \ExtensionsModel\PostModel::model()->getErrors(true, true);
                }
            }
        }

        return $this->_container->module->render(
            $response,
            'announcements/view.html',
            [
                'model' => $model,
                'success' => $success,
                'message' => $message,
                'params' => $params,
                'errors' => $errors
            ]);
    }

    public function view($request, $response, $args)
    {
        $isAllowed = $this->isAllowed($request, $response, $args);
        if ($isAllowed instanceof \Slim\Http\Response)
            return $isAllowed;

        if(!$isAllowed){
            return $this->notAllowedAction();
        }

        $model = new \ExtensionsModel\PostModel();

        return $this->_container->module->render(
            $response,
            'announcements/view.html',
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

        $model = new \ExtensionsModel\PostModel();
        $data = $model->getPostDetail($args['id']);

        return $this->_container->module->render(
            $response,
            'announcements/detail.html',
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
        $model = \ExtensionsModel\PostModel::model()->findByPk($args['id']);
        if ($model instanceof \RedBeanPHP\OODBBean) {
            $delete2 = \ExtensionsModel\PostContentModel::model()->deleteAllByAttributes(['post_id'=>$args['id']]);
            $delete3 = \ExtensionsModel\PostInCategoryModel::model()->deleteAllByAttributes(['post_id'=>$args['id']]);
            $images = \ExtensionsModel\PostImagesModel::model()->findAllByAttributes(['post_id'=>$args['id']]);
            if (is_array($images)) {
                foreach ($images as $image) {
                    if ($image instanceof \RedBeanPHP\OODBBean) {
                        if (file_exists($image->upload_folder . $image->file_name)) {
                            unlink($image->upload_folder . $image->file_name);
                        }
                        $delete4 = \ExtensionsModel\PostImagesModel::model()->delete($image);
                    }
                }
            }
            $delete5 = \ExtensionsModel\PostModel::model()->delete($model);
            if ($delete5) {
                $result['success'] = 1;
                $result['message'] = 'Data telah berhasil dihapus';
            }
        }

        return $response->withJson($result);
    }

}