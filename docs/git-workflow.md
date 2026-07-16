# Git Workflow & Deployment

## Branch Strategy

### `develop` Branch (Development)
- Main development branch
- Contains all source files, build tools, and development dependencies
- Includes: `src/`, `node_modules/`, build configs, dev dependencies
- All development work happens here
- Protected branch - requires pull requests

### `master` Branch (Production)
- Production-ready distribution branch
- Contains only files needed to run the plugin
- Excludes: source files, build tools, dev dependencies
- Automatically updated via GitHub Actions
- Users download from this branch

## Automated Deployment

### How It Works

1. Developer merges code to `develop` branch
2. GitHub Action automatically triggers
3. Action performs:
   - Installs Node.js dependencies
   - Installs Composer production dependencies (`--no-dev`)
   - Runs production build (`npm run build:prod`)
   - Removes development files
   - Force-pushes clean build to `master` branch

### Files Included in Production (master)

**Core Plugin Files:**
- `index.php` - Main plugin file
- `run.php` - Plugin initialization
- `uninstall.php` - Uninstall handler
- `includes/` - All PHP classes
- `vendor/` - Production Composer dependencies only
- `dist/` - Compiled CSS/JS assets
- `languages/` - Translation files
- `templates/` - Template files

**Documentation:**
- `README.md`
- `CHANGELOG.md`
- `LICENSE.txt`

### Files Excluded from Production (master)

**Development Files:**
- `src/` - Source files (pre-compiled)
- `node_modules/` - Node dependencies
- `.github/` - GitHub workflows
- `agent-skills/` - AI agent documentation
- `backups/` - Code backups
- `docs/` - Internal documentation

**Build Configuration:**
- `package.json`, `package-lock.json`
- `composer.json`, `composer.lock`
- `babel.config.js`
- `eslint.config.js`
- `phpcs.xml`
- `webpack.config.js`

## Manual Deployment Steps

If you need to manually deploy to production:

```bash
# 1. Checkout develop branch
git checkout develop

# 2. Install dependencies
npm ci
composer install --no-dev --optimize-autoloader

# 3. Build production assets
npm run build:prod

# 4. Create production build
# Remove development files
rm -rf node_modules src .github agent-skills backups docs
rm -f package.json package-lock.json composer.json composer.lock
rm -f babel.config.js eslint.config.js phpcs.xml

# 5. Switch to master and deploy
git checkout master
# Copy files from develop
# Commit and push
```

## Development Workflow

### Initial Setup

```bash
# Clone repository
git clone https://github.com/code-soup/boxnow-delivery-croatia-woocommerce.git
cd boxnow-delivery-croatia-woocommerce

# Checkout develop branch
git checkout develop

# Install dependencies
npm install
composer install

# Build assets for development
npm run build
```

### Daily Development

```bash
# Create feature branch from develop
git checkout develop
git pull origin develop
git checkout -b feature/your-feature-name

# Make changes, commit
git add .
git commit -m "feat: your feature description"

# Push feature branch
git push origin feature/your-feature-name

# Create pull request to develop branch
# After merge, GitHub Action automatically deploys to master
```

## GitHub Action Configuration

The deployment workflow is defined in `.github/workflows/deploy-production.yml`.

**Trigger:** Push to `develop` branch
**Steps:**
1. Checkout develop
2. Setup Node.js 18
3. Setup PHP 8.1
4. Install dependencies
5. Build production assets
6. Remove dev files
7. Force-push to master

## Download URLs

**Development version (with source):**
```
https://github.com/code-soup/boxnow-delivery-croatia-woocommerce/archive/refs/heads/develop.zip
```

**Production version (ready to install):**
```
https://github.com/code-soup/boxnow-delivery-croatia-woocommerce/archive/refs/heads/master.zip
```

## Notes

- Never commit directly to `master` - it's automatically managed
- All development happens in `develop` branch or feature branches
- Production build is automatically created on merge to `develop`
- Users should always download from `master` branch
- `master` branch is force-pushed - old history is replaced with each deployment
