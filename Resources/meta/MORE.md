# More info

---

## Branching model

Since Propel 2 was officially released on June 23, 2026, we are migrating the branching model of this bundle in advance!

### Maintained branches

The following branches are maintained:

* `5.2`: contains Propel **2** (branch **2.0.0-beta4**) integration for Symfony **5.4** and PHP **7.4+**;
* `6.1`: contains Propel **2** integration for Symfony **6.4** and PHP **8.1+**;
* `7.1`: contains Propel **2** integration for Symfony **7.4** and PHP **8.2+**;
* `8.0`: will contain Propel **2** integration for Symfony **8.4** and PHP **8.4+**.
    * Currently, it supports Symfony 8.1 and is in development state, which means it is unstable.

### Unmaintained branches

The following branches are no longer maintained, but are still available here for archive purpose:

#### Propel 1.x (legacy)

* `1.0`: contains Propel **1.6** integration for Symfony **2.0**;
    * `1.1`: contains Propel **1.6** integration for Symfony **2.1**;
    * `1.2`: contains Propel **1.6** integration for Symfony **2.2**.

#### Propel 2

* `2.0`: contains Propel **2** (branches **below 2.0.0-beta1**) integration for Symfony **2.5 - 2.8**;
* `3.0`: contains Propel **2** (branches **below 2.0.0-beta1**) integration for Symfony **2.8 - 3.x**;
* `4.0`: contains Propel **2** (branches **below 2.0.0-beta1**) integration for Symfony **3.4 - 4.x**;
* `5.0`: contains Propel **2** (branch **2.0.0-beta1**) integration for Symfony **4.x - 5.x** and PHP **7.2 - 8.0**;
    * `5.1`: contains Propel **2** (branch **2.0.0-beta2**) integration for Symfony **4.x - 6.x** and PHP **7.4 - 8.1**;
* `6.0`: contains Propel **2** (branch **2.0.0-beta2**) integration for Symfony **6.x** and PHP **8.0.2+**;
* `7.0`: contains Propel **2** (branches **2.0.0-beta2 - 2.0.0-beta4**) integration for Symfony **6.x - 7.x** and PHP
  **8.0.2+**.

## Docker

PropelBundle can be tested in Docker. See [docker-compose.yaml](docker/docker-compose.yaml) for more details about it.
It currently has images for Debian, Ubuntu, and Alpine Linux, and their goal is to run unit tests.
