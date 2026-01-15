<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $containerConfigurator->extension('doctrine', [
        'orm' => [
            'mappings' => [
                'App' => [
                    'type' => 'attribute',
                    'prefix' => 'App\Entity\\',
                    'dir' => __DIR__ . '/../../tests/Fixtures/App/Entity',
                    'is_bundle' => false,
                ],
            ],
        ],
    ]);
};
