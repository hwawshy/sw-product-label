import template from './sw-product-label-list.html.twig';

const { Criteria } = Shopware.Data;

export default {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            productLabels: null,
            isLoading: true,
            sortBy: 'priority',
            sortDirection: 'DESC',
            total: 0,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.$t('sw-product-label.list.textHeadline')),
        };
    },

    computed: {
        productLabelRepository() {
            return this.repositoryFactory.create('product_label');
        },

        columns() {
            return [{
                property: 'name',
                dataIndex: 'name',
                label: this.$t('sw-product-label.list.columnName'),
                routerLink: 'sw.product.label.detail',
                primary: true,
            }, {
                property: 'color',
                dataIndex: 'color',
                label: this.$t('sw-product-label.list.columnColor'),
                align: 'left',
            }, {
                property: 'priority',
                dataIndex: 'priority',
                label: this.$t('sw-product-label.list.columnPriority'),
                align: 'right',
            }, {
                property: 'active',
                dataIndex: 'active',
                label: this.$t('sw-product-label.list.columnActive'),
                align: 'center',
            }, {
                property: 'validFrom',
                dataIndex: 'validFrom',
                label: this.$t('sw-product-label.list.columnValidFrom'),
                align: 'left',
            }, {
                property: 'validTo',
                dataIndex: 'validTo',
                label: this.$t('sw-product-label.list.columnValidTo'),
                align: 'left',
            }];
        },
    },

    created() {
        this.getList();
    },

    methods: {
        formatDate(value) {
            if (!value) {
                return '';
            }

            return Shopware.Filter.getByName('date')(value);
        },

        getList() {
            this.isLoading = true;

            const criteria = new Criteria(1, 25);
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            this.productLabelRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.total = result.total;
                this.productLabels = result;
                this.isLoading = false;
            });
        },

        onChange({ page, limit }) {
            this.isLoading = true;

            const criteria = new Criteria(page, limit);
            criteria.addSorting(Criteria.sort(this.sortBy, this.sortDirection));

            this.productLabelRepository.search(criteria, Shopware.Context.api).then((result) => {
                this.total = result.total;
                this.productLabels = result;
                this.isLoading = false;
            });
        },

        onSortColumn(column) {
            if (this.sortBy === column.dataIndex) {
                this.sortDirection = this.sortDirection === 'ASC' ? 'DESC' : 'ASC';
            } else {
                this.sortDirection = 'ASC';
                this.sortBy = column.dataIndex;
            }

            this.getList();
        },
    },
};
