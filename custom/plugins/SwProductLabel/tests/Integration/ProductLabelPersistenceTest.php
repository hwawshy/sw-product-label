<?php

declare(strict_types=1);

namespace SwProductLabel\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Locale\LocaleCollection;
use Shopware\Core\System\Tax\TaxCollection;
use SwProductLabel\Content\ProductLabel\ProductLabelCollection;

/**
 * Verifies the product_label entity can be written and read through the DAL,
 * including translations and the many-to-many assignment to products.
 *
 * Each test runs inside a database transaction that is rolled back on teardown
 * (DatabaseTransactionBehaviour), so data created by a test never persists.
 */
class ProductLabelPersistenceTest extends TestCase
{
    use IntegrationTestBehaviour;

    public function testLabelCanBeWrittenAndReadWithTranslations(): void
    {
        $context = Context::createDefaultContext();
        $labelRepository = $this->getLabelRepository();
        $deLanguageId = $this->createDeLanguage();

        $labelId = Uuid::randomHex();
        $labelRepository->create([
            [
                'id' => $labelId,
                'color' => '#FF4B4B',
                'priority' => 10,
                'active' => true,
                'validFrom' => (new \DateTimeImmutable())->modify('-1 day'),
                'translations' => [
                    Defaults::LANGUAGE_SYSTEM => ['name' => 'Sale'],
                    $deLanguageId => ['name' => 'Angebot'],
                ],
            ],
        ], $context);

        $label = $labelRepository->search(new Criteria([$labelId]), $context)->getEntities()->get($labelId);
        static::assertNotNull($label);
        static::assertSame('Sale', $label->getName());
        static::assertSame('#FF4B4B', $label->getColor());
        static::assertSame(10, $label->getPriority());
        static::assertTrue($label->getActive());
        static::assertInstanceOf(\DateTimeInterface::class, $label->getValidFrom());

        $deContext = new Context(
            new SystemSource(),
            [],
            Defaults::CURRENCY,
            [$deLanguageId, Defaults::LANGUAGE_SYSTEM],
        );
        $translatedLabel = $labelRepository->search(new Criteria([$labelId]), $deContext)->getEntities()->get($labelId);
        static::assertNotNull($translatedLabel);
        static::assertSame('Angebot', $translatedLabel->getName());
    }

    public function testLabelCanBeAssignedToProduct(): void
    {
        $context = Context::createDefaultContext();
        $labelRepository = $this->getLabelRepository();
        $productRepository = $this->getProductRepository();

        $labelId = Uuid::randomHex();
        $labelRepository->create([[
            'id' => $labelId,
            'color' => '#4B7BFF',
            'priority' => 5,
            'active' => true,
            'translations' => [Defaults::LANGUAGE_SYSTEM => ['name' => 'New']],
        ]], $context);

        $productId = Uuid::randomHex();
        $productRepository->create([
            [
                'id' => $productId,
                'name' => 'Test product',
                'productNumber' => Uuid::randomHex(),
                'stock' => 10,
                'taxId' => $this->getTaxId(),
                'price' => [['currencyId' => Defaults::CURRENCY, 'gross' => 10.0, 'net' => 9.0, 'linked' => false]],
                'productLabels' => [['id' => $labelId]],
            ],
        ], $context);

        $criteria = (new Criteria([$productId]))->addAssociation('productLabels');
        $product = $productRepository->search($criteria, $context)->getEntities()->get($productId);
        static::assertNotNull($product);

        $labels = $product->getExtension('productLabels');
        static::assertInstanceOf(ProductLabelCollection::class, $labels);
        static::assertCount(1, $labels);

        $assignedLabel = $labels->first();
        static::assertNotNull($assignedLabel);
        static::assertSame($labelId, $assignedLabel->getId());
        static::assertSame('New', $assignedLabel->getName());
    }

    /**
     * @return EntityRepository<ProductLabelCollection>
     */
    private function getLabelRepository(): EntityRepository
    {
        $repository = $this->getContainer()->get('product_label.repository');
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }

    /**
     * @return EntityRepository<ProductCollection>
     */
    private function getProductRepository(): EntityRepository
    {
        $repository = $this->getContainer()->get('product.repository');
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }

    /**
     * @return EntityRepository<TaxCollection>
     */
    private function getTaxRepository(): EntityRepository
    {
        $repository = $this->getContainer()->get('tax.repository');
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }

    private function getTaxId(): string
    {
        $taxId = Uuid::randomHex();
        $this->getTaxRepository()->create([[
            'id' => $taxId,
            'taxRate' => 19.0,
            'name' => '19%',
        ]], Context::createDefaultContext());

        return $taxId;
    }

    /**
     * @return EntityRepository<LanguageCollection>
     */
    private function getLanguageRepository(): EntityRepository
    {
        $repository = $this->getContainer()->get('language.repository');
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }

    /**
     * Creates a dedicated German language with a random id, so the translations
     * of a label can never collide with the system language's key — independent
     * of which locale the shop's system language uses.
     */
    private function createDeLanguage(): string
    {
        $context = Context::createDefaultContext();

        $localeId = $this->getLocaleRepository()
            ->searchIds((new Criteria())->addFilter(new EqualsFilter('code', 'de-DE')), $context)
            ->firstId();
        static::assertNotNull($localeId, 'Expected a de-DE locale to exist in the test environment');

        $languageId = Uuid::randomHex();
        $this->getLanguageRepository()->create([[
            'id' => $languageId,
            'name' => 'Deutsch',
            'localeId' => $localeId,
            'translationCodeId' => $localeId,
        ]], $context);

        return $languageId;
    }

    /**
     * @return EntityRepository<LocaleCollection>
     */
    private function getLocaleRepository(): EntityRepository
    {
        $repository = $this->getContainer()->get('locale.repository');
        static::assertInstanceOf(EntityRepository::class, $repository);

        return $repository;
    }
}
