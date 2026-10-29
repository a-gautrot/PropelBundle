# PropelBundle

---

![Minimum PHP version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)
[![GitHub Actions workflow status](https://img.shields.io/github/actions/workflow/status/Gautrot/PropelBundle/ci.yml?branch=6.1&label=GitHub%20Actions%20-%20CI%20branch%206.1)](https://github.com/Gautrot/PropelBundle/actions/workflows/ci.yml)
[![Codecov (6.1)](https://img.shields.io/codecov/c/github/gautrot/PropelBundle/6.1)](https://codecov.io/gh/gautrot/PropelBundle/tree/6.1)

**PropelBundle** is the official implementation of [Propel](https://www.propelorm.org/) in Symfony. This version is a
LTS version for Symfony 6.4 only.

## Installation

### Minimum requirements

- [Symfony](https://symfony.com/releases/6.4): **6.4**
- [PHP](https://www.php.net/releases/8.1/en.php): **8.1**
- [Propel](https://github.com/propelorm/Propel2/tree/2.0.0): **2.0.0**

### Composer

```shell
composer require propel/propel "~2.0"
composer require agautrot/propel-bundle "~6.1" # (or newer tag/release)
# or
composer require agautrot/propel-bundle "6.1.x-dev"
```

#### composer.json

Stable release:

```json
{
  "require": {
    "propel/propel": "~2.0",
    "agautrot/propel-bundle": "~6.1"
  }
}
```

Nightly release:

```json
{
  "require": {
    "propel/propel": "~2.0",
    "agautrot/propel-bundle": "6.1.x-dev"
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
* Integration to [Symfony Profiler](https://symfony.com/doc/6.4/profiler.html);
* Load SQL, YAML, and XML fixtures;
* Create and drop databases;
* Integration with the [Form component](https://symfony.com/doc/6.4/forms.html);
* Integration with the [Security component](https://symfony.com/doc/6.4/security.html);
* Propel's ParamConverter can be used
  with [Symfony Attributes](https://symfony.com/doc/6.4/reference/attributes.html).

## New to version 6.1

* Removed support for Symfony 6.0 to 6.3
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 8.0
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to its official stable release (2.0.0)

You can also read the changelogs [here](CHANGELOG.md).

## And more...

You can read and learn Propel 2 from their official documentation [here](https://propelorm.org/documentation/). For
licenses, see: [LICENSE](LICENSE).
