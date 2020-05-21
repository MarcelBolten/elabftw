<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\InvalidCredentialsException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class AuthTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $Request = Request::createFromGlobals();

        $Session = new Session();

        $Request->setSession($Session);
        $this->Auth = new Auth($Request, $Session);
    }

    public function testCheckCredentials()
    {
        $this->assertEquals($this->Auth->checkCredentials('phpunit@example.com', 'phpunitftw'), 1);
        $this->expectException(InvalidCredentialsException::class);
        $this->Auth->checkCredentials('phpunit@example.com', 'wrong password');
    }

    public function testLogin()
    {
        $this->assertTrue($this->Auth->login($this->Auth->checkCredentials('phpunit@example.com', 'phpunitftw')));
        //$this->assertFalse($this->Auth->login('toto@yopmail.com', '0'));
    }
}
