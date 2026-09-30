import template from './sw-product-detail.html.twig';

const { Application } = Shopware;

/*
 * The "sw-product" module cannot be re-registered to add a child route, so
 * the route is added as soon as a product detail page is created. The tab
 * itself is injected through the `sw_product_detail_content_tabs_additional`
 * template block.
 */
Shopware.Component.override('sw-product-detail', {
    template,

    created() {
        this.registerProductLabelTabRoute();
    },

    methods: {
        registerProductLabelTabRoute() {
            const router = this.$router;

            if (router.hasRoute('sw.product.detail.productLabels')) {
                return;
            }

            router.addRoute('sw.product.detail', {
                name: 'sw.product.detail.productLabels',
                path: '/sw/product/detail/:id/productLabels',
                component: Application.view.getComponentForRoute('sw-product-detail-labels'),
                meta: {
                    parentPath: 'sw.product.index',
                },
            });
        },
    },
});
