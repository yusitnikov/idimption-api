<?php

namespace Idimption;

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class Email
{
    /**
     * @return self
     */
    private static function getInstance()
    {
        static $instance = null;
        return $instance = $instance ?: new self();
    }

    /** @var resource */
    private $_log;

    private function __construct()
    {
        $this->_log = fopen(__DIR__ . '/../logs/email.log', 'ab');
    }

    function __destruct()
    {
        fclose($this->_log);
    }

    private static function _log($message)
    {
        $prefix = App::getInstance()->getLogPrefix();
        fwrite(self::getInstance()->_log, "$prefix $message\n");
    }

    private static function _config($fieldName, $defaultValue = null)
    {
        return App::getInstance()->getConfig('email', $fieldName) ?? $defaultValue;
    }

    public static function send($subject, $content, $toAddresses, $ccAddresses = array(), $bccAddresses = array())
    {
        self::_log('Sending an email to ' . implode(', ', array_merge($toAddresses, $ccAddresses, $bccAddresses)) . ' about ' . $subject . '...');

        if (self::_config('disable')) {
            self::_log('Emails disabled, logging the content instead');
            self::_log($content);
            return true;
        }

        $mail = new PHPMailer(true);

        try {
            $mail->SMTPDebug = self::_config('debugLevel', 0);

            $mail->isSMTP();
            $mail->Host = self::_config('host');
            $mail->Port = self::_config('port');
            $mail->SMTPAuth = true;
            $mail->Username = self::_config('login');
            $mail->Password = self::_config('password');
            $mail->SMTPSecure = self::_config('sslType');
            if (!self::_config('verifySsl')) {
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
            }

            $mail->setFrom(self::_config('fromEmail'), self::_config('fromName'));
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

            self::_log('Email sent successfully.');
            return true;
        } catch (PHPMailerException $exception) {
            self::_log('Email send error: ' . $mail->ErrorInfo);
            return $mail->ErrorInfo;
        }
    }
}
