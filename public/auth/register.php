<?php

namespace Idimption;

require_once __DIR__ . '/../../vendor/autoload.php';

App::getInstance()->run(function() {
    $userId = Auth::register(
        App::getInstance()->getParam('email', true, ['string']),
        App::getInstance()->getParam('name', true, ['string']),
        App::getInstance()->getParam('password', true, ['string'])
    );

    return [
        'userId' => $userId,
        'data' => Entity\AllEntities::getAllRows()
    ];
});
