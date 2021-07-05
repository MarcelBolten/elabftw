<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

use Elabftw\Models\Experiments;
use Elabftw\Models\Items;
use Elabftw\Models\Users;

class MakeCsvTest extends \PHPUnit\Framework\TestCase
{
    private MakeCsv $MakeExp;

    private MakeCsv $MakeDb;

    protected function setUp(): void
    {
        $this->MakeExp = new MakeCsv(new Experiments(new Users(1, 1)), '1 2 3');
        $this->MakeDb = new MakeCsv(new Items(new Users(1, 1)), '1 2 3');
    }

    public function testGetFileName(): void
    {
        $this->assertMatchesRegularExpression('/\d{8}-export.elabftw.csv/', $this->MakeExp->getFileName());
    }

    public function testGetCsvExp(): void
    {
        $csv = $this->MakeExp->getCsv();
    }

    public function testGetCsvDb(): void
    {
        $csv = $this->MakeDb->getCsv();
    }
}
