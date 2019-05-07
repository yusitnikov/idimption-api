<?php

namespace Idimption;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    use SingletonWithMockTrait;

    /** @var Logger */
    protected $_log;

    protected function init()
    {
        $this->_log = new Logger('email.log');
    }

    private function _log($message)
    {
        $this->_log->log($message);
    }

    private function _config($fieldName, $defaultValue = null)
    {
        return App::getInstance()->getConfig('email', $fieldName) ?? $defaultValue;
    }

    public function queue($subject, $content, $toAddresses, $ccAddresses = array(), $bccAddresses = array())
    {
        return $this->send($subject, $content, $toAddresses, $ccAddresses, $bccAddresses, true);
    }

    public function send($subject, $content, $toAddresses, $ccAddresses = array(), $bccAddresses = array(), $queue = false)
    {
        $this->_log('Sending an email to ' . implode(', ', array_merge($toAddresses, $ccAddresses, $bccAddresses)) . ' about ' . $subject . '...');

        if ($queue) {
            Db::getInstance()->insertRow('emailqueue', [
                'subject' => $subject,
                'content' => $content,
                'toAddresses' => $toAddresses,
                'ccAddresses' => $ccAddresses,
                'bccAddresses' => $bccAddresses,
            ]);
            return true;
        }

        if ($this->_config('disable')) {
            $this->_log('Emails disabled, logging the content instead');
            $this->_log($content);
            return true;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->SMTPDebug = $this->_config('debugLevel', 0);

            $mail->isSMTP();
            $mail->Host = $this->_config('host');
            $mail->Port = $this->_config('port');
            $mail->SMTPAuth = true;
            $mail->Username = $this->_config('login');
            $mail->Password = $this->_config('password');
            $mail->SMTPSecure = $this->_config('sslType');
            if (!$this->_config('verifySsl')) {
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
            }

            $mail->setFrom($this->_config('fromEmail'), $this->_config('fromName'));
            foreach ($toAddresses as $emailAddress) {
                $mail->addAddress($emailAddress);
            }
            foreach ($ccAddresses as $emailAddress) {
                $mail->addCC($emailAddress);
            }
            foreach ($bccAddresses as $emailAddress) {
                $mail->addBCC($emailAddress);
            }

            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $content;

            $mail->send();

            $this->_log('Email sent successfully.');
            return true;
        } catch (PHPMailerException $exception) {
            $this->_log('Email send error: ' . $mail->ErrorInfo);
            return $mail->ErrorInfo;
        }
    }
}
