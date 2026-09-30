<?php

declare(strict_types=1);

namespace SwProductLabel\Command;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'product-label:demo',
    description: 'Creates demo product labels and a demo product (including sales channel visibilities) and assigns the labels to the product',
)]
class ProductLabelDemoCommand extends Command
{
    private const DEMO_PRODUCT_NUMBER = 'SW-DEMO-001';

    private const DEMO_LABELS = [
        ['name' => 'Sale', 'color' => '#E5342B', 'priority' => 10],
        ['name' => 'New', 'color' => '#1FA45B', 'priority' => 5],
    ];

    /**
     * @param EntityRepository<\SwProductLabel\Content\ProductLabel\ProductLabelCollection> $labelRepository
     * @param EntityRepository<\Shopware\Core\Content\Product\ProductCollection> $productRepository
     * @param EntityRepository<\Shopware\Core\System\Tax\TaxCollection> $taxRepository
     */
    public function __construct(
        private readonly EntityRepository $labelRepository,
        private readonly EntityRepository $productRepository,
        private readonly EntityRepository $taxRepository,
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $context = Context::createCLIContext();

        $labelIds = $this->upsertLabels($io, $context);
        $productId = $this->upsertDemoProduct($io, $context, $labelIds);

        $io->success(sprintf(
            'Demo data is ready. Labels: %s | Product id: %s',
            implode(', ', array_column(self::DEMO_LABELS, 'name')),
            $productId,
        ));

        return Command::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function upsertLabels(SymfonyStyle $io, Context $context): array
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('name', array_column(self::DEMO_LABELS, 'name')));

        $searchResult = $this->labelRepository->search($criteria, $context);
        $existingLabelIds = [];
        foreach ($searchResult as $existingLabel) {
            $existingLabelIds[strval($existingLabel->getName())] = $existingLabel->getId();
        }

        $createPayload = [];
        $labelIds = [];

        foreach (self::DEMO_LABELS as $demoLabel) {
            $existingLabelId = $existingLabelIds[$demoLabel['name']] ?? null;
            if ($existingLabelId) {
                $labelIds[] = $existingLabelId;
                $io->text(sprintf('Label "%s" already exists (%s).', $demoLabel['name'], $existingLabelId));

                continue;
            }

            $labelId = Uuid::randomHex();
            $labelIds[] = $labelId;
            $createPayload[] = [
                'id' => $labelId,
                'name' => $demoLabel['name'],
                'color' => $demoLabel['color'],
                'priority' => $demoLabel['priority'],
                'active' => true,
            ];

            $io->text(sprintf('Label "%s" created (%s).', $demoLabel['name'], $labelId));
        }

        if ($createPayload !== []) {
            $this->labelRepository->create($createPayload, $context);
        }

        return $labelIds;
    }

    /**
     * @param list<string> $labelIds
     */
    private function upsertDemoProduct(SymfonyStyle $io, Context $context, array $labelIds): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', self::DEMO_PRODUCT_NUMBER));
        $criteria->addAssociation('visibilities');
        $criteria->setLimit(1);

        $existing = $this->productRepository->search($criteria, $context)->first();

        $salesChannelIds = array_map(
            static fn(mixed $salesChannelId): string => \is_string($salesChannelId) ? $salesChannelId : '',
            $this->connection->fetchFirstColumn('SELECT LOWER(HEX(id)) FROM sales_channel'),
        );
        if ($salesChannelIds === []) {
            $io->warning('No sales channels exist, skipping product visibilities.');

            return $this->storeProduct($io, $context, $existing, $labelIds, []);
        }

        return $this->storeProduct($io, $context, $existing, $labelIds, $this->buildVisibilities($existing, $salesChannelIds));
    }

    /**
     * @param list<string> $salesChannelIds
     *
     * @return list<array{salesChannelId: string, visibility: int}>
     */
    private function buildVisibilities(?ProductEntity $existing, array $salesChannelIds): array
    {
        $assigned = [];
        foreach ($existing?->getVisibilities() ?? [] as $visibility) {
            $assigned[] = $visibility->getSalesChannelId();
        }

        $visibilities = [];
        foreach ($salesChannelIds as $salesChannelId) {
            if (\in_array($salesChannelId, $assigned, true)) {
                continue;
            }

            $visibilities[] = [
                'salesChannelId' => $salesChannelId,
                'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
            ];
        }

        return $visibilities;
    }

    /**
     * @param list<string> $labelIds
     * @param list<array{salesChannelId: string, visibility: int}> $visibilities
     */
    private function storeProduct(SymfonyStyle $io, Context $context, ?ProductEntity $existing, array $labelIds, array $visibilities): string
    {
        $productId = $existing?->getId() ?? Uuid::randomHex();

        $payload = [
            'id' => $productId,
            'productLabels' => array_map(
                static fn(string $labelId) => ['id' => $labelId],
                $labelIds,
            ),
        ];

        if ($visibilities !== []) {
            $payload['visibilities'] = $visibilities;
        }

        if ($existing === null) {
            $payload = [
                ...$payload,
                'productNumber' => self::DEMO_PRODUCT_NUMBER,
                'name' => 'Demo Surfboard',
                'description' => 'Demo product to showcase the product label plugin in the storefront.',
                'price' => [[
                    'currencyId' => Defaults::CURRENCY,
                    'gross' => 149.9,
                    'net' => 125.97,
                    'linked' => true,
                ]],
                'stock' => 10,
                'active' => true,
                'taxId' => $this->getOrCreateTaxId($io, $context),
            ];

            $this->productRepository->create([$payload], $context);
            $io->text(sprintf('Product "%s" created (%s).', self::DEMO_PRODUCT_NUMBER, $productId));
        } else {
            $this->productRepository->update([$payload], $context);
            $io->text(sprintf('Product "%s" updated (%s).', self::DEMO_PRODUCT_NUMBER, $productId));
        }

        return $productId;
    }

    private function getOrCreateTaxId(SymfonyStyle $io, Context $context): string
    {
        $taxId = $this->connection->fetchOne('SELECT LOWER(HEX(id)) FROM tax LIMIT 1');
        if (\is_string($taxId) && $taxId !== '') {
            return $taxId;
        }

        $taxId = Uuid::randomHex();
        $this->taxRepository->create([[
            'id' => $taxId,
            'name' => '19%',
            'taxRate' => 19.0,
        ]], $context);
        $io->text('Tax "19%" created.');

        return $taxId;
    }
}
