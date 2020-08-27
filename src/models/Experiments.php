<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Models;

use function bin2hex;
use Elabftw\Exceptions\IllegalActionException;
use Elabftw\Interfaces\CreateInterface;
use Elabftw\Maps\Team;
use Elabftw\Services\Filter;
use PDO;
use function random_bytes;
use function sha1;

/**
 * All about the experiments
 */
class Experiments extends AbstractEntity implements CreateInterface
{
    /**
     * Constructor
     *
     * @param Users $users
     * @param int|null $id
     */
    public function __construct(Users $users, ?int $id = null)
    {
        parent::__construct($users, $id);
        $this->page = 'experiments';
        $this->type = 'experiments';
    }

    /**
     * Create an experiment
     *
     * @param int $tpl the template on which to base the experiment
     * @return int the new id of the experiment
     */
    public function create(int $tpl): int
    {
        $Templates = new Templates($this->Users);

        // do we want template ?
        if ($tpl > 0) {
            $Templates->setId($tpl);
            $templatesArr = $Templates->read();
            $title = $templatesArr['name'];
            $body = $templatesArr['body'];
        } else {
            $title = _('Untitled');
            $body = $Templates->readCommonBody();
        }

        $canread = 'team';
        $canwrite = 'user';
        if ($this->Users->userData['default_read'] !== null) {
            $canread = $this->Users->userData['default_read'];
        }
        if ($this->Users->userData['default_write'] !== null) {
            $canwrite = $this->Users->userData['default_write'];
        }

        // enforce the permissions if the admin has set them
        $Team = new Team((int) $this->Users->userData['team']);
        $canread = $Team->getDoForceCanread() === 1 ? $Team->getForceCanread() : $canread;
        $canwrite = $Team->getDoForceCanwrite() === 1 ? $Team->getForceCanwrite() : $canwrite;

        // SQL for create experiments
        $sql = 'INSERT INTO experiments(title, date, body, category, elabid, canread, canwrite, datetime, userid)
            VALUES(:title, :date, :body, :category, :elabid, :canread, :canwrite, NOW(), :userid)';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req, array(
            'title' => $title,
            'date' => Filter::kdate(),
            'body' => $body,
            'category' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'canread' => $canread,
            'canwrite' => $canwrite,
            'userid' => $this->Users->userData['userid'],
        ));
        $newId = $this->Db->lastInsertId();

        // insert the tags from the template
        if ($tpl !== 0) {
            $this->Links->duplicate($tpl, $newId, true);
            $this->Steps->duplicate($tpl, $newId, true);
            $Tags = new Tags($Templates);
            $Tags->copyTags($newId, true);
        }

        return $newId;
    }

    /**
     * Read all experiments related to a DB item
     *
     * @param int $itemId the DB item
     * @return array
     */
    public function readRelated(int $itemId): array
    {
        $itemsArr = array();

        // get the id of related experiments
        $sql = 'SELECT item_id FROM experiments_links
            WHERE link_id = :link_id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':link_id', $itemId, PDO::PARAM_INT);
        $this->Db->execute($req);
        while ($data = $req->fetch()) {
            $this->setId((int) $data['item_id']);
            $itemsArr[] = $this->read();
        }

        return $itemsArr;
    }

    public function getBoundEvents(): array
    {
        $sql = 'SELECT team_events.* from team_events WHERE experiment = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);
        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Can this experiment be timestamped?
     *
     * @return bool
     */
    public function isTimestampable(): bool
    {
        $currentCategory = (int) $this->entityData['category_id'];
        $sql = 'SELECT is_timestampable FROM status WHERE id = :category;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':category', $currentCategory, PDO::PARAM_INT);
        $this->Db->execute($req);
        return (bool) $req->fetchColumn();
    }

    /**
     * Set the experiment as timestamped with a path to the token
     *
     * @param string $responseTime the date of the timestamp
     * @param string $responsefilePath the file path to the timestamp token
     * @return void
     */
    public function updateTimestamp(string $responseTime, string $responsefilePath): void
    {
        $this->canOrExplode('write');

        $sql = 'UPDATE experiments SET
            locked = 1,
            lockedby = :userid,
            lockedwhen = :when,
            timestamped = 1,
            timestampedby = :userid,
            timestampedwhen = :when,
            timestamptoken = :longname
            WHERE id = :id;';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':when', $responseTime);
        // the date recorded in the db has to match the creation time of the timestamp token
        $req->bindParam(':longname', $responsefilePath);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);

        $this->Db->execute($req);
    }

    /**
     * Duplicate an experiment
     *
     * @return int the ID of the new item
     */
    public function duplicate(): int
    {
        $this->canOrExplode('read');

        // let's add something at the end of the title to show it's a duplicate
        // capital i looks good enough
        $title = $this->entityData['title'] . ' I';

        $sql = 'INSERT INTO experiments(title, date, body, category, elabid, canread, canwrite, datetime, userid)
            VALUES(:title, :date, :body, :category, :elabid, :canread, :canwrite, NOW(), :userid)';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req, array(
            'title' => $title,
            'date' => Filter::kdate(),
            'body' => $this->entityData['body'],
            'category' => $this->getStatus(),
            'elabid' => $this->generateElabid(),
            'canread' => $this->entityData['canread'],
            'canwrite' => $this->entityData['canwrite'],
            'userid' => $this->Users->userData['userid'],
        ));
        $newId = $this->Db->lastInsertId();

        if ($this->id === null) {
            throw new IllegalActionException('Try to duplicate without an id.');
        }
        $this->Links->duplicate($this->id, $newId);
        $this->Steps->duplicate($this->id, $newId);
        $this->Tags->copyTags($newId);

        return $newId;
    }

    /**
     * Destroy an experiment and all associated data
     * The foreign key constraints will take care of associated tables
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->canOrExplode('write');

        $this->Tags->destroyAll();
        $this->Uploads->destroyAll();

        $sql = 'DELETE FROM experiments WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $this->id, PDO::PARAM_INT);
        $this->Db->execute($req);

        // delete from pinned
        $this->Pins->rmFromPinned();
    }

    /**
     * Get the team from the elabid
     *
     * @param string $elabid
     * @return int
     */
    public function getTeamFromElabid(string $elabid): int
    {
        $sql = 'SELECT users2teams.teams_id FROM `experiments`
            CROSS JOIN users2teams ON (users2teams.users_id = experiments.userid)
            WHERE experiments.elabid = :elabid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':elabid', $elabid, PDO::PARAM_STR);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Count all the experiments owned by a user
     *
     * @return int
     */
    public function countAll(): int
    {
        $sql = 'SELECT COUNT(id) FROM experiments WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
        return (int) $req->fetchColumn();
    }

    /**
     * Get the current unfinished steps from experiments owned by current user
     *
     * @return array
     */
    public function getSteps(): array
    {
        $sql = "SELECT experiments.id, experiments.title, stepst.finished, stepst.steps_body, stepst.steps_id
            FROM experiments
            CROSS JOIN (
                SELECT item_id, finished,
                GROUP_CONCAT(experiments_steps.body SEPARATOR '|') AS steps_body,
                GROUP_CONCAT(experiments_steps.id SEPARATOR '|') AS steps_id
                FROM experiments_steps
                WHERE finished = 0 GROUP BY item_id) AS stepst ON (stepst.item_id = experiments.id)
            WHERE userid = :userid GROUP BY experiments.id ORDER BY experiments.id DESC";

        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }

        // clean up the results so we get a nice array with experiment id/title and steps with their id/body
        // use reference to edit in place
        foreach ($res as &$exp) {
            $exp['steps'] = array_combine(explode('|', $exp['steps_id']), explode('|', $exp['steps_body']));
            unset($exp['steps_body'], $exp['steps_id'], $exp['finished']);
        }

        return $res;
    }

    /**
     * Select what will be the status for the experiment
     *
     * @return int The status ID
     */
    private function getStatus(): int
    {
        // what will be the status ?
        // go pick what is the default status upon creating experiment
        // there should be only one because upon making a status default,
        // all the others are made not default
        $sql = 'SELECT id FROM status WHERE is_default = true AND team = :team LIMIT 1';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
        $this->Db->execute($req);
        $status = $req->fetchColumn();

        // if there is no is_default status
        // we take the first status that come
        if (!$status) {
            $sql = 'SELECT id FROM status WHERE team = :team LIMIT 1';
            $req = $this->Db->prepare($sql);
            $req->bindParam(':team', $this->Users->userData['team'], PDO::PARAM_INT);
            $this->Db->execute($req);
            $status = $req->fetchColumn();
        }
        return (int) $status;
    }

    /**
     * Generate unique elabID
     * This function is called during the creation of an experiment.
     *
     * @return string unique elabid with date in front of it
     */
    private function generateElabid(): string
    {
        $date = Filter::kdate();
        return $date . '-' . sha1(bin2hex(random_bytes(16)));
    }
}
