<?php

namespace Idimption;

class EmailMock extends Email
{
    use SingletonMockTrait;

    protected $_emails = [];

    protected function init()
    {
        $this->_log = new LoggerMock();
    }

    public function send($subject, $content, $toAddresses, $ccAddresses = array(), $bccAddresses = array(), $queue = false)
    {
        $content = preg_replace('/ +/', ' ', $content);
        $content = preg_replace('/ *\r?\n */', "\n", $content);
        $this->_emails[] = [
            'subject' => $subject,
            'content' => trim($content),
            'to' => $toAddresses,
            'cc' => $ccAddresses,
            'bcc' => $bccAddresses,
            'queue' => $queue,
        ];
    }

    public function popEmails()
    {
        $result = $this->_emails;
        $this->_emails = [];
        return $result;
    }
}
