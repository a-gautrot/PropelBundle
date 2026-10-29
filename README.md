# PropelBundle

---

![Minimum PHP version](https://img.shields.io/badge/php-%3E%3D%207.4-8892BF.svg)
[![GitHub Actions workflow status](https://img.shields.io/github/actions/workflow/status/a-gautrot/PropelBundle/ci.yml?branch=5.2&label=GitHub%20Actions%20-%20CI%20branch%205.2)](https://github.com/a-gautrot/PropelBundle/actions/workflows/ci.yml)
[![Codecov (5.2)](https://img.shields.io/codecov/c/github/a-gautrot/PropelBundle/5.2)](https://codecov.io/gh/a-gautrot/PropelBundle/tree/5.2)

**PropelBundle** is the official implementation of [Propel](https://www.propelorm.org/) in Symfony. This version is an
extended LTS version for Symfony 5.4 only.

## Installation

### Minimum requirements

- [Symfony](https://symfony.com/releases/5.4): **5.4**
- [PHP](https://www.php.net/): **7.4**
    - If you are using PHP 7.2 or 7.3, you **must** upgrade to PHP 7.4 at least.
- [Propel](https://github.com/propelorm/Propel2/tree/2.0.0-beta4): **2.0.0 - beta 4**

### Composer

```shell
composer require propel/propel "2.0.0-beta4"
composer require a-gautrot/propel-bundle "~5.2" # (or newer tag/release)
# or
composer require a-gautrot/propel-bundle "5.2.x-dev"
```

#### composer.json

Stable release:

```json
{
  "require": {
    "propel/propel": "2.0.0-beta4",
    "a-gautrot/propel-bundle": "~5.2"
  }
}
```

Nightly release:

```json
{
  "require": {
    "propel/propel": "2.0.0-beta4",
    "a-gautrot/propel-bundle": "5.2.x-dev"
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
* Integration to [Symfony Profiler](https://symfony.com/doc/5.x/profiler.html);
* Load SQL, YAML, and XML fixtures;
* Create and drop databases;
* Integration with the [Form component](https://symfony.com/doc/5.x/forms.html);
* Integration with the [Security component](https://symfony.com/doc/5.x/security.html);
* Propel's ParamConverter can be used
  with [Sensio Framework Extra Bundle](https://github.com/sensiolabs/SensioFrameworkExtraBundle).

### Deprecated

* XmlFileLoader has been [deprecated](https://symfony.com/blog/new-in-symfony-7-4-deprecated-xml-configuration) since
  Symfony **7.4**. If you are using XML schemas, you must convert them to YAML schemas.
* Sensio Framework Extra Bundle, has been deprecated since Symfony **6.2**. If you are still using it, you must convert
  them to [Symfony Attributes](https://symfony.com/doc/6.4/reference/attributes.html).

## New to version 5.2

* Removed support for Symfony 5.0 to 5.3
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 7.2 and 7.3
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to latest beta release (2.0.0-beta4)

You can also read the changelogs [here](CHANGELOG.md).

## And more...

You can read and learn Propel 2 from their official documentation [here](https://propelorm.org/documentation/). For
licenses, see: [LICENSE](LICENSE).
