import template from './sw-product-detail-labels.html.twig';

const { Criteria, EntityCollection } = Shopware.Data;

export default {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            isLoading: false,
        };
    },

    computed: {
        product() {
            return Shopware.Store.get('swProductDetail').product;
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },

        labelColumns() {
            return [{
                property: 'name',
                label: this.$t('sw-product-label.list.columnName'),
                primary: true,
            }, {
                property: 'color',
                label: this.$t('sw-product-label.list.columnColor'),
            }, {
                property: 'priority',
                label: this.$t('sw-product-label.list.columnPriority'),
            }];
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        /*
         * The product detail store loads the product without the
         * productLabels association. Extension associations are not hydrated
         * into EntityCollections by the admin data layer (they arrive as a
         * plain array under `extensions`), so the labels are wrapped into a
         * real EntityCollection whose `source` is the nested association
         * route. The assignment card uses that source for its assign/delete
         * writes with local-mode disabled.
         */
        async createdComponent() {
            if (!this.product || this.product.isNew() || this.product.productLabels) {
                return;
            }

            this.isLoading = true;

            const criteria = new Criteria(1, 500);
            criteria.addAssociation('productLabels');

            const freshProduct = await this.productRepository.get(
                this.product.id,
                Shopware.Context.api,
                criteria,
            );

            const assignedLabels = freshProduct?.extensions?.productLabels ?? [];

            this.product.productLabels = new EntityCollection(
                `/product/${this.product.id}/extensions/productLabels`,
                'product_label',
                Shopware.Context.api,
                criteria,
                assignedLabels,
            );

            this.isLoading = false;
        },
    },
};
