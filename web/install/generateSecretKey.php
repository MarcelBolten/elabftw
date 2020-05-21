<?php declare(strict_types=1);
/**
 * generateSecretKey.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

use Defuse\Crypto\Key;

/**
 * Generate a secret key for the config file
 *
 */
require_once \dirname(__DIR__, 2) . '/vendor/autoload.php';
echo Key::createNewRandomKey()->saveToAsciiSafeString();
