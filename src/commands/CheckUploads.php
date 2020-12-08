<?php
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */
declare(strict_types=1);

namespace Elabftw\Commands;

use Elabftw\Elabftw\Db;
use Elabftw\Traits\UploadTrait;
use file_exists;
use PDO;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Check the the current schema version versus the required one
 */
class CheckUploads extends Command
{
    use UploadTrait;

    /** @var Db $Db SQL Database */
    protected $Db;

    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'uploads:orphans';

    /**
     * Set the help messages
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('Find orphan files')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to find orphan files.');
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int 0 if no inconsistencies, 1 if DB and HD don't match
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->Db = Db::getConnection();

        $filesHD = $this->getAllFromHD();
        $filesDB = $this->readAllFormDB();

        if (count($filesHD) === 0 && count($filesDB) === 0) {
            $output->writeln('No uploaded files.');
            return 0;
        }

        $output->writeln('Files that are missing a counterpart in the DB:');

        $onlyOnHD = array_diff($filesHD, $filesDB);
        $n = 0;
        foreach ($onlyOnHD as $file) {
            if (strpos($file, '_th')) {
                $parentFile = preg_replace('/\.(.*)_th\..*/', '.\1', $file);
                if (!in_array($parentFile, $filesHD)) {
                    $output->writeln($file);
                    $n++;
                }
            } else {
                $output->writeln($file);
                $n++;
            }
        }

        if ($n === 0) {
            $output->writeln('There are no missing entries in the DB.');
        }

        $output->writeln('');
        $output->writeln('Files that are missing a counterpart on the HD:');

        $onlyInDB = array_diff($filesDB, $filesHD);
        foreach ($onlyInDB as $file) {
            $output->writeln($file);
        }

        if (count($onlyInDB) === 0) {
            $output->writeln('There are no missing files on the HD.');
        }

        if ($n === 0 && count($onlyInDB) === 0) {
            $output->writeln('');
            $output->writeln('All good!');
            return 0;
        }

        return 1;
    }

    /**
     * Read all uploads from DB
     *
     * @return array
     */
    protected function readAllFormDB(): array
    {
        $sql = 'SELECT long_name FROM uploads';
        $req = $this->Db->prepare($sql);
        $this->Db->execute($req);
        $res = $req->fetchAll(PDO::FETCH_COLUMN, 'long_name');
        if ($res === false) {
            return array();
        }
        return $res;
    }

    /**
     * Get all files from upload folder on hard drive
     *
     * @return array
     */
    protected function getAllFromHD(): array
    {
        chdir($this->getUploadsPath());
        $filesOnServer = glob('*/*', GLOB_NOSORT);
        chdir(dirname(__DIR__, 2));
        if ($filesOnServer === false) {
            return array();
        }
        return $filesOnServer;
    }
}
