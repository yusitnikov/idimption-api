<?php

namespace Idimption;

use Idimption\Exception\BadRequestException;

require_once __DIR__ . '/../vendor/autoload.php';

App::getInstance()->run(function() {
    if (!Auth::isVerifiedEmail()) {
        throw new BadRequestException('User email not verified');
    }

    $file = $_FILES['file'] ?? null;
    if (!is_array($file)) {
        // The common reason of not having the var here is the file size limitation
        throw new BadRequestException('File size is too large');
    }
    if (is_array($file['error'])) {
        throw new BadRequestException('Only one file should be provided');
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new BadRequestException('File size is too large');
        default:
            throw new BadRequestException('Error uploading file');
    }
    list($type, $extension) = explode('/', $file['type']);
    if (!in_array($type, ['image'])) {
        throw new BadRequestException('Only image files are allowed');
    }
    $rootDir = __DIR__ . '/upload/';
    $fileDir = sha1(Auth::getLoggedInUserId()) . '/';
    $fullDir = $rootDir . $fileDir;
    @mkdir($fullDir);
    $fileName = $fileDir . time() . '.' . $extension;
    move_uploaded_file($file['tmp_name'], $rootDir . $fileName);
    return $fileName;
});
