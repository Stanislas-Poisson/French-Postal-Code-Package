# Contributing

Thank you for helping. The sections below describe the workflow of this repository.

## Development workflow

| Step | Command / Action | Description |
| :--- | :--- | :--- |
| **1. Install** | `make install` | Install the dependencies. |
| **2. Branch** | `git checkout -b feature/#TICKET-name develop` | Create a branch from `develop`. |
| **3. Code** | *(your IDE)* | Write the change and its tests. |
| **4. Quality** | `make quality` | Check the code style and run the static analysis. |
| **5. Test** | `make test` | Make sure all the tests pass. |
| **6. Commit** | `git commit -m "type(scope): #TICKET subject"` | Use the [Conventional Commits][conventional-commits] format, in English, 72 characters at most. |
| **7. Push** | `git push origin feature/#TICKET-name` | Push and open a pull request to `develop`. |

A pull request needs a review and a green `ci` check, and is merged with a merge commit.

## Testing an adapter

The frameworks are not in `composer.json`: Laravel and Symfony do not share compatible versions of their components, so each CI job installs only its own. To test the Laravel adapter against one major version of Laravel:

```bash
scripts/install-laravel.sh 12      # 11, 12 or 13
composer test:laravel
composer analyse:laravel
git checkout composer.json && composer install    # back to the plain package
```

The Symfony adapter works the same way, with `scripts/install-symfony.sh 6.4|7.4|8.0`, `composer test:symfony` and `composer analyse:symfony`. Symfony 8 needs PHP 8.4. Do not install both frameworks in the same `vendor`.

The adapter tests run on SQLite in memory. To run them on a server, as the `full-data` job of the CI does, start it and tell the tests where it is:

```bash
docker run -d -p 33306:3306 -e MYSQL_ROOT_PASSWORD=secret -e MYSQL_DATABASE=french_postal_code mysql:8.4
TEST_DB=mysql TEST_DB_PORT=33306 TEST_DB_PASSWORD=secret composer test:laravel
```

`TEST_DB` is `sqlite` (the default), `mysql`, `mariadb` or `pgsql`. `TEST_DB_HOST`, `TEST_DB_PORT`, `TEST_DB_NAME`, `TEST_DB_USER` and `TEST_DB_PASSWORD` tell where the server is. The tests wipe that database: do not point them at one you care about.

## Releasing

Reserved to the maintainer. Tags are plain `X.Y.Z`, signed, and made on `main` only.

1. Merge `develop` into `main` with a pull request, and wait for the CI of `main`.
2. Tag the merge commit and push the tag:

   ```bash
   git checkout main && git pull
   git tag -s 4.0.0 -m "4.0.0"
   git push origin 4.0.0
   ```

3. The `Release` workflow checks that the tag is on `main` and that the CI passed on that commit, then creates the GitHub release. Its notes list the merged pull requests by label (`enhancement`, `bug`, `documentation`, `dependencies`) and give the `composer require` line.
4. Packagist reads the new tag by itself, once the package is submitted and its GitHub hook is active. The last step of the workflow warns when it does not list the version.

## Rules

- `declare(strict_types=1)`, `final` classes and explicit types. PHPStan runs at the maximum level with no ignored error.
- The coverage of `src/` stays at 100 %.
- The code works on every PHP version the package supports (8.2 and more): no feature of a later version.
- **Never edit `data/` by hand.** It comes from a release of the [builder][builder] with `make data`. A wrong value in the data is reported and fixed there.
- The package does not require a framework: the Laravel and Symfony adapters load only when the application uses them.

[builder]: https://github.com/Stanislas-Poisson/French-Postal-Code
[conventional-commits]: https://www.conventionalcommits.org/
