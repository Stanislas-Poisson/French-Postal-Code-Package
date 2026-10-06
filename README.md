<div align="center">

# French Postal Code

**The regions, departments, communes and postal codes of France, with one GPS point per postal code, in your own database.**

[![Packagist][badge-packagist]][packagist]
[![CI][badge-ci]][ci]
[![License: MIT][badge-license]][license]
[![PHP 8.3+][badge-php]][composer]

</div>

---

A Composer package that gives an application the French administrative areas and postal codes as **data**, **models with their relations** and a **command that loads them** into its database. It is built to work with **Laravel** (Eloquent) and **Symfony** (Doctrine), and its core needs no framework.

The data comes from the [French-Postal-Code][builder] project, which builds it from the official open sources (INSEE, La Poste, the Base Adresse Nationale) and publishes it on [data.gouv.fr][data-gouv].

## Documentation

The documentation site is <https://stanislas-poisson.github.io/French-Postal-Code-Package/>: this guide and the reference of every class, read from the source, for each released version (selector at the top right, `next` is `main`). Build it with `cd docs && npm ci && npm run dev`.

## Why a package

An application that stores addresses wants to point each one to a stable postal entry, such as "37200 Tours", with a foreign key. This package keeps the **original identifiers** of the dataset when it loads the rows, and never deletes a row: a closed row has a `valid_to` date, and a replaced city points to its successor. The foreign keys of your application therefore stay valid from one version to the next.

## What the data holds

| Table | Content |
| :--- | :--- |
| `regions` | The regions. |
| `departments` | The departments and the overseas collectivities. |
| `communes` | The communes and the municipal arrondissements, closed ones included. |
| `cities` | **One row per commune and per postal code**, with its GPS point. The table to reference by a foreign key. |
| `commune_successions` | For an old INSEE code, the code that follows it, the kind of change and its date. |

Every table has `valid_from` and `valid_to` columns. The files are in [`data/`](data), described by a `manifest.json` and by a [Table Schema](schemas) per file.

## Reading the data without a framework

```php
use StanislasPoisson\FrenchPostalCode\Core\Dataset;

$dataset = new Dataset();

$dataset->count('cities');                  // 35510
$dataset->columns('cities');                // ['id', 'commune_id', 'postal_code', ...]

foreach ($dataset->chunks('cities', 1000) as $rows) {
    // $rows is a list of arrays indexed by column name, an empty value is null
}
```

The reader streams the files and checks them against the manifest: a truncated or altered file is an error, never a silent partial load.

## Laravel

```bash
composer require stanislas-poisson/french-postal-code
php artisan migrate
php artisan french-postal-code:load
```

`migrate` creates the tables, `french-postal-code:load` fills them. The command can be run again at any time: it adds the new rows, updates the others and deletes nothing, so it is also how you take a new version of the data.

The models are in `StanislasPoisson\FrenchPostalCode\Laravel\Models`: `Region`, `Department`, `Commune`, `City` and `CommuneSuccession`, with their relations.

```php
use StanislasPoisson\FrenchPostalCode\Laravel\Models\City;

$city = City::query()->where('postal_code', '37200')->firstOrFail();

$city->commune->name;                        // Tours
$city->commune->department->region->name;    // Centre-Val de Loire
City::query()->current()->count();           // only the rows that are valid today
```

An application points to a postal entry with a foreign key to `french_cities`:

```php
// In a migration of your application
$table->foreignId('city_id')->constrained('french_cities');

// In your model
public function city(): BelongsTo
{
    return $this->belongsTo(City::class);
}
```

### Configuration

Many applications already own a `cities` or a `regions` table, so the tables of the package are prefixed. Both settings can be set in the environment, or in the configuration file published with `php artisan vendor:publish --tag=french-postal-code-config`:

| Setting | Environment variable | Default |
| :--- | :--- | :--- |
| `table_prefix` | `FRENCH_POSTAL_CODE_TABLE_PREFIX` | `french_` (an empty value gives `regions`, `cities`...) |
| `connection` | `FRENCH_POSTAL_CODE_CONNECTION` | the default connection |

Set them **before** running `php artisan migrate`.

### Compatibility

| | Supported |
| :--- | :--- |
| PHP | 8.3, 8.4 |
| Laravel | 11, 12 and 13 |
| Databases | Those Laravel supports, with the foreign keys enforced |

Laravel 11 no longer receives security fixes, so Composer refuses to install it unless you accept its advisories; the adapter is tested on it nonetheless.

## Symfony

```bash
composer require stanislas-poisson/french-postal-code
```

Register the bundle in `config/bundles.php`:

```php
StanislasPoisson\FrenchPostalCode\Symfony\FrenchPostalCodeBundle::class => ['all' => true],
```

Create the tables with your usual Doctrine tooling, then load the data:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
php bin/console french-postal-code:load
```

The entities are mapped for you, so the migration your application generates contains the five tables, with their foreign keys. `french-postal-code:load` can be run again at any time: it adds the new rows, updates the others and deletes nothing, so it is also how you take a new version of the data.

The entities are in `StanislasPoisson\FrenchPostalCode\Symfony\Entity`: `Region`, `Department`, `Commune`, `City` and `CommuneSuccession`, with their relations and read-only accessors. The repositories add `current()`, a query builder on the rows that are valid today.

```php
use StanislasPoisson\FrenchPostalCode\Symfony\Entity\City;

