# PropelBundle

---

![Minimum PHP version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)
[![GitHub Actions workflow status](https://img.shields.io/github/actions/workflow/status/a-gautrot/PropelBundle/ci.yml?branch=8.0&label=GitHub%20Actions%20-%20CI%20branch%208.0)](https://github.com/a-gautrot/PropelBundle/actions/workflows/ci.yml)
[![Codecov (8.0)](https://img.shields.io/codecov/c/github/a-gautrot/PropelBundle/8.0)](https://codecov.io/gh/a-gautrot/PropelBundle/tree/8.0)

This is the official implementation of [Propel](https://www.propelorm.org/) in Symfony.

## Installation

### Minimum requirements

- [Symfony](https://symfony.com/releases/8.4): **8.4**
- [PHP](https://www.php.net/releases/8.4/en.php): **8.4**
- [Propel](https://github.com/propelorm/Propel2/tree/2.0.0): **2.0.0**

### Composer

```shell
composer require propel/propel "~2.0"
composer require a-gautrot/propel-bundle "~8.0" # (or newer tag/release)
# or
composer require a-gautrot/propel-bundle "8.0.x-dev"
```

#### composer.json

Stable release:

```json
{
  "require": {
    "propel/propel": "~2.0",
    "a-gautrot/propel-bundle": "~8.0"
  }
}
```

Nightly release:

```json
{
  "require": {
    "propel/propel": "~2.0",
    "a-gautrot/propel-bundle": "8.0.x-dev"
  }
}
```

## Features

* Generation of model classes based on an XML schema only, placed under `BundleName/Resources/*schema.xml`;
* Insertion of SQL statements;
* Runtime autoloading of Propel and generated classes;
* Propel runtime initialization through the XML configuration;
* [Propel migrations](https://propelorm.org/documentation/09-migrations.html);
* Reverse engineering
  from [existing database](https://propelorm.org/documentation/cookbook/working-with-existing-databases.html);
* Integration to [Symfony Profiler](https://symfony.com/doc/7.4/profiler.html);
* Load SQL, YAML, and XML fixtures;
* Create and drop databases;
* Integration with the [Form component](https://symfony.com/doc/7.4/forms.html);
* Integration with the [Security component](https://symfony.com/doc/7.4/security.html);
* Propel's ParamConverter can be used
  with [Symfony Attributes](https://symfony.com/doc/7.4/reference/attributes.html).

## New to version 8.0

* Added support for Symfony 8.1

You can also read the changelogs [here](CHANGELOG.md).

## And more...

You can read and learn Propel 2 from their official documentation [here](https://propelorm.org/documentation/). For
licenses, see: [LICENSE](Resources/meta/LICENSE).
