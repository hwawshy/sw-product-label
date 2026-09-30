# SwProductLabel — Shopware 6 Product Label Plugin

A Shopware 6.7 plugin that lets merchandisers create colored labels (e.g. **New**, **Sale**,
**Limited Edition**), assign them to products and render them automatically on the
storefront — ordered by priority, with validity windows and multi-language support.

Built as a vertical-slice reference implementation covering the full Shopware extension
surface: entity model + migrations, storefront criteria decoration, administration module
with product-detail assignment, console tooling, cache invalidation and CI.

## Features

- **`product_label` entity** — translated name, color, priority, `active` flag and
  `validFrom`/`validTo` validity window, many-to-many assigned to products
- **Storefront badges** on listing and detail pages, ordered by priority, colored via a
  CSS custom property per label
- **Validity windows** — a label only renders while `active` and inside its window;
  open-ended sides supported
- **Administration module** — list / detail / create pages under *Catalogues → Product
  labels*, plus a "Labels" assignment tab on the product detail page
- **Multi-language** — labels carry translations (en-GB / de-DE snippets included)
- **Cache invalidation** — writing a label (or an assignment) invalidates the affected
  cached product listing and detail routes
- **Console commands**
  - `product-label:deactivate-expired` — bulk-deactivates labels past their `validTo`
  - `product-label:demo` — creates demo labels, a demo product with sales-channel
    visibilities and category assignment, and wires everything together

## Repository layout

This repository is a full Shopware 6.7 development environment (docker compose, production
template) with the plugin living at `custom/plugins/SwProductLabel`.

```
custom/plugins/SwProductLabel
├── src/
│   ├── Command/                    console commands
│   ├── Content/                    entity definitions, collections, entities, product extension
│   ├── Migration/                  schema migrations
│   ├── Storefront/                 criteria builder, subscribers, cache invalidation, templates, SCSS
│   └── Resources/
│       ├── app/administration/     admin module sources (main.js, module/, view/, page/)
│       ├── app/storefront/         storefront SCSS
│       ├── config/services.php     PHP-DI service registration
│       └── snippet/                storefront snippets (en-GB / de-DE)
└── tests/
    ├── Unit/                       pure unit tests (criteria builder logic)
    └── Integration/                DAL persistence tests (transactional)
```

## Setup

Requirements: Docker Compose, PHP 8.5 (provided by the dev container), Node (for admin
builds).

```bash
docker compose up -d
docker compose exec web composer install
docker compose exec web bin/console system:install --create-database --basic-setup
docker compose exec web bin/console plugin:refresh
docker compose exec web bin/console plugin:install --activate SwProductLabel
docker compose exec web bin/console assets:install
docker compose exec web bin/console theme:compile
docker compose exec web bin/console cache:clear
```

Seed demo data (labels + a product that is visible in the storefront listing and carries
both labels):

```bash
docker compose exec web bin/console product-label:demo
```

Open the storefront at `http://127.0.0.1:8000/` and the admin at
`http://127.0.0.1:8000/admin` (admin / shopware). Navigate to *Catalogues → Product
labels*, open a label and try the language switch, or assign labels on a product's
"Labels" tab.

After changing administration sources, rebuild the bundle:

```bash
docker compose exec -e HOME=/tmp -e npm_config_cache=/tmp/.npm web bin/build-administration.sh
docker compose exec web bin/console assets:install
```

