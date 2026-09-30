const { Module } = Shopware;

Shopware.Component.register('sw-product-label-list', () => import('./page/sw-product-label-list'));
Shopware.Component.register('sw-product-label-detail', () => import('./page/sw-product-label-detail'));
Shopware.Component.extend('sw-product-label-create', 'sw-product-label-detail', () => import('./page/sw-product-label-create'));

Module.register('sw-product-label', {
    type: 'plugin',
    name: 'product-label',
    title: 'sw-product-label.general.mainMenuItemGeneral',
    description: 'sw-product-label.general.descriptionTextModule',
    version: '1.0.0',
    targetVersion: '1.0.0',
    color: '#FFB600',
    icon: 'regular-tag',
    favicon: 'icon-module-products.png',
    entity: 'product_label',

    routes: {
        index: {
            components: {
                default: 'sw-product-label-list',
            },
            path: 'index',
            alias: '/',
        },
        detail: {
            component: 'sw-product-label-detail',
            path: 'detail/:id',
            meta: {
                parentPath: 'sw.product.label.index',
            },
        },
        create: {
            component: 'sw-product-label-create',
            path: 'create',
            meta: {
                parentPath: 'sw.product.label.index',
            },
        },
    },

    navigation: [
        {
            id: 'sw-product-label',
            label: 'sw-product-label.general.mainMenuItemGeneral',
            parent: 'sw-catalogue',
            path: 'sw.product.label.index',
            position: 60,
        },
    ],
});
