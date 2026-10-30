<?php

/**
 * This file is part of the PropelBundle package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Propel\Bundle\PropelBundle\DataFixtures\Loader;

use Faker\Generator;
use Override;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * # YamlDataLoader
 * YAML fixtures loader.
 *
 * @author William Durand <william.durand1@gmail.com>
 * @api
 */
class YamlDataLoader extends AbstractDataLoader
{
    /**
     * @var Generator|null
     */
    private ?Generator $faker;

    /**
     * @param string $rootDir
     * @param array{database?: array{connections: array<string, array<string, mixed>>}} $datasources
     * @param Generator|null $faker
     */
    public function __construct(string $rootDir, array $datasources, ?Generator $faker = null)
    {
        parent::__construct($rootDir, $datasources);

        $this->faker = $faker;
    }

    /**
     * @param string $file
     * @return array<string, mixed>
     * @psalm-suppress UnusedVariable The faker closure is used by included fixture templates.
     * @throws ParseException
     */
    #[Override]
    protected function transformDataToArray(string $file): array
    {
        if (!str_contains($file, "\n") && is_file($file)) {
            if (is_readable($file) === false) {
                throw new ParseException("Unable to parse \"$file\" as the file is not readable.");
            }

            if ($this->faker !== null) {
                $generator = $this->faker;
                /** @psalm-suppress UnusedVariable Available to PHP fixture templates through include. */
                $faker = function (string $type) use ($generator): void {
                    $args = func_get_args();
                    array_shift($args);

                    echo Yaml::dump(call_user_func_array([$generator, $type], $args)) . "\n";
                };
            } else {
                /** @psalm-suppress UnusedVariable Available to PHP fixture templates through include. */
                $faker = function (mixed $text): void {
                    echo "$text\n";
                };
            }

            ob_start();
            /** @psalm-suppress UnresolvableInclude Fixture paths are supplied by the caller and checked above. */
            $retval = include $file;
            $content = ob_get_clean();

            // if an array is returned by the config file assume it's in plain php form else in YAML
            $file = is_array($retval) ? $retval : ($content === false ? '' : $content);

            // if an array is returned by the config file assume it's in plain php form else in YAML
            if (is_array($file)) {
                return $file;
            }
        }

        $data = Yaml::parse($file);
        if (!is_array($data)) {
            throw new ParseException('Fixture data must be an array.');
        }

        return $data;
    }
}
