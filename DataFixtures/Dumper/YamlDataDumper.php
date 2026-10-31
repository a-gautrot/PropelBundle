<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DataFixtures\Dumper;

use Symfony\Component\Yaml\Yaml;

/**
 * # YamlDataDumper
 * YAML fixtures dumper.
 *
 * @author William Durand <william.durand1@gmail.com>
 * @api
 */
class YamlDataDumper extends AbstractDataDumper
{
    /**
     * @param array<string, array<string, array<string, mixed>>> $data
     * @return string
     */
    #[\Override]
    protected function transformArrayToData(array $data): string
    {
        return Yaml::dump($data, 3, 4, Yaml::DUMP_OBJECT);
    }
}
