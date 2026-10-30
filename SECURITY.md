# Security Policy

## Supported versions

| Version | Supported          | Notes                                                                    |
|---------|--------------------|--------------------------------------------------------------------------|
| 8.0.x   | :wrench:           | In dev. state, will be stable when Symfony 8.4 releases in November 2027 |
| 7.1.x   | :white_check_mark: | Will be maintained until November 2029                                   |
| 7.0.x   | :x:                | No longer maintained, kept for archive purpose                           |
| 6.1.x   | :white_check_mark: | LTS version, will be maintained until November 2027                      |
| 6.0.x   | :x:                | No longer maintained, kept for archive purpose                           |
| 5.2.x   | :white_check_mark: | Extended LTS version, will be maintained until February 2029             |
| < 5.2   | :x:                | No longer maintained, kept for archive purpose                           |

## Roadmap

As described in the table above, a couple of branches will be supported until their respective deadlines:

* `5.2` will be supported until **February 2029**, per [Symfony 5.4](https://symfony.com/releases/5.4);
* `6.1` will be supported until **November 2027**, per [Symfony 6.4](https://symfony.com/releases/6.4);
* `7.1` will be supported until **November 2029**, per [Symfony 7.4](https://symfony.com/releases/7.4).

[Symfony 8.4](https://symfony.com/releases/8.4) will be ready for November 2027, therefore there are no deadline for it
currently. However, there is a
branch `8.0` for anticipating its support.

The project works with a pseudo-semantic version system (`w.x.y.z`), this is how it goes:

* `w`: Which Symfony version it is based of (ex: `5.x.y.z` for Symfony 5.4);
* `x`: The major version, will increment for each major or minor release of Propel (ex: `w.2.y.z` for Propel ~2.0);
* `y`: The minor version, will increment for each new feature or major refactor;
* `z`: The patch version, will increment for each new smaller scale features, security patches, and bug fixes.
