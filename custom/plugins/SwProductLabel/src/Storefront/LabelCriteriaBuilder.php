<?php

declare(strict_types=1);

namespace SwProductLabel\Storefront;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\Filter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class LabelCriteriaBuilder
{
    final public const ASSOCIATION_NAME = 'productLabels';

    public function addLabelAssociation(Criteria $criteria, ?\DateTimeImmutable $now = null): void
    {
        $association = $criteria->getAssociation(self::ASSOCIATION_NAME);

        foreach ($this->getValidityFilters($now ?? new \DateTimeImmutable()) as $filter) {
            $association->addFilter($filter);
        }

        $association->addSorting(new FieldSorting('priority', FieldSorting::DESCENDING));
        $association->addSorting(new FieldSorting('id', FieldSorting::ASCENDING));
    }

    /**
     * A label is currently valid when it is active and its validity window
     * covers the given point in time. An open-ended window is expressed by
     * a null side (validFrom / validTo).
     *
     * @return list<Filter>
     */
    public function getValidityFilters(\DateTimeImmutable $now): array
    {
        $now = $now->format(Defaults::STORAGE_DATE_TIME_FORMAT);

        return [
            new EqualsFilter('active', true),
            new OrFilter([
                new EqualsFilter('validFrom', null),
                new RangeFilter('validFrom', [RangeFilter::LTE => $now]),
            ]),
            new OrFilter([
                new EqualsFilter('validTo', null),
                new RangeFilter('validTo', [RangeFilter::GT => $now]),
            ]),
        ];
    }
}
