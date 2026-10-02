# Security Policy

## Reporting vulnerabilities

| Channel | Description | Contact / Link |
| :--- | :--- | :--- |
| **Private report** | Preferred channel for sensitive reports. | [Report a vulnerability][advisories] |
| **Issues** | Non-sensitive problems. | [Open an issue][issues] |
| **Email** | Alternative contact. | `security@the-white-rabbits.fr` |

Please **do not disclose a vulnerability publicly** until it has been reviewed and fixed.

## Scope

- The package is a library: it has no web server, no authentication and no user account.
- It reads the files of its `data/` directory and writes to the database of the application that loads them. It makes no network call and stores no secret.
- The data holds no personal data: only administrative areas, postal codes and GPS points.
- `scripts/update-data.sh` downloads a release of the builder over HTTPS and checks its SHA-256 checksum before replacing any file. It is a development tool, not part of what an application installs.

[advisories]: https://github.com/Stanislas-Poisson/French-Postal-Code-Package/security/advisories/new
[issues]: https://github.com/Stanislas-Poisson/French-Postal-Code-Package/issues
