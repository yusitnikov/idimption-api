<?php

namespace Idimption;

require_once __DIR__ . '/../../vendor/autoload.php';

App::getInstance()->run(function() {
    Auth::sendVerificationCode(
        App::getInstance()->getParam('email', false, ['string']),
        !!App::getInstance()->getParam('resetPassword')
    );
});
