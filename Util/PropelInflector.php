<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\Util;

/**
 * # PropelInflector
 * The Propel Inflector class provides methods for inflecting text.
 *
 * @author William Durand <william.durand1@gmail.com>
 */
class PropelInflector
{
    /**
     * Camelize a word.
     * Inspired by https://github.com/doctrine/common/blob/2.3/lib/Doctrine/Common/Util/Inflector.php
     *
     * @param string|null $word The word to camelize.
     * @return string|null
     */
    public static function camelize(?string $word): ?string
    {
        if (empty($word)) {
            return $word;
        }

        return lcfirst(str_replace(" ", "", ucwords(strtr($word, "_-", "  "))));
    }
}