$city = $entityManager->getRepository(City::class)->findOneBy(['postalCode' => '37200']);

$city->getCommune()->getName();                                // Tours
$city->getCommune()->getDepartment()?->getRegion()?->getName(); // Centre-Val de Loire
```

An application points to a postal entry with a relation to `City`:

```php
#[ORM\ManyToOne(targetEntity: City::class)]
#[ORM\JoinColumn(nullable: false)]
private City $city;
```

### Configuration

```yaml
# config/packages/french_postal_code.yaml
french_postal_code:
    table_prefix: french_   # an empty value gives "regions", "cities"...
```

Many applications already own a `cities` or a `regions` table, so the tables of the package are prefixed. Set it **before** generating the migration. The entities live in the **default entity manager**, on its connection.

### Compatibility

| | Supported |
| :--- | :--- |
| PHP | 8.3, 8.4 |
| Symfony | 6.4 and 7.4, 8.0 (PHP 8.4) |
| Doctrine | ORM 3 with DBAL 4, and DoctrineBundle 2 (Symfony 6.4 and 7) or 3 (Symfony 8) |
| Databases | MySQL, MariaDB, PostgreSQL and SQLite |

Below PHP 8.4, Doctrine ORM needs `symfony/var-exporter` to load a relation on demand; a Symfony application with Doctrine has it already.

## Keeping a reference up to date

A row is never deleted. When a commune merges or a postal code changes, the old `City` is closed (`valid_to` is set) and points to the one that replaces it, so your foreign key keeps working and always leads to a row that exists. After loading a new version of the data, you can move the references that lead to a closed city.

Laravel:

```php
// In your model: the closed city knows its replacement
$address->city->replacedBy;   // City or null

// Move every address that leads to a closed city to its replacement
Address::query()
    ->whereIn('city_id', City::query()->whereNotNull('replaced_by_city_id')->select('id'))
    ->update(['city_id' => City::query()->select('replaced_by_city_id')->whereColumn('french_cities.id', 'addresses.city_id')]);
```

Symfony:

```php
$address->getCity()->getReplacedBy();   // City or null

// The same move, in SQL: adapt the names of your table and of the tables of the package (prefix included)
$entityManager->getConnection()->executeStatement(
    'UPDATE addresses SET city_id = (SELECT c.replaced_by_city_id FROM french_cities c WHERE c.id = addresses.city_id)
     WHERE city_id IN (SELECT id FROM french_cities WHERE replaced_by_city_id IS NOT NULL)'
);
```

A replacement can itself be replaced later: run the update again until it changes nothing, or follow `replacedBy` in a loop. For the old INSEE codes of a commune, `commune_successions` gives the commune or communes that replace it, with the date and the kind of change.

## Updating the data

The files of `data/` are never edited by hand. They are the package archive of a release of the builder:

```bash
make data                  # the latest release
make data VERSION=4.0.0    # a given release
```

A workflow (`Update data`) does it for you every Monday, and by hand from the Actions tab: when the
latest release of the builder changes `data/`, it opens a pull request with the new figures. Review and
merge it, then tag and publish as usual: that part stays manual. The same thing runs locally with
`scripts/propose-data-update.sh --dry-run`.

The archive is checked against the `SHA256SUMS` file of the release before anything is replaced.

## Versions

The version of the package follows [SemVer][semver]. A new dataset without change of layout is a minor release, a change of the layout of the files is a major release, and a fix is a patch. The first release is `4.0.0`, to follow the dataset it holds.

## Development

```bash
make install
make test        # PHPUnit: the reader and the loader (see CONTRIBUTING.md for the adapters)
make quality     # Pint, PHPStan, Rector, PHP Insights and the tests
```

See [`CONTRIBUTING.md`][contributing], [`SECURITY.md`][security] and the [code of conduct][conduct].

## Licence

[MIT][license] for the code. The data stays subject to the licences of its sources, see the [builder][builder].

[badge-packagist]: https://img.shields.io/packagist/v/stanislas-poisson/french-postal-code?style=flat-square&logo=packagist&logoColor=white&label=packagist
[badge-ci]: https://img.shields.io/github/actions/workflow/status/Stanislas-Poisson/French-Postal-Code-Package/ci.yml?branch=main&label=ci&style=flat-square&logo=githubactions&logoColor=white
[badge-license]: https://img.shields.io/badge/license-MIT-2ea44f?style=flat-square
[badge-php]: https://img.shields.io/badge/php-8.3%2B-777BB4?style=flat-square&logo=php&logoColor=white
[builder]: https://github.com/Stanislas-Poisson/French-Postal-Code
[packagist]: https://packagist.org/packages/stanislas-poisson/french-postal-code
[ci]: https://github.com/Stanislas-Poisson/French-Postal-Code-Package/actions/workflows/ci.yml
[composer]: composer.json
[conduct]: CODE_OF_CONDUCT.md
[contributing]: CONTRIBUTING.md
[data-gouv]: https://www.data.gouv.fr/datasets/regions-departements-communes-et-codes-postaux-de-france-avec-un-point-gps-par-code-postal-et-lhistorique-des-changements
[license]: LICENSE
[security]: SECURITY.md
[semver]: https://semver.org/
