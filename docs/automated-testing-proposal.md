# Automated testing proposal

**Status:** Draft for review  
**Audience:** Maintainers preparing the 1.3.0 release and ongoing development  
**Last updated:** 2026-10-26

## Executive summary

CodeSoup Options today relies on **manual QA** and local **Composer lint scripts** (`lint`, `lint:phpcs`). There is **no CI**, **no PHPUnit suite**, and **no regression coverage** for recent fixes (tabs save button, `Path_Helper` install modes, `hide_save_button`).

This proposal adds a **phased automation plan**: fast static checks in GitHub Actions first, then a **WordPress-backed integration test suite** for behavior that matters, with optional **browser E2E** later. The goal is to catch the class of bugs found in the 1.3.0 review **before merge**, without blocking velocity on day one.

---

## Current state

| Capability | Tooling | Automated in CI |
|------------|---------|-----------------|
| PHP syntax | `php-parallel-lint` (`composer lint`) | No |
| Coding standards | PHPCS + WPCS (`composer lint:phpcs`) | No |
| Unit / integration tests | None | No |
| E2E (admin UI) | None | No |
| Release branch gates | Manual review | No |

**Implications:**

- Regressions in admin templates (e.g. inverted `if` in `form.php`) are invisible until manual testing.
- URL resolution (`Path_Helper`) depends on install layout; substring/segment logic needs fixture paths, not eyeballing.
- `hide_save_button` spans UI, form handler, `wp_insert_post_data`, ACF sync, and `Manager::save_options()` — too many surfaces for manual checks every release.

---

## Goals and non-goals

### Goals

1. **Every PR** runs static analysis in under ~2 minutes (no WordPress DB).
2. **Default branch and `v/*` release branches** run integration tests against a real WordPress test install.
3. **Critical paths** from 1.3.0 have named regression tests before the next tag.
4. **Contributor workflow** is documented: `composer test` locally mirrors CI.

### Non-goals (initial phases)

- Full ACF Pro coverage in CI (license + weight); stub or skip integration tests that require ACF unless a CI secret is provided.
- Visual regression testing of admin CSS.
- Testing third-party field frameworks (CMB2, MetaBox) beyond extension points.

---

## Recommended test pyramid

```
                    ┌─────────────┐
                    │  E2E (opt)  │  Playwright / wp-browser — Phase 3
                    └──────┬──────┘
               ┌───────────┴───────────┐
               │  Integration (WP)     │  PHPUnit + WP test library — Phase 2
               └───────────┬───────────┘
          ┌────────────────┴────────────────┐
          │  Unit (pure / mocked WP)        │  PHPUnit + Brain Monkey — Phase 2b
          └────────────────┬────────────────┘
     ┌─────────────────────┴─────────────────────┐
     │  Static: parallel-lint, PHPCS, (PHPStan)   │  Phase 0–1
     └────────────────────────────────────────────┘
```

**Principle:** Prefer **integration tests** for this codebase — most logic is hook registration, post lifecycle, and admin flow. Pure units still help for **path math**, **config normalization**, and **value objects** (`Page`).

---

## Tooling choices

| Layer | Recommendation | Why |
|-------|----------------|-----|
| Test runner | **PHPUnit 9.x** | Matches `php >= 7.4` in `composer.json`; PHPUnit 10+ requires PHP 8.1+. |
| WP bootstrap | **WordPress test library** (`wordpress-develop` / `install-wp-tests.sh` pattern) | De-facto standard for plugins; exercises real `$wpdb`, capabilities, `plugins_url()`. |
| Fast unit mocks | **Brain Monkey** (optional) | Mock `add_action` / filters for small helpers without full WP boot. |
| Polyfills | **yoast/phpunit-polyfills** | PHPUnit version compatibility with WP core test suite. |
| CI | **GitHub Actions** | Repo is on GitHub; no `.github/workflows` today. |
| Static (later) | **PHPStan** + **wordpress-stubs** | Phase 4; optional once tests exist. |

**Not recommended as the first step:** Cypress-only against production-like stacks — slower, flakier, and redundant until integration tests cover save guards and path helpers.

---

## Repository layout (target)

