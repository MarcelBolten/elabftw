<?php declare(strict_types=1);
/**
 * app/logout.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';

$Session = new Session();
$Session->start();

// kill session
$Session->invalidate();
// disable token cookie
setcookie('token', '', time() - 3600, '/', '', true, true);
// and redirect to login page
$Response = new RedirectResponse('../login.php');
$Response->send();
