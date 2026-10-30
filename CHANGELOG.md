# CHANGELOG

---

<!-- TOC -->

* [7.1.1](#711)
* [7.1.0](#710)
    * [7.1.0.1](#7101)
    * [7.1.0.2](#7102)
    * [7.1.0.3](#7103)

<!-- TOC -->

---

## 7.1.0

* Added support for Symfony 7.4
    * Removed support for Symfony 6 and 7.0 to 7.3
        * LTS for Symfony 6.4 was migrated to a new branch: [6.1](https://github.com/Gautrot/PropelBundle/tree/6.1)
        * Extended LTS for Symfony 5.4 was migrated to a new
          branch: [5.2](https://github.com/Gautrot/PropelBundle/tree/5.2)
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 8.0 and 8.1
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to its official stable release (2.0.0)

### 7.1.0.1

* Fixed a potential SQL injection in AbstractDataDumper
  ([AIKIDO](https://app.aikido.dev/issues/45323423/detail?status=closed))
* Fixed font color in Symfony Profiler

### 7.1.0.2

* Trimmed the package
    * Removed misc. content
* Removed more deprecated content

### 7.1.0.3

* Updated `composer.json`
* Removed more deprecated content

## 7.1.1

* Updated `composer.json`
* Updated the project's source code
    * Trimmed the package with gitattributes
    * Updated with support from PHPStan, Psalm, and PHPCS
* Updated unit tests
    * Now uses Docker for some UTs
    * Fixed UTs for its Book schema
* Fixed fixture path validation, SQL result handling, and static type declarations
