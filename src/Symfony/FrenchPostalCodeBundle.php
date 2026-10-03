<?php

declare(strict_types=1);

namespace StanislasPoisson\FrenchPostalCode\Symfony;

use StanislasPoisson\FrenchPostalCode\Core\Dataset;
use StanislasPoisson\FrenchPostalCode\Symfony\Command\LoadCommand;
use StanislasPoisson\FrenchPostalCode\Symfony\Doctrine\DbalRowWriter;
use StanislasPoisson\FrenchPostalCode\Symfony\Doctrine\TablePrefixListener;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\CityRepository;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\CommuneRepository;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\DepartmentRepository;
use StanislasPoisson\FrenchPostalCode\Symfony\Repository\RegionRepository;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

/**
 * The regions, departments, communes and postal codes of France, as Doctrine entities and a command that loads them.
 *
 * The entities are mapped in the default entity manager, on the default connection.
 */
final class FrenchPostalCodeBundle extends AbstractBundle
{
    protected string $extensionAlias = 'french_postal_code';

    public function configure(DefinitionConfigurator $definitionConfigurator): void
    {
        $definitionConfigurator->rootNode()
            ->children()
            ->scalarNode('table_prefix')
            ->defaultValue('french_')
            ->info(
                'Prefix of the tables of the package. Many applications already own a "cities" or a "regions" table. '
                . 'An empty value gives "regions", "cities"...',
            )
            ->end()
            ->end();
    }

    /**
     * @param array{table_prefix: string} $config
     */
    public function loadExtension(
        array $config,
        ContainerConfigurator $containerConfigurator,
        ContainerBuilder $containerBuilder,
    ): void {
        $services = $containerConfigurator->services();

        $services->set('french_postal_code.dataset', Dataset::class)->public();
        $services->alias(Dataset::class, 'french_postal_code.dataset');

        $services->set('french_postal_code.row_writer', DbalRowWriter::class)
            ->args([service('doctrine.dbal.default_connection'), $config['table_prefix']])
            ->public();

        $services->set('french_postal_code.table_prefix_listener', TablePrefixListener::class)
            ->args([$config['table_prefix']])
            ->tag('doctrine.event_listener', ['event' => 'loadClassMetadata', 'method' => 'loadClassMetadata']);

        $services->set(LoadCommand::class)
            ->args([service('french_postal_code.dataset'), service('french_postal_code.row_writer')])
            ->tag('console.command');

        $repositories = [
            RegionRepository::class,
            DepartmentRepository::class,
            CommuneRepository::class,
            CityRepository::class,
        ];

        foreach ($repositories as $repository) {
            $services->set($repository)->args([service('doctrine')])->tag('doctrine.repository_service');
        }
    }

    public function prependExtension(
        ContainerConfigurator $containerConfigurator,
        ContainerBuilder $containerBuilder,
    ): void {
        $containerConfigurator->extension('doctrine', [
            'orm' => [
                'mappings' => [
                    'FrenchPostalCode' => [
                        'type'      => 'attribute',
                        'is_bundle' => false,
                        'dir'       => __DIR__ . '/Entity',
                        'prefix'    => TablePrefixListener::namespace(),
                        'alias'     => 'FrenchPostalCode',
                    ],
                ],
            ],
        ]);
    }
}
