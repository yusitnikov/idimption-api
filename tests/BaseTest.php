<?php

namespace Idimption\Test;

use Idimption\App;
use Idimption\AppMock;
use Idimption\Auth;
use Idimption\AuthMock;
use Idimption\Db;
use Idimption\DbMock;
use Idimption\Email;
use Idimption\EmailMock;
use Idimption\Entity\AllEntities;
use Idimption\Entity\BaseEntity;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    const EXPECTED_EMAIL_FOOTER = "<p>Cheers,<br>Yura from <a href='http://localhost:8080'>Idimption</a>.</p>";

    /** @var AppMock */
    protected $_appMock;

    /** @var AuthMock */
    protected $_authMock;

    /** @var DbMock */
    protected $_dbMock;

    /** @var EmailMock */
    protected $_emailMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->_appMock = new AppMock();
        App::setInstance($this->_appMock);

        $this->_authMock = new AuthMock();
        Auth::setInstance($this->_authMock);

        $this->_dbMock = new DbMock();
        Db::setInstance($this->_dbMock);

        $this->_emailMock = new EmailMock();
        Email::setInstance($this->_emailMock);

        BaseEntity::resetInstances();
        AllEntities::resetAllModels();
    }
}
