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

## Rules

- `declare(strict_types=1)`, `final` classes and explicit types. PHPStan runs at the maximum level with no ignored error.
- The coverage of `src/` stays at 100 %.
- The code works on every PHP version the package supports (8.2 and more): no feature of a later version.
- **Never edit `data/` by hand.** It comes from a release of the [builder][builder] with `make data`. A wrong value in the data is reported and fixed there.
- The package does not require a framework: the Laravel and Symfony adapters load only when the application uses them.

[builder]: https://github.com/Stanislas-Poisson/French-Postal-Code
[conventional-commits]: https://www.conventionalcommits.org/
