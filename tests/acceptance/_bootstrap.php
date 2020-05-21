<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

/**
 * Login to the test instance
 */
function testLogin($I)
{
    // if snapshot exists -> skip login
    if ($I->amOnPage('/') && $I->loadSessionSnapshot('login')) {
        return;
    }
    // logging in
    $I->amOnPage('/login.php');
    $I->submitForm('#login', array('email' => 'phpunit@example.com', 'password' => 'phpunitftw'));
    // saving snapshot
    $I->saveSessionSnapshot('login');
}
