<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Tests\DataFixtures\Dumper;

use Propel\Bundle\PropelBundle\DataFixtures\Dumper\YamlDataDumper;
use Propel\Bundle\PropelBundle\Tests\DataFixtures\TestCase;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook;
use Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor;
use stdClass;

/**
 * # YamlDataDumperTest
 * @author William Durand <william.durand1@gmail.com>
 * @author Toni Uebernickel <tuebernickel@gmail.com>
 */
class YamlDataDumperTest extends TestCase
{
    /**
     * @return void
     */
    public function testYamlDump(): void
    {
        $author = new CoolBookAuthor();
        $author->setId(1);
        $author->setName('A famous one')->save($this->con);

        $complementary = new stdClass();
        $complementary->first_word_date = '2012-01-01';

        $book = new CoolBook();
        $book
            ->setId(1)
            ->setName('An important one')
            ->setAuthorId(1)
            ->setComplementaryInfos($complementary)
            ->save($this->con);

        $filename = $this->getTempFile();

        $loader = new YamlDataDumper(__DIR__ . '/../../Fixtures/DataFixtures/Loader', []);
        $loader->dump($filename);

        $expected = <<<YAML
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBookAuthor:
    CoolBookAuthor_1:
        id: '1'
        name: 'A famous one'
\Propel\Bundle\PropelBundle\Tests\Fixtures\DataFixtures\Loader\CoolBook:
    CoolBook_1:
        id: '1'
        name: 'An important one'
        author_id: CoolBookAuthor_1
        complementary_infos: !php/object 'O:8:"stdClass":1:{s:15:"first_word_date";s:10:"2012-01-01";}'

YAML;
        $result = file_get_contents($filename);

        $this->assertEquals(str_replace(["\r\n", "\n", "\r"], '', $expected), str_replace(["\r\n", "\n", "\r"], '', $result));
    }
}
