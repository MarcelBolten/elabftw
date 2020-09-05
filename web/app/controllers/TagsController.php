<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use function dirname;
use Elabftw\Exceptions\DatabaseErrorException;
use Elabftw\Exceptions\FilesystemErrorException;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Models\Database;
use Elabftw\Models\Experiments;
use Elabftw\Models\Tags;
use Elabftw\Models\Templates;
use Elabftw\Services\Check;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Tags
 *
 */
require_once dirname(__DIR__) . '/init.inc.php';

$Response = new JsonResponse();
$Response->setData(array(
    'res' => true,
    'msg' => _('Saved'),
));

try {
    // id of the item (experiment or database item)
    $id = 1;

    if ($Request->request->has('itemId')) {
        $id = (int) $Request->request->get('itemId');
    }

    if ($Request->request->get('type') === 'experiments' ||
        $Request->query->get('type') === 'experiments') {
        $Entity = new Experiments($App->Users, $id);
    } elseif ($Request->request->get('type') === 'experiments_templates') {
        $Entity = new Templates($App->Users, $id);
    } else {
        $Entity = new Database($App->Users, $id);
    }

    $Tags = new Tags($Entity);

    // CREATE TAG
    if ($Request->request->has('createTag')) {
        $Tags->create($Request->request->get('tag'));
    }

    // GET TAG LIST
    if ($Request->query->has('term')) {
        $term = $Request->query->filter('term', null, FILTER_SANITIZE_STRING);
        $Response->setData($Tags->getList($term));
    }

    // UPDATE TAG
    if ($Request->request->has('update') && $App->Session->get('is_admin')) {
        $Tags->update((int) $Request->request->get('tagId'), $Request->request->get('newtag'));
    }

    // DEDUPLICATE TAG
    if ($Request->request->has('deduplicate') && $Session->get('is_admin')) {
        $deduplicated = $Tags->deduplicate();
        $Response->setData(array('res' => true, 'msg' => sprintf(_('Deduplicated %d tags'), $deduplicated)));
    }

    // UNREFERENCE TAG
    if ($Request->request->has('unreferenceTag')) {
        if (Check::id((int) $Request->request->get('tagId')) === false) {
            throw new IllegalActionException('Bad id value');
        }
        $Tags->unreference((int) $Request->request->get('tagId'));
    }

    // DELETE TAG
    if ($Request->request->has('destroyTag') && $App->Session->get('is_admin')) {
        if (Check::id((int) $Request->request->get('tagId')) === false) {
            throw new IllegalActionException('Bad id value');
        }
        $Tags->destroy((int) $Request->request->get('tagId'));
    }
} catch (ImproperActionException $e) {
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (IllegalActionException $e) {
    $App->Log->notice('', array(array('userid' => $App->Session->get('userid')), array('IllegalAction', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => Tools::error(true),
    ));
} catch (DatabaseErrorException | FilesystemErrorException $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('Error', $e)));
    $Response->setData(array(
        'res' => false,
        'msg' => $e->getMessage(),
    ));
} catch (Exception $e) {
    $App->Log->error('', array(array('userid' => $App->Session->get('userid')), array('exception' => $e)));
} finally {
    $Response->send();
}
