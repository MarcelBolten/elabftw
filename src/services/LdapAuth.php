<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Services;

use Elabftw\Elabftw\AuthResponse;
use Elabftw\Exceptions\ImproperActionException;
use Elabftw\Exceptions\InvalidCredentialsException;
use Elabftw\Exceptions\ResourceNotFoundException;
use Elabftw\Interfaces\AuthInterface;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use LdapRecord\Connection;

/**
 * LDAP auth service
 */
class LdapAuth implements AuthInterface
{
    private string $email = '';

    private AuthResponse $AuthResponse;

    public function __construct(private Connection $connection, private array $configArr, string $email, private string $password)
    {
        $this->email = Filter::sanitize($email);
        $this->AuthResponse = new AuthResponse('ldap');
    }

    public function tryAuth(): AuthResponse
    {
        $query = $this->connection->query()->setDn($this->configArr['ldap_base_dn']);
        $record = $query->findbyOrFail('mail', $this->email);
        $uidOrCnConfig = $this->configArr['ldap_uid_cn'];
        $uidOrCn = $record[$uidOrCnConfig][0];
        if (!$this->connection->auth()->attempt($uidOrCnConfig . '=' . $uidOrCn . ',' . $this->configArr['ldap_base_dn'], $this->password)) {
            throw new InvalidCredentialsException();
        }
        $Users = new Users();
        $Teams = new Teams($Users);
        try {
            $Users->populateFromEmail($this->email);
        } catch (ResourceNotFoundException) {
            // the user doesn't exist yet in the db
            // what do we do? Lookup the config setting for that case
            if ($this->configArr['saml_user_default'] === '0') {
                throw new ImproperActionException('Could not find an existing user. Ask a Sysadmin to create your account.');
            }
            // GET FIRSTNAME AND LASTNAME
            $firstname = $record[$this->configArr['ldap_firstname']][0] ?? 'Unknown';
            $lastname = $record[$this->configArr['ldap_lastname']][0] ?? 'Unknown';
            // GET TEAMS
            $teamFromLdap = $record[$this->configArr['ldap_team']][0];
            // if no team attribute is sent by the LDAP server, use the default team
            if (empty($teamFromLdap)) {
                // we directly get the id from the stored config
                $teamId = (int) $this->configArr['saml_team_default'];
                if ($teamId === 0) {
                    throw new ImproperActionException('Could not find team ID to assign user!');
                }
                $teamFromLdap = array($teamId);
            } elseif (is_string($teamFromLdap)) {
                $teamFromLdap = array($teamFromLdap);
            }
            // normalize the team(s)
            $teams = $Teams->getTeamsFromIdOrNameOrOrgidArray($teamFromLdap)[0];
            // CREATE USER (and force validation of user)
            $Users = new Users($Users->create($this->email, $teams, $firstname, $lastname, '', null, true));
        }

        $this->AuthResponse->userid = (int) $Users->userData['userid'];
        $this->AuthResponse->mfaSecret = $Users->userData['mfa_secret'];
        $this->AuthResponse->setTeams();

        return $this->AuthResponse;
    }
}
