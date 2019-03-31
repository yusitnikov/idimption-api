<?php

namespace Idimption;

use Idimption\Entity\AllEntities;
use Idimption\Exception\BadRequestException;

require_once __DIR__ . '/../vendor/autoload.php';

App::getInstance()->run(function() {
    if (Auth::getLoggedInUser() && !Auth::isVerifiedEmail()) {
        throw new BadRequestException('User email not verified');
    }

    $transitions = App::getInstance()->getParam('transitions', true, 'array') ?: [];

    $guidMap = AllEntities::save($transitions);
    $newData = AllEntities::getAllRows();

    return [
        'guids' => $guidMap,
        'data' => $newData
    ];
});
