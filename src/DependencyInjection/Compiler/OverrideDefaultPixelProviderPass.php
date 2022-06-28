<?php

declare(strict_types=1);

namespace Setono\SyliusFacebookPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OverrideDefaultPixelProviderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('setono_sylius_facebook.provider.doctrine_based_pixel_provider')) {
            return;
        }

        $container->setAlias(
            'setono_meta_conversions_api.pixel_provider.default',
            'setono_sylius_facebook.provider.doctrine_based_pixel_provider'
        );
    }
}
