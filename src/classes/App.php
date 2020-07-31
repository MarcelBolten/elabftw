<?php
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */
declare(strict_types=1);

namespace Elabftw\Elabftw;

use Elabftw\Models\Config;
use Elabftw\Models\Teams;
use Elabftw\Models\Users;
use Elabftw\Services\Check;
use Elabftw\Traits\TwigTrait;
use Elabftw\Traits\UploadTrait;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Logger;
use function substr;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * This is a super class holding various global objects
 */
class App
{
    use UploadTrait;
    use TwigTrait;

    /** @var Request $Request the request */
    public $Request;

    /** @var Session $Session the session */
    public $Session;

    /** @var Config $Config the config stored in sql */
    public $Config;

    /** @var Logger $Log instance of Logger */
    public $Log;

    /** @var Csrf $Csrf instance of Csrf */
    public $Csrf;

    /** @var Users $Users instance of Users */
    public $Users;

    /** @var Db $Db SQL Database */
    public $Db;

    /** @var string $pageTitle the title for the current page */
    public $pageTitle = 'Lab manager';

    /** @var array $ok the ok messages from flashBag */
    public $ok = array();

    /** @var array $ko the ko messages from flashBag */
    public $ko = array();

    /** @var array $warning the warning messages from flashBag */
    public $warning = array();

    /** @var array $teamConfigArr the config for the current team */
    public $teamConfigArr = array();

    /**
     * Constructor
     *
     * @param Request $request
     */
    public function __construct(Request $request, Session $session, Config $config, Logger $log, Csrf $csrf)
    {
        $this->Request = $request;
        $this->Session = $session;
        $this->ok = $this->Session->getFlashBag()->get('ok');
        $this->ko = $this->Session->getFlashBag()->get('ko');
        $this->warning = $this->Session->getFlashBag()->get('warning');

        $this->Config = $config;
        $this->Log = $log;
        $this->Log->pushHandler(new ErrorLogHandler());
        $this->Csrf = $csrf;

        $this->Users = new Users();
        $this->Db = Db::getConnection();
        // UPDATE SQL SCHEMA if necessary or show error message if version mismatch
        $Update = new Update($this->Config, new Sql());
        $Update->checkSchema();
    }

    /**
     * Get the page generation time (called in the footer)
     *
     * @return float
     */
    public function getGenerationTime(): float
    {
        return round(microtime(true) - $this->Request->server->get('REQUEST_TIME_FLOAT'), 5);
    }

    /**
     * Get the current memory usage (called in the footer)
     *
     * @return int
     */
    public function getMemoryUsage(): int
    {
        return memory_get_usage();
    }

    /**
     * Get the mininum password length for injecting in templates
     *
     * @return int
     */
    public function getMinPasswordLength(): int
    {
        return Check::MIN_PASSWORD_LENGTH;
    }

    /**
     * If the current user is authenticated, load Users with an id
     *
     * @param Users $users
     * @return void
     */
    public function loadUser(Users $users): void
    {
        $this->Users = $users;

        // team config
        $Teams = new Teams($this->Users);
        $this->teamConfigArr = $Teams->read();
    }

    /**
     * Get the lang (in short form like 'en' or 'fr') for the HTML attribute in head.html template
     *
     * @return string
     */
    public function getLangForHtmlAttribute(): string
    {
        $lang = 'en';
        if (isset($this->Users->userData['lang'])) {
            $lang = substr($this->Users->userData['lang'], 0, 2);
        }

        return $lang;
    }

    /**
     * Generate HTML from a twig template. The App object is injected into every template.
     *
     * @param string $template template located in app/tpl/
     * @param array $variables the variables injected in the template
     * @return string html
     */
    public function render(string $template, array $variables): string
    {
        return $this->getTwig($this->Config)->render($template, array_merge(array('App' => $this), $variables));
    }
}
