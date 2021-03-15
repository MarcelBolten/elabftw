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
use Elabftw\Models\Users;

class MakeJsonTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        $this->Make = new MakeJson(new Experiments(new Users(1, 1)), '1 2 3');
    }

    public function testGetFileName()
    {
        $this->assertEquals('export-elabftw.json', $this->Make->getFileName());
    }

    public function testGetJson()
    {
        $this->assertTrue(is_array($this->Make->getJson()));
    }
}