(Commit the rebuilt `src/Resources/public/` together with the source change — the repo
ships built assets so consumers don't need a node toolchain.)

## Quality gates

The same commands run in CI:

```bash
docker compose exec web composer cs:check   # php-cs-fixer (PER-CS2x0 + no_unused_imports)
docker compose exec web composer stan       # PHPStan level max + shopwarelabs/phpstan-shopware
docker compose exec web composer test       # PHPUnit (unit + integration suites)
```

CI (`.github/workflows/ci.yml`) runs three jobs on every push/PR to `master`/`main`:

1. **quality** — PHPStan + php-cs-fixer
2. **phpunit** — plugin test suite against a MariaDB 11.8 service
3. **install-validation** — boots a fresh Shopware from scratch
   (`system:install --create-database --basic-setup`), installs and activates the plugin,
   compiles the theme, then smoke-tests the storefront (HTTP 200 with a rendered body)
   and the presence of the built administration bundle

## Design decisions

### CI-first

The CI pipeline was written before the features it validates and grows with each phase.
The install-validation job is the most valuable one: it proves the plugin can be
discovered, installed, activated and rendered on a **pristine** Shopware — the exact flow
a customer runs. The database connection is patched into `.env` because the storefront
resolves `DATABASE_URL` through `$_SERVER` (dotenv), which ignores process environment
overrides.

### Criteria-level filtering

Label validity (active + `validFrom`/`validTo` window) is enforced in the
`LabelCriteriaBuilder` as **filters on the `productLabels` association inside the listing
and detail criteria** — not as Twig-level conditions. Consequences:

- expired or inactive labels never leave the database, so no bandwidth or hydration cost
- the object-cached listing route results stay correct without post-processing
- the logic is a pure function of `$now` (`getValidityFilters()`), which makes it
  unit-testable without a kernel — see `tests/Unit/LabelCriteriaBuilderTest.php`

### Plain-config admin convention

Administration page/view files export **plain config objects** (`export default { ... }`)
and the module's `index.js` performs the single lazy
`Shopware.Component.register(name, () => import(...))`. Calling `Component.register` inside
a page file double-registers the component, which silently produces an empty config and a
"could not build" failure. Every component imports its twig template explicitly and
declares `inject` for services it uses. Because `{{ }}` output is passthrough to Vue
(twig.js output tokens are removed by the admin template factory), templates use `$t()`
directly — only `{% block %}` inheritance is handled by twig.

The product detail tab is added via `Component.override('sw-product-detail', ...)` plus a
runtime `$router.addRoute(...)` — re-registering the `sw-product` module to add a child
route does not work in 6.7 (duplicate module ids abort registration, and the documented
`routeMiddleware` pattern does not match the 6.7 router). Extension associations are
serialized under `extensions` by the API, so the assignment view wraps the hydrated plain
array into a real `EntityCollection` whose `source` is the nested
`/product/{id}/extensions/productLabels` route — that is what the many-to-many assignment
card uses for its writes.

### Cache invalidation mirrors core

`ProductLabelCacheSubscriber` listens to `EntityWrittenContainerEvent` and resolves
affected products (assignments) and their categories (`product_category_tree`) to
invalidate the same tags core uses: `ProductDetailRoute::buildName(id)` and
`ProductListingRoute::buildName(categoryId)`. Console commands reuse the invalidator, so
`product-label:deactivate-expired` keeps the storefront consistent even though it writes
via SQL.

## Ideas for improvement

- **Scheduled task instead of cron** — `deactivate-expired` could run as a Shopware
  scheduled task (`ScheduledTaskHandler`) with the console command kept for manual runs,
  removing the external cron dependency.
- **ACL** — the admin module currently assumes administrator privileges; wire
  `privilege` metadata into the module routes plus `acl.can()` checks on the grid and
  forms (core convention: `product_label.viewer|editor|creator|deleter`).
- **Theme configuration** — expose badge shape/typography as theme config variables
  (`@Shopware` theme `config/inheritance`), so merchants can restyle badges per sales
  channel without SCSS.
- **Administration JS unit tests / e2e** — add Jest (or Vitest) tests for the admin
  components and a Playwright e2e flow (create label → assign → assert storefront badge),
  complementing the PHP unit/integration suites.
- **Storefront JS entry** — register a storefront JS entry for interactions (e.g. badge
  tooltips) once behavior beyond CSS is needed.

## License

MIT
