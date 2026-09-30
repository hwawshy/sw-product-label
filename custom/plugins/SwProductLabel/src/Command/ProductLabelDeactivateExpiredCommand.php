<?php

declare(strict_types=1);

namespace SwProductLabel\Command;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Uuid\Uuid;
use SwProductLabel\Storefront\Cache\ProductLabelCacheInvalidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'product-label:deactivate-expired',
    description: 'Deactivates all product labels whose "valid to" date is in the past',
)]
class ProductLabelDeactivateExpiredCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ProductLabelCacheInvalidator $cacheInvalidator,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        $expiredLabels = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(label.id)) AS id, label_translation.name
             FROM product_label label
             INNER JOIN product_label_translation label_translation
                ON label_translation.product_label_id = label.id
                AND label_translation.language_id = :systemLanguage
             WHERE label.active = 1
             AND label.valid_to IS NOT NULL
             AND label.valid_to < :now',
            [
                'now' => $now,
                'systemLanguage' => Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM),
            ],
        );

        if ($expiredLabels === []) {
            $io->success('No expired active product labels found.');

            return Command::SUCCESS;
        }

        $expiredLabelIds = array_map(
            static fn(array $label): string => \is_string($label['id']) ? $label['id'] : '',
            $expiredLabels,
        );

        $this->connection->executeStatement(
            'UPDATE product_label
             SET active = 0, updated_at = :now
             WHERE id IN (:ids)',
            [
                'now' => $now,
                'ids' => Uuid::fromHexToBytesList($expiredLabelIds),
            ],
            ['ids' => ArrayParameterType::BINARY],
        );

        $this->cacheInvalidator->invalidateByLabelIds($expiredLabelIds);

        $io->success(sprintf('Deactivated %d expired product label(s):', \count($expiredLabels)));
        $io->table(['Name', 'Id'], array_map(
            static fn(array $label) => [$label['name'], $label['id']],
            $expiredLabels,
        ));

        return Command::SUCCESS;
    }
}
