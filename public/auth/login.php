<?php

namespace Idimption;

require_once __DIR__ . '/../../vendor/autoload.php';

App::getInstance()->run(function() {
    return Auth::login(
        App::getInstance()->getParam('email', true, ['string']),
        App::getInstance()->getParam('password', true, ['string'])
    );
});
