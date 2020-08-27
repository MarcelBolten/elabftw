<?php declare(strict_types=1);
/**
 * app/download.php
 *
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Elabftw;

use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Services\Filter;
use Exception;

$elabRoot = \dirname(__DIR__, 2);
require_once $elabRoot . '/web/app/init.inc.php';

try {
    // we disable errors to avoid having notice and warning polluting our file
    error_reporting(E_ERROR);

    // Check for LONG_NAME
    if (!isset($_GET['f']) || empty($_GET['f'])) {
        throw new IllegalActionException('Missing parameter for download');
    }
    // Nullbyte hack fix
    if (strpos($_GET['f'], "\0") !== false) {
        throw new IllegalActionException('Null byte detected');
    }

    // Remove any path info to avoid hacking by adding relative path, etc.
    $long_filename = basename($_GET['f']);
    // get the first two letters to get the folder
    $folder = substr($long_filename, 0, 2);
    $final_filename = $folder . '/' . $long_filename;

    // maybe it's an old file that has no subfolder
    if (!is_readable($elabRoot . '/uploads/' . $final_filename)) {
        $final_filename = $long_filename;
    }

    // REAL_NAME
    if (!isset($_GET['name']) || empty($_GET['name'])) {
        $filename = $final_filename;
    } else {
        // we redo a check for filename
        $filename = Filter::forFilesystem($_GET['name']);
        if ($filename === '') {
            $filename = 'unnamed_file';
        }
    }

    // SET FILE PATH
    // the zip archives will be in the tmp folder
    if (isset($_GET['type']) && ($_GET['type'] === 'zip' || $_GET['type'] === 'csv' || $_GET['type'] === 'report')) {
        $file_path = $elabRoot . '/cache/elab/' . $long_filename;
    } else {
        $file_path = $elabRoot . '/uploads/' . $final_filename;
    }

    if (!is_readable($file_path)) {
        throw new FilesystemErrorException('File not found!');
    }

    // MIME
    $mtype = 'application/force-download';

    if (\function_exists('mime_content_type')) {
        $mtype = mime_content_type($file_path);
    } elseif (function_exists('finfo_file')) {
        $finfo = new \finfo(FILEINFO_MIME);
        $mtype = $finfo->file($file_path);
    }

    // Make sure program execution doesn't time out
    // Set maximum script execution time in seconds (0 means no limit)
    set_time_limit(0);

    // file size in bytes
    $fsize = filesize($file_path);

    // HEADERS
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Cache-Control: public');
    header('Content-Type: ' . $mtype);
    if (isset($_GET['forceDownload'])) {
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . $filename);
    }
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . $fsize);

    // DOWNLOAD
    $file = fopen($file_path, 'rb');
    if ($file === false) {
        throw new FilesystemErrorException('Error opening the file!');
    }
    while (!feof($file)) {
        echo fread($file, 1024 * 8);
        flush();
        if (connection_status() !== 0) {
            fclose($file);
        }
    }
    fclose($file);
} catch (Exception $e) {
    $App->Log->error('', array('exception' => $e));
    $Session->getFlashBag()->add('ko', $e->getMessage());
    header('Location: ../experiments.php');
}
