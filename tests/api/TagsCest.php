<?php declare(strict_types=1);
/**
 * @package   Elabftw\Elabftw
 * @author    Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2012 Nicolas CARPi
 * @license   https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @see       https://www.elabftw.net Official website
 */

use \Codeception\Util\HttpCode;

class TagsCest
{
    public function _before(ApiTester $I)
    {
        $I->haveHttpHeader('Authorization', 'apiKey4Test');
    }

    public function getTagsTest(ApiTester $I)
    {
        $I->wantTo('Get tags');
        $I->sendGET('/tags');
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }
}
