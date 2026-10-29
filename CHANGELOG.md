# CHANGELOG

---

<!-- TOC -->

* [6.1.0](#610)
    * [6.1.0.1](#6101)
    * [6.1.0.2](#6102)
    * [6.1.0.3](#6103)
    * [6.1.0.4](#6104)

<!-- TOC -->

---

## 6.1.0

* Removed support for Symfony 6.0 to 6.3
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 8.0
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to its official stable release (2.0.0)

### 6.1.0.1

* Fixed a potential SQL injection in AbstractDataDumper
  ([AIKIDO](https://app.aikido.dev/issues/45323423/detail?status=closed))
* Fixed font color in Symfony Profiler

### 6.1.0.2

* Trimmed the package
    * Removed misc. content
* Removed more deprecated content

### 6.1.0.3

* Updated `composer.json`
* Removed more deprecated content

### 6.1.0.4

* Updated the project's source code
  * Updated `composer.json`
  * Optimized the lib with gitattributes
* Added deprecation warnings for XML schemas
