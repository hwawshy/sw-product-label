<?php

declare(strict_types=1);

namespace SwProductLabel\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use SwProductLabel\Storefront\LabelCriteriaBuilder;

class LabelCriteriaBuilderTest extends TestCase
{
    private const NOW = '2026-06-15 12:00:00.000';

    public function testGetValidityFiltersRequiresActiveLabels(): void
    {
        $filters = $this->getFilters(new \DateTimeImmutable(self::NOW));

        static::assertCount(3, $filters);

        $activeFilter = $filters[0];
        static::assertInstanceOf(EqualsFilter::class, $activeFilter);
        static::assertSame('active', $activeFilter->getField());
        static::assertTrue($activeFilter->getValue());
    }

    public function testGetValidityFiltersAllowsOpenEndedAndStartedLabels(): void
    {
        $validFromFilter = $this->getFilters(new \DateTimeImmutable(self::NOW))[1];

        static::assertInstanceOf(OrFilter::class, $validFromFilter);
        $queries = $validFromFilter->getQueries();
        static::assertCount(2, $queries);

        $openEnded = $queries[0];
        static::assertInstanceOf(EqualsFilter::class, $openEnded);
        static::assertSame('validFrom', $openEnded->getField());
        static::assertNull($openEnded->getValue());

        $started = $queries[1];
        static::assertInstanceOf(RangeFilter::class, $started);
        static::assertSame('validFrom', $started->getField());
        static::assertSame([RangeFilter::LTE => self::NOW], $started->getParameters());
    }

    public function testGetValidityFiltersAllowsOpenEndedAndNotYetExpiredLabels(): void
    {
        $validToFilter = $this->getFilters(new \DateTimeImmutable(self::NOW))[2];

        static::assertInstanceOf(OrFilter::class, $validToFilter);
        $queries = $validToFilter->getQueries();
        static::assertCount(2, $queries);

        $openEnded = $queries[0];
        static::assertInstanceOf(EqualsFilter::class, $openEnded);
        static::assertSame('validTo', $openEnded->getField());
        static::assertNull($openEnded->getValue());

        $notExpired = $queries[1];
        static::assertInstanceOf(RangeFilter::class, $notExpired);
        static::assertSame('validTo', $notExpired->getField());
        static::assertSame([RangeFilter::GT => self::NOW], $notExpired->getParameters());
    }

    public function testGetValidityFiltersNormalizesNowToStorageFormat(): void
    {
        // 14:30 in +02:00 equals 12:30 UTC and has to be compared against the
        // storage formatted value, not the local representation
        $now = new \DateTimeImmutable('2026-06-15 14:30:00.000+02:00');

        $filters = $this->getFilters($now);

        $validFromFilter = $filters[1];
        static::assertInstanceOf(OrFilter::class, $validFromFilter);
        $started = $validFromFilter->getQueries()[1];
        static::assertInstanceOf(RangeFilter::class, $started);
        static::assertSame(
            [(new \DateTimeImmutable('2026-06-15 12:30:00.000'))->format(Defaults::STORAGE_DATE_TIME_FORMAT)],
            array_values($started->getParameters()),
        );
    }

    public function testAddLabelAssociationAppliesValidityFiltersAndSortings(): void
    {
        $criteria = new Criteria();
        $builder = new LabelCriteriaBuilder();

        $builder->addLabelAssociation($criteria, new \DateTimeImmutable(self::NOW));

        $association = $criteria->getAssociation(LabelCriteriaBuilder::ASSOCIATION_NAME);
        static::assertCount(3, $association->getFilters());
        static::assertCount(0, $criteria->getFilters());

        $sortings = $association->getSorting();
        static::assertCount(2, $sortings);
        static::assertInstanceOf(FieldSorting::class, $sortings[0]);
        static::assertSame('priority', $sortings[0]->getField());
        static::assertSame(FieldSorting::DESCENDING, $sortings[0]->getDirection());
        static::assertInstanceOf(FieldSorting::class, $sortings[1]);
        static::assertSame('id', $sortings[1]->getField());
        static::assertSame(FieldSorting::ASCENDING, $sortings[1]->getDirection());
    }

    /**
     * @return list<Filter>
     */
    private function getFilters(\DateTimeImmutable $now): array
    {
        return (new LabelCriteriaBuilder())->getValidityFilters($now);
    }
}
