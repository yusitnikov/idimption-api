<?php

namespace Idimption;

require_once __DIR__ . '/../vendor/autoload.php';

while (true) {
    $email = Db::selectRow('
        SELECT *
        FROM emailqueue
        ORDER BY id
        LIMIT 1
    ');
    if ($email) {
        var_dump($email);
        Db::deleteRow('emailqueue', $email['id']);
        Email::send(
            $email['subject'],
            $email['content'],
            json_decode($email['toAddresses'] ?: '[]', true),
            json_decode($email['ccAddresses'] ?: '[]', true),
            json_decode($email['bccAddresses'] ?: '[]', true)
        );
    } else {
        sleep(5);
    }
}

