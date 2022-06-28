<?php

declare(strict_types=1);

namespace Setono\SyliusFacebookPlugin\EventSubscriber;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Setono\SyliusFacebookPlugin\Event\ProductViewedEvent;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\ProductInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Webmozart\Assert\Assert;

final class ViewProductSubscriber implements EventSubscriberInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product.show' => 'track',
        ];
    }

    public function track(ResourceControllerEvent $event): void
    {
        $product = $event->getSubject();
        Assert::isInstanceOf($product, ProductInterface::class);

        $this->eventDispatcher->dispatch(new ConversionApiEventRaised(new ProductViewedEvent($product)));
    }
}
