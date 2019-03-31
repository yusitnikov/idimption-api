<?php

namespace Idimption;

require_once __DIR__ . '/../../vendor/autoload.php';

App::getInstance()->run(function() {
    Auth::login(
        App::getInstance()->getParam('userId', true, ['string']),
        App::getInstance()->getParam('password', true, ['string'])
    );
});