```
tests/
├── bootstrap.php                 # Loads WP test environment + plugin
├── phpunit.xml.dist
├── factory/                      # Helpers to create Manager + pages + posts
│   └── ManagerFactory.php
├── Unit/
│   ├── PathHelperTest.php
│   ├── PageTest.php
│   └── ConfigHelperTest.php
└── Integration/
    ├── PostManagerTest.php
    ├── HideSaveButtonTest.php
    ├── FormHandlerTest.php
    └── MetaboxRegistryTest.php

bin/
└── install-wp-tests.sh           # Standard WP plugin script (env: WP_VERSION, DB_*)

.github/workflows/
├── static.yml                    # lint + phpcs on all PRs
└── tests.yml                     # PHPUnit on push/PR to main + v/*
```

**Composer scripts (target):**

```json
"scripts": {
  "test": "phpunit",
  "test:unit": "phpunit --testsuite unit",
  "test:integration": "phpunit --testsuite integration",
  "ci:static": ["@lint", "@lint:phpcs"]
}
```

**Autoload-dev:**

```json
"autoload-dev": {
  "psr-4": {
    "CodeSoup\\Options\\Tests\\": "tests/"
  }
}
```

---

## Phase plan

### Phase 0 — CI static gate (≈ half day)

**Deliverables**

- `.github/workflows/static.yml`: PHP 7.4 + 8.3 matrix, `composer install`, `composer ci:static`.
- Branch protection (manual in GitHub settings): require `static` check on `master` and `v/*`.

**Value:** Immediate guard against syntax and WPCS drift; zero WordPress install.

---

### Phase 1 — Bootstrap WordPress tests (≈ 1–2 days)

**Deliverables**

