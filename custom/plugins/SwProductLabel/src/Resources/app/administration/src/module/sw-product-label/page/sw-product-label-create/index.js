import template from './sw-product-label-create.html.twig';

export default {
    template,

    methods: {
        async createdComponent() {
            this.productLabel = this.productLabelRepository.create(Shopware.Context.api);
            this.isLoading = false;
        },

        onSave() {
            this.isSaveSuccessful = false;
            this.isLoading = true;

            return this.productLabelRepository
                .save(this.productLabel, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.isSaveSuccessful = true;

                    void this.$router.push({
                        name: 'sw.product.label.detail',
                        params: { id: this.productLabel.id },
                    });
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: error?.detail ?? this.$t('global.default.errorTitle'),
                    });
                });
        },
    },
};
