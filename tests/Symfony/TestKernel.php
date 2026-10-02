<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Tests\Symfony;

use Composer\InstalledVersions;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use StanislasPoisson\FrenchPostalCode\Symfony\FrenchPostalCodeBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;

/**
 * The smallest application that uses the bundle: Doctrine on SQLite in memory.
 */
final class TestKernel extends Kernel
{
    use MicroKernelTrait;

    public function __construct(private readonly string $prefix = 'french_')
    {
        parent::__construct('test', true);
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir() . '/french-postal-code-symfony/' . md5($this->prefix) . '/cache';
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir() . '/french-postal-code-symfony/' . md5($this->prefix) . '/log';
    }

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle;

        yield new DoctrineBundle;

        yield new FrenchPostalCodeBundle;
    }

    protected function configureContainer(ContainerConfigurator $containerConfigurator): void
    {
        $containerConfigurator->extension('framework', ['test' => true, 'secret' => 'test']);

        $orm = ['auto_mapping' => false];

        // DoctrineBundle 2 still asks for these two options with Doctrine ORM 3; DoctrineBundle 3 no longer knows them.
        if (version_compare((string) InstalledVersions::getPrettyVersion('doctrine/doctrine-bundle'), '3.0', '<')) {
            $orm += ['report_fields_where_declared' => true, 'enable_lazy_ghost_objects' => true];
        }

        $containerConfigurator->extension('doctrine', [
            'dbal' => ['driver' => 'pdo_sqlite', 'memory' => true],
            'orm'  => $orm,
        ]);

        $containerConfigurator->extension('french_postal_code', ['table_prefix' => $this->prefix]);
    }
}