- `bin/install-wp-tests.sh` (copy from [WP plugin handbook](https://developer.wordpress.org/plugins/plugin-basics/writing-a-plugin/#setup) template).
- `tests/bootstrap.php` loads plugin via `index.php` / autoloader.
- `tests/phpunit.xml.dist` with `unit` and `integration` suites.
- `.github/workflows/tests.yml`:
  - Services: MySQL 8.
  - Env: `WP_VERSION` (e.g. `latest`, `6.0` minimum from `index.php`).
  - Steps: install WP tests → `composer test`.

**Value:** Pipeline exists; first green test proves wiring.

---

### Phase 2 — High-value regression tests (≈ 2–3 days)

Prioritize tests that map to **known 1.3.0 issues** and **security-sensitive** paths.

| Priority | Subject | What to assert |
|----------|---------|----------------|
| P0 | `Page` / `hide_save_button` | `(bool)` coercion; string `'false'` does not hide save. |
| P0 | `Manager::is_page_save_allowed()` / `is_post_save_allowed()` | When merged (PR #10): saves blocked server-side. |
| P0 | `Path_Helper` | Standalone plugin path, `vendor/codesoup/options`, `vendor-prefixed/...`; **no** false positive for `my-vendor-tools`. Use `set_url_scheme` / filter `plugins_url` if needed. |
| P1 | `Post_Manager::extract_page_id_from_post_name()` | Prefix with underscores vs dashes in `post_name`. |
| P1 | `Config_Helper::normalize()` | Flat → nested keys; deprecation path. |
| P1 | `Form_Handler` (tabs) | 403 when `hide_save_button`; `save_post` fires when allowed. |
| P2 | `Hook_Registry::prevent_save_on_read_only_page()` | `post_content` unchanged after simulated save. |
| P2 | `Metabox_Registry` | Actions metabox absent when `hide_save_button`. |
| P3 | ACF `Init::save_options()` | Skips DB update when save disabled (mock `get_fields` if ACF absent). |

**Template / UI tests:** Avoid testing HTML strings where possible. Prefer:

- Calling the **condition** used by templates via a package-private test helper, **or**
- Integration test that loads admin screen and checks presence of submit control (heavier; defer to Phase 3).

For the **tabs save-button inversion** bug specifically: a one-line **integration** test that bootstraps tab mode, sets active tab, renders `form.php` into output buffer, and asserts substring `codesoup-save-actions` — acceptable as a single regression test.

---

### Phase 3 — E2E (optional, after 1.3.0)

**When:** After Phase 2 is stable and flakiness is understood.

**Options**

1. **Playwright** against `wp-env` (@wordpress/env) — good DX, Docker required locally/CI.
2. **Codeception + wp-browser** — PHP-native, steeper setup.

**Scenarios (smoke only):**

- Tabs mode: save button visible → submit → success notice.
- Pages mode: Actions metabox updates post.
- Asset enqueue: admin tabs CSS returns 200 (URL sanity).

---

### Phase 4 — Hardening

- PHPStan level 5+ on `includes/` with `php-stubs/wordpress-stubs`.
- Add `composer test` to release checklist in `CHANGELOG` / maintainer docs.
- **Require** new features to ship with at least one integration test (CONTRIBUTING note).

---

## CI workflow sketch

### Static (Phase 0)

```yaml
name: Static analysis
on:
  pull_request:
  push:
    branches: [master, 'v/**']
jobs:
  static:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['7.4', '8.3']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: none
      - run: composer install --prefer-dist --no-progress
      - run: composer ci:static
```

### Tests (Phase 1+)

```yaml
name: Tests
on:
  pull_request:
  push:
    branches: [master, 'v/**']
jobs:
  phpunit:
    runs-on: ubuntu-latest
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: wordpress_test
    env:
      WP_VERSION: latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install --prefer-dist --no-progress
      - run: bash bin/install-wp-tests.sh wordpress_test root root 127.0.0.1 latest
      - run: composer test
```

Tune PHP matrix (7.4 minimum + one modern 8.x) to balance coverage vs CI time.

---

## Local developer workflow

```bash
# One-time: DB credentials for local MySQL/MariaDB
export WP_TESTS_DIR=/tmp/wordpress-tests-lib
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

composer install
composer test              # all suites
composer test:unit         # fast subset
composer ci:static         # same as CI static job
```

Document in `README.md` § Development (short) linking to this file.

---

## Release and PR policy (proposed)

| Event | Required checks |
|-------|-----------------|
| PR → `master` or `v/*` | `static` |
| PR → `v/*` (release candidate) | `static` + `phpunit` (once Phase 1 lands) |
| Tag `v*` | All checks green on tagged commit; manual smoke optional until Phase 3 |

**Merge order for open 1.3.0 fix PRs:** Land tests in a follow-up PR onto `v/1.3.0` **or** require test commits in each fix PR before merge — maintainer choice. Recommendation: **one “test harness” PR** after #7–#10 merge, then **regression tests** that fail on `9bfef0f` and pass on `35eb980`.

---

## Risks and mitigations

| Risk | Mitigation |
|------|------------|
| WP test install slow/flaky in CI | Cache `wordpress-develop` tarball; single PHP version on integration job. |
| ACF Pro not in CI | Default `integrations.acf.enabled => false` in test factory; dedicated optional job with secret. |
| `plugins_url()` depends on filesystem layout | Tests set `WP_PLUGIN_DIR` and symlink plugin; path helper tests use real paths under `sys_get_temp_dir()`. |
| Parallel PRs (#7–#10) change behavior | Land harness first on `v/1.3.0`, then add tests as fixes merge. |

---

## Effort summary

| Phase | Scope | Outcome |
|-------|--------|---------|
| 0 | Static CI | PRs cannot break syntax/WPCS |
| 1 | WP bootstrap + smoke test | `composer test` works locally and in Actions |
| 2 | Regression suite | 1.3.0-class bugs covered |
| 3 | E2E smoke | Admin flows verified in browser |
| 4 | PHPStan + policy | Long-term maintainability |

---

## Decision checklist (for maintainers)

- [ ] Approve PHPUnit 9 + WP test library (vs Pest-only stack).
- [ ] Target branches: `master` only vs `master` + `v/**`.
- [ ] PHP matrix: 7.4 required + 8.2/8.3 recommended.
- [ ] Minimum WP version in CI: `6.0` (plugin header) vs `latest` only.
- [ ] ACF in CI: skip / free ACF / Pro via secret.
- [ ] Phase 0 implemented before or after 1.3.0 tag.

---

## Next steps after approval

1. Open implementation PR: Phase 0 workflow + `composer ci:static` script alias.
2. Open implementation PR: Phase 1 harness + one passing integration test (`Manager::create` registers post type).
3. Add regression tests for merged 1.3.0 fixes on `v/1.3.0`.
4. Update `docs/file-structure.md` with `tests/` directory.

**Questions or changes:** comment on the PR that introduces this document or open a discussion in GitHub Discussions if enabled.
