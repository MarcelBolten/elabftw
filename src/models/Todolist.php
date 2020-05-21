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

use Elabftw\Elabftw\Db;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Interfaces\CrudInterface;
use Elabftw\Traits\SortableTrait;
use PDO;

/**
 * All about the todolist
 */
class Todolist implements CrudInterface
{
    use SortableTrait;

    /** @var Users $Users our user */
    public $Users;

    /** @var Db $Db SQL Database */
    protected $Db;

    /**
     * Gimme a userid
     *
     * @param Users $users
     */
    public function __construct(Users $users)
    {
        $this->Db = Db::getConnection();
        $this->Users = $users;
    }

    /**
     * Create a todoitem
     *
     * @param string $body
     * @return int the id of the created todoitem
     */
    public function create(string $body): int
    {
        $sql = 'INSERT INTO todolist(body, userid)
            VALUES(:body, :userid)';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':body', $body);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        return $this->Db->lastInsertId();
    }

    /**
     * Select all the todoitems for a user
     *
     * @return array
     */
    public function readAll(): array
    {
        $sql = 'SELECT id, body, creation_time FROM todolist WHERE userid = :userid ORDER BY ordering ASC, creation_time DESC';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);

        $res = $req->fetchAll();
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Update the body of a todoitem with jeditable
     *
     * @param int $id Id of the todoitem
     * @param string $body
     * @return void
     */
    public function update(int $id, string $body): void
    {
        if (\mb_strlen($body) < 2) {
            throw new ImproperActionException(sprintf(_('Input is too short! (minimum: %d)'), 2));
        }
        $sql = 'UPDATE todolist SET body = :body WHERE id = :id';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':body', $body);
        $this->Db->execute($req);
    }

    /**
     * Remove a todoitem
     *
     * @param int $id
     * @return void
     */
    public function destroy(int $id): void
    {
        $sql = 'DELETE FROM todolist WHERE id = :id AND userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':id', $id, PDO::PARAM_INT);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
    }

    /**
     * Clear all todoitems from the todolist
     *
     * @return void
     */
    public function destroyAll(): void
    {
        $sql = 'DELETE FROM todolist WHERE userid = :userid';
        $req = $this->Db->prepare($sql);
        $req->bindParam(':userid', $this->Users->userData['userid'], PDO::PARAM_INT);
        $this->Db->execute($req);
    }
}
