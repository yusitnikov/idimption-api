<?php

namespace Idimption;

class Logger
{
    protected $_file;

    public function __construct($fileName)
    {
        $this->init($fileName);
    }

    public function __destruct()
    {
        $this->dispose();
    }

    protected function init($fileName)
    {
        $this->_file = fopen(__DIR__ . '/../logs/' . $fileName, 'ab');
    }

    protected function dispose()
    {
        fclose($this->_file);
    }

    public function log($message)
    {
        $prefix = App::getInstance()->getLogPrefix();
        fwrite($this->_file, "$prefix $message\n");
    }
}
