<?php

namespace Idimption;

require_once __DIR__ . '/../../vendor/autoload.php';

App::getInstance()->run(function() {
    return Auth::getLoggedInUserId();
});
