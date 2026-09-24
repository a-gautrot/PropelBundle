# CHANGELOG

---

<!-- TOC -->

* [2026-09-23](#release-2026-09-23)
* [2026-09-22](#release-2026-09-22)
* [2026-09-12](#release-2026-09-12)
* [2026-09-11](#release-2026-09-11)

<!-- TOC -->

---

## Release 2026-09-23

### General

* Updated `composer.json`
* Removed more deprecated content

## Release 2026-09-22

### General

* Trimmed the release branches by removing the `misc` folder
    * Dockerfiles are now only available in the `main` and `dev` branches
* Removed more deprecated content

## Release 2026-09-12

### General

* Added 8.0 branch for Symfony 8.4 in November 2027
    * Currently in development, therefore unstable, and runs on Symfony 8.1
* Re-added SkyFoxvn as part of the authors
* Fixed a potential SQL injection in AbstractDataDumper
  ([AIKIDO](https://app.aikido.dev/issues/45323423/detail?status=closed))
* Fixed font color in Symfony Profiler

### 5.2.0.1

* Fixed Dockerfiles

## Release 2026-09-11

### General

* Added Docker and codecov supports
* Updated `composer.json` for security patches

### 7.1.0

* Added support for Symfony 7.4
    * Removed support for Symfony 6 and 7.0 to 7.3
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 8.0 and 8.1
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to its official stable release (2.0.0)

### 6.1.0

* Removed support for Symfony 6.0 to 6.3
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 8.0
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to its official stable release (2.0.0)

### 5.2.0

* Removed support for Symfony 5.0 to 5.3
* Added support for PHP 8.4 and 8.5
    * Removed support for PHP 7.2 and 7.3
    * Removed deprecated content introduced in PHP 8.4 and 8.5
* Updated `propel/propel` minimum version to its last beta release (2.0.0-beta4)
