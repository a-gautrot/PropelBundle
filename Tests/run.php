<?php

declare(strict_types=1);

use PHPUnit\Runner\Version;
use PHPUnit\TextUI\Application;
use PHPUnit\TextUI\Command;

require dirname(__DIR__) . '/vendor/autoload.php';

$legacy = version_compare(Version::id(), '10.0.0', '<');
$configuration = $legacy ? 'phpunit-legacy.dist.xml' : 'phpunit.dist.xml';

$arguments = array_slice($_SERVER['argv'], 1);
if ($legacy) {
    $arguments = array_values(array_filter($arguments, static fn(string $argument): bool => !in_array($argument, ['--display-deprecations', '--display-phpunit-deprecations'], true)));
}

$_SERVER['argv'] = array_merge(
    [$_SERVER['argv'][0], '--configuration', dirname(__DIR__) . '/' . $configuration],
    $arguments
);
$_SERVER['argc'] = count($_SERVER['argv']);

if ($legacy) {
    Command::main();
} else {
    exit((new Application())->run($_SERVER['argv']));
}
