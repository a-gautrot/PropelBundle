<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DataFixtures\Loader;

use Override;
use SimpleXMLElement;

/**
 * # XmlDataLoader
 * XML fixtures loader.
 *
 * @author William Durand <william.durand1@gmail.com>
 * @api
 */
class XmlDataLoader extends AbstractDataLoader
{
    /**
     * @param string $file
     * @return array<string, array<string, array<string, string>>>
     */
    #[Override]
    protected function transformDataToArray(string $file): array
    {
        $xml = simplexml_load_file($file);

        return $this->simpleXmlToArray($xml);
    }

    /**
     * @param SimpleXMLElement|false $xml
     * @return array<string, array<string, array<string, string>>>
     */
    protected function simpleXmlToArray($xml): array
    {
        $array = [];
        if ($xml instanceof SimpleXMLElement) {
            $classes = $xml->xpath('*[namespace-uri() = namespace-uri(..)]');
            foreach (is_array($classes) ? $classes : [] as $value) {
                $key = $value->getName();
                // First make a valid key which is the Ns (Namespace) attribute
                // + the element name (the class name)
                $attributes = $value->xpath('@*[namespace-uri() = ""]');
                foreach (is_array($attributes) ? $attributes : [] as $subvalue) {
                    $subkey = $subvalue->getName();
                    if ($subkey === 'Namespace') {
                        $key = "$subvalue\\$key";
                        break;
                    }
                }

                $array[$key] = [];
                $elements = $value->xpath('*[namespace-uri() = namespace-uri(..)]');
                foreach (is_array($elements) ? $elements : [] as $elementValue) {
                    $elementKey = $elementValue->getName();
                    $array[$key][$elementKey] = [];

                    $attributes = $elementValue->xpath('@*[namespace-uri() = ""]');
                    foreach (is_array($attributes) ? $attributes : [] as $subvalue) {
                        $array[$key][$elementKey][$subvalue->getName()] = (string)$subvalue;
                    }
                }
            }
        }

        return $array;
    }
}
