<?php

namespace Idimption;

use Idimption\Entity\AllEntities;

require_once __DIR__ . '/../vendor/autoload.php';

App::getInstance()->run(function() {
    $transitions = App::getInstance()->getParam('transitions', true, 'array') ?: [];

    $guidMap = AllEntities::save($transitions);
    $newData = AllEntities::getAllRows();

    return [
        'guids' => $guidMap,
        'data' => $newData
    ];
});
