# PropelBundle

---

[![Latest stable version](https://img.shields.io/packagist/v/gautrot/propel-bundle)](https://packagist.org/packages/gautrot/propel-bundle)
![Minimum PHP version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)
[![GitHub Actions workflow status](https://img.shields.io/github/actions/workflow/status/Gautrot/PropelBundle/ci.yml?branch=dev&label=GitHub%20Actions%20-%20CI%20branch%20dev)](https://github.com/Gautrot/PropelBundle/actions/workflows/ci.yml)
[![Codecov (dev)](https://img.shields.io/codecov/c/github/gautrot/PropelBundle/dev)](https://codecov.io/gh/gautrot/PropelBundle/tree/dev)

**PropelBundle** is the official implementation of [Propel](https://www.propelorm.org/) in Symfony.

## Installation

### Minimum requirements

| [Symfony](https://symfony.com/)                             | [PHP](https://www.php.net/)                    | [Propel](https://propelorm.org/)                                     | PropelBundle                                                                 |
|-------------------------------------------------------------|------------------------------------------------|----------------------------------------------------------------------|------------------------------------------------------------------------------|
| **[8.4](https://symfony.com/releases/8.4)** (Currently 8.1) | [8.4](https://www.php.net/releases/8.4/en.php) | [2.0.0](https://github.com/propelorm/Propel2/tree/2.0.0)             | **[8.0](https://github.com/Gautrot/PropelBundle/tree/8.0)** (In development) |
| **[7.4](https://symfony.com/releases/7.4)**                 | [8.2](https://www.php.net/releases/8.2/en.php) | [2.0.0](https://github.com/propelorm/Propel2/tree/2.0.0)             | **[7.1](https://github.com/Gautrot/PropelBundle/tree/7.1)**                  |
| **[6.4](https://symfony.com/releases/6.4)**                 | [8.1](https://www.php.net/releases/8.1/en.php) | [2.0.0](https://github.com/propelorm/Propel2/tree/2.0.0)             | **[6.1](https://github.com/Gautrot/PropelBundle/tree/6.1)** (LTS)            |
| **[5.4](https://symfony.com/releases/5.4)**                 | 7.4                                            | [2.0.0-beta4](https://github.com/propelorm/Propel2/tree/2.0.0-beta4) | **[5.2](https://github.com/Gautrot/PropelBundle/tree/5.2)** (Extended LTS)   |

For Symfony 5.4, if you are using PHP 7.2 or 7.3, you **must** upgrade to PHP 7.4 at least.

### Composer

```shell
composer require propel/propel "~2.0"
composer require gautrot/propel-bundle "~7.1" # (or newer/older tag/release)
# or
composer require gautrot/propel-bundle "7.1.x-dev"
```

#### composer.json

Stable release:

```json
{
  "require": {
    "propel/propel": "~2.0",
    "gautrot/propel-bundle": "~7.1"
  }
}
```

Nightly release:

```json
{
  "require": {
    "propel/propel": "~2.0",
    "gautrot/propel-bundle": "7.1.x-dev"
  }
}
```

## Features

* Generation of model classes based on XML or YAML schemas, placed under
  `BundleName/Resources/config/*schema.{xml,yaml,yml}`;
* Insertion of SQL statements;
* Runtime autoloading of Propel and generated classes;
* Propel runtime initialization through Symfony configuration;
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

### Deprecated

* [Sensio Framework Extra Bundle](https://github.com/sensiolabs/SensioFrameworkExtraBundle), has been deprecated since
  Symfony **6.2**. If you are still using it, you must convert them
  to [Symfony Attributes](https://symfony.com/doc/6.4/reference/attributes.html).
* XmlFileLoader has been [deprecated](https://symfony.com/blog/new-in-symfony-7-4-deprecated-xml-configuration) since
  Symfony **7.4**. Use YAML or PHP for Symfony service configuration. This deprecation does not apply to Propel XML
  schemas or PHPUnit's XML configuration, however it is recommended to migrate to either formats for consistency.

## New to version 7.1

* Added support for Symfony 7.4
    * Removed support for Symfony 6 and 7.0 to 7.3
        * LTS for Symfony 6.4 was migrated to a new branch: [6.1](https://github.com/Gautrot/PropelBundle/tree/6.1)
        * Extended LTS for Symfony 5.4 was migrated to a new
          branch: [5.2](https://github.com/Gautrot/PropelBundle/tree/5.2)
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 8.0 and 8.1 in branch `7.1`
    * Removed support for PHP 8.0 in branch `6.1`
    * Removed support for PHP 7.2 and 7.3 in branch `5.2`
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to its official stable release (2.0.0) for `7.1` and `6.1` and last beta
  release (2.0.0-beta4) for `5.2`
* Added support for YAML and PHP formats (schemas, services)
    * XmlFileLoader has been deprecated since Symfony 7.4
    * YAML schemas are backports of Propel 1.x's YAML schemas

You can also read the changelogs [here](CHANGELOG.md).

## And more...

You can read and learn Propel 2 from their official documentation [here](https://propelorm.org/documentation/). For
licenses, see: [LICENSE](Resources/meta/LICENSE). If you want more details about PropelBundle, [go here](Misc/MORE.md).
