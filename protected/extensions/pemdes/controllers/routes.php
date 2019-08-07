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

?>
