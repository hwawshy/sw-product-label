import template from './sw-product-label-detail.html.twig';

const { Mixin } = Shopware;

export default {
    template,

    inject: ['repositoryFactory'],

    mixins: [
        Mixin.getByName('notification'),
        Mixin.getByName('placeholder'),
    ],

    data() {
        return {
            productLabel: null,
            isLoading: false,
            isSaveSuccessful: false,
        };
    },

    metaInfo() {
        return {
            title: this.$createTitle(this.productLabel?.translated?.name),
        };
    },

    computed: {
        productLabelRepository() {
            return this.repositoryFactory.create('product_label');
        },
    },

    created() {
        this.createdComponent();
    },

    methods: {
        async createdComponent() {
            if (!this.$route.params.id) {
                return;
            }

            this.isLoading = true;
            this.productLabel = await this.productLabelRepository.get(
                this.$route.params.id,
                Shopware.Context.api,
            );
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
                })
                .catch((error) => {
                    this.isLoading = false;
                    this.createNotificationError({
                        message: error?.detail ?? this.$t('global.default.errorTitle'),
                    });
                });
        },

        saveFinish() {
            this.isSaveSuccessful = false;
        },

        onCancel() {
            this.$router.push({ name: 'sw.product.label.index' });
        },

        saveOnLanguageChange() {
            return this.onSave();
        },

        abortOnLanguageChange() {
            return this.productLabelRepository.hasChanges(this.productLabel);
        },

        onChangeLanguage() {
            this.createdComponent();
        },
    },
};
