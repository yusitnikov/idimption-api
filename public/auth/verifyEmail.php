<?php

namespace Idimption;

require_once __DIR__ . '/../../vendor/autoload.php';

App::getInstance()->run(function() {
    $response = Auth::verifyEmail(App::getInstance()->getParam('code', true, ['string']));

    return [
        'data' => Entity\AllEntities::getAllRows(),
    ] + $response;
});
