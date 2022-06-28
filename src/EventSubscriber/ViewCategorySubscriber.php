<?php

declare(strict_types=1);

namespace Setono\SyliusFacebookPlugin\EventSubscriber;

use Psr\EventDispatcher\EventDispatcherInterface;
use Setono\MetaConversionsApiBundle\Event\ConversionApiEventRaised;
use Setono\SyliusFacebookPlugin\Event\CategoryViewedEvent;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Bundle\ResourceBundle\Grid\View\ResourceGridView;
use Sylius\Component\Locale\Context\LocaleContextInterface;
use Sylius\Component\Product\Model\ProductInterface;
use Sylius\Component\Taxonomy\Model\TaxonInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Traversable;

/**
 * See https://developers.facebook.com/docs/marketing-api/audiences/guides/dynamic-product-audiences/#setuppixel
 * for reference of the 'ViewCategory' custom event
 */
final class ViewCategorySubscriber implements EventSubscriberInterface
{
    private LocaleContextInterface $localeContext;

    private TaxonRepositoryInterface $taxonRepository;

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        LocaleContextInterface $localeContext,
        TaxonRepositoryInterface $taxonRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->localeContext = $localeContext;
        $this->taxonRepository = $taxonRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.product.index' => 'track',
        ];
    }

    public function track(ResourceControllerEvent $event): void
    {
        $gridView = $event->getSubject();
        if (!$gridView instanceof ResourceGridView) {
            return;
        }

        $taxon = $this->getTaxon($gridView);
        if (null === $taxon) {
            return;
        }

        $this->eventDispatcher->dispatch(new ConversionApiEventRaised(new CategoryViewedEvent(
            $taxon,
            $this->getProducts($gridView),
        )));
    }

    /**
     * @return list<string>
     */
    private function getProducts(ResourceGridView $gridView): array
    {
        $data = $gridView->getData();
        if (!$data instanceof Traversable) {
            return [];
        }

        $codes = [];

        $i = 0;
        $max = 10;

        /** @var mixed $datum */
        foreach ($data as $datum) {
            if ($i >= $max) {
                break;
            }

            if ($datum instanceof ProductInterface) {
                $code = $datum->getCode();
                if (null !== $code) {
                    $codes[] = $code;
                }
            }

            ++$i;
        }

        return $codes;
    }

    private function getTaxon(ResourceGridView $gridView): ?TaxonInterface
    {
        $request = $gridView->getRequestConfiguration()->getRequest();

        $slug = $request->attributes->get('slug');
        if (!is_string($slug)) {
            return null;
        }

        $locale = $this->localeContext->getLocaleCode();

        return $this->taxonRepository->findOneBySlug($slug, $locale);
    }
}
