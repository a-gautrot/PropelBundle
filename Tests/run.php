<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$_SERVER['argv'] = array_merge(
    [$_SERVER['argv'][0], '--configuration', dirname(__DIR__) . '/phpunit.dist.xml'],
    array_slice($_SERVER['argv'], 1)
);
$_SERVER['argc'] = count($_SERVER['argv']);

exit((new \PHPUnit\TextUI\Application())->run($_SERVER['argv']));
