<?php

declare(strict_types=1);

namespace Setono\SyliusFacebookPlugin\EventSubscriber;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\MainRequestTrait\MainRequestTrait;
use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Setono\SyliusFacebookPlugin\Event\OrderPlacedEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class PurchaseSubscriber implements EventSubscriberInterface
{
    use MainRequestTrait;

    private OrderRepositoryInterface $orderRepository;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->orderRepository = $orderRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'track',
        ];
    }

    public function track(RequestEvent $event): void
    {
        if (!$this->isMainRequest($event)) {
            return;
        }

        $request = $event->getRequest();

        if ($request->attributes->get('_route') !== 'sylius_shop_order_thank_you') {
            return;
        }

        $order = $this->resolveOrder($request);
        if (null === $order) {
            return;
        }

        $this->eventDispatcher->dispatch(new ConversionApiEventRaised(new OrderPlacedEvent($order)));
    }

    /**
     * This method will return an OrderInterface if
     * - A session exists with the order id
     * - The order can be found in the order repository
     */
    private function resolveOrder(Request $request): ?OrderInterface
    {
        $orderId = $request->getSession()->get('sylius_order_id');

        if (!is_scalar($orderId)) {
            return null;
        }

        $order = $this->orderRepository->find($orderId);
        if (!$order instanceof OrderInterface) {
            return null;
        }

        return $order;
    }
}
