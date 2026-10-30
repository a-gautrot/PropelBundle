# CHANGELOG

---

<!-- TOC -->

* [5.2.1](#521)
* [5.2.0](#520)
    * [5.2.0.1](#5201)
    * [5.2.0.2](#5202)
    * [5.2.0.3](#5203)

<!-- TOC -->

---

## 5.2.0

* Removed support for Symfony 5.0 to 5.3
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 7.2 and 7.3
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to latest beta release (2.0.0-beta4)

### 5.2.0.1

* Fixed a potential SQL injection in AbstractDataDumper
  ([AIKIDO](https://app.aikido.dev/issues/45323423/detail?status=closed))
* Fixed font color in Symfony Profiler
* Fixed Dockerfiles

### 5.2.0.2

* Trimmed the package
    * Removed misc. content
* Removed more deprecated content

### 5.2.0.3

* Updated `composer.json`
* Removed more deprecated content

## 5.2.1

* Updated `composer.json`
* Updated the project's source code
    * Trimmed the package with gitattributes
    * Updated with support from Rector, PHPStan, Psalm, and PHPCS
* Added a way to migrate from Sensio to Symfony
* Added deprecation warnings for XML schemas
* Fixed fixture path validation, SQL result handling, and static type declarations
* Updated unit tests
    * Now uses Docker for some UTs
    * Fixed UTs for its Book schema
