# SwProductLabel

Shopware 6.7 plugin — colored product labels ("New", "Sale", …) with translations,
priority ordering, validity windows and a many-to-many product assignment. Renders
badges on storefront listing and detail pages and ships an administration module
(list/detail/create plus a product-detail assignment tab).

See the repository root README for full documentation (setup, architecture, design
decisions and CI).

## Install

```bash
bin/console plugin:refresh
bin/console plugin:install --activate SwProductLabel
bin/console assets:install
bin/console theme:compile
bin/console product-label:demo   # optional demo data
```

## Console commands

| Command | Description |
|---------|-------------|
| `product-label:deactivate-expired` | Deactivates all labels whose `validTo` is in the past |
| `product-label:demo` | Creates demo labels, a visible demo product and the label assignments |

## Testing

```bash
composer test      # unit + integration suites
composer stan      # PHPStan level max
composer cs:check  # php-cs-fixer
```

## License

MIT
