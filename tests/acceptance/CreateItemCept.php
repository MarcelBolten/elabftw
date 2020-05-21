<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
$I = new AcceptanceTester($scenario);
$I->wantTo('Test creating an item');
$I->amOnPage('/');
testLogin($I);
$I->amOnPage('database.php?create=true&tpl=1');
$I->see('Tags');
$I->see('Date');
$I->see('Title');
