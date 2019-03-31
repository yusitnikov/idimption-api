<?php

namespace Idimption;

require_once __DIR__ . '/../../vendor/autoload.php';

App::getInstance()->run(function() {
    $userId = Auth::verifyEmail(App::getInstance()->getParam('code', true, ['string']));

    return [
        'data' => Entity\AllEntities::getAllRows(),
        'userId' => $userId,
    ];
});
