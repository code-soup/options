# WordPress plugin production deploy

This repository is a **Composer package** and a **standalone WordPress plugin**. For plugin-only distribution (no `skills/`, `docs/`, PHPCS, or Composer dev tooling), use the [wp-plugin-production-deploy](https://github.com/code-soup/wp-plugin-production-deploy) GitHub Action.

## Branch layout

| Branch | Purpose |
|--------|---------|
| `master` | Development source (this tree) |
| `production` | Auto-generated runtime plugin (force-pushed by CI) |

Install the plugin from `production` for a minimal tree, or use Composer from `master` / Packagist as today.

## Workflow

File: [`.github/workflows/production-deploy.yml`](../.github/workflows/production-deploy.yml)

**Triggers:**

- Push to `master`
- Manual **Run workflow** (`workflow_dispatch`)

**What it does:**

1. Runs `npm ci` and `npm run build:prod` (no-op stub — assets are committed under `assets/`)
2. Runs `composer install --no-dev` (no runtime PHP deps; dev tools excluded via `.deployignore`)
3. Copies paths listed in `production-files`
4. Applies [`.deployignore`](../.deployignore)
5. Force-pushes to `production`

## Included in production builds

Default copy list:

- `includes/`, `assets/`, `index.php`
- `LICENSE.txt`, `README.md`, `CHANGELOG.md`, `readme.txt` (when present on `master`)

## Before you release

1. Merge plugin metadata (`readme.txt`, version headers) onto `master` if not already there.
2. Let the workflow run (or trigger it manually).
3. Tag from `production` or attach a zip built from that branch if you distribute archives.

## Local parity

There is no local build step for CSS/JS. To preview what ships:

```bash
# After a workflow run, fetch production
git fetch origin production
git checkout production
```

## Changing behavior

- **More files:** extend `production-files` in the workflow YAML.
- **Exclude paths:** edit `.deployignore`.
- **Different target branch:** change `target-branch` input (default in the action is `master`; this repo uses `production` to avoid overwriting dev history).
