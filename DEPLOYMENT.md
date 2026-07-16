# Deployment Instructions

## Initial Repository Setup

Repository is initialized and ready to push.

**Repository URL:** https://github.com/code-soup/boxnow-delivery-croatia-woocommerce

## Push to GitHub

### First Time Push

```bash
# Push develop branch (main development branch)
git push -u origin develop
```

This will:
1. Push all source code to `develop` branch
2. Trigger GitHub Action automatically
3. Action will build production version
4. Production build pushed to `master` branch

### After First Push

The `master` branch will be automatically created and populated by the GitHub Action with production-ready files.

## What Happens Next

1. **GitHub Action runs** (automatically after pushing to develop)
   - Installs Node.js 18
   - Installs PHP 8.1
   - Runs `npm ci` (clean install)
   - Runs `composer install --no-dev --optimize-autoloader`
   - Runs `npm run build:prod`
   - Removes all development files
   - Creates production .gitignore
   - Force-pushes to `master` branch

2. **Master branch contains** (production-ready)
   - Compiled assets in `dist/`
   - Production PHP code in `includes/`
   - Production Composer dependencies in `vendor/`
   - Documentation (README, CHANGELOG, LICENSE)
   - NO source files, NO build tools, NO dev dependencies

## Branch Structure

### `develop` Branch
- **Purpose:** Development and source control
- **Contents:** Full source code, build tools, dev dependencies
- **Work here:** All development, feature branches merge here
- **Protected:** Yes - require pull requests

### `master` Branch
- **Purpose:** Production distribution
- **Contents:** Production-ready plugin files only
- **Managed by:** GitHub Actions (automated)
- **Never commit directly:** Automatically updated on `develop` push

## Development Workflow

### Clone and Setup

```bash
# Clone repository
git clone https://github.com/code-soup/boxnow-delivery-croatia-woocommerce.git
cd boxnow-delivery-croatia-woocommerce

# Checkout develop
git checkout develop

# Install dependencies
npm install
composer install

# Build for development
npm run build
```

### Daily Development

```bash
# Create feature branch
git checkout -b feature/my-feature

# Make changes
# ... edit files ...

# Commit changes
git add .
git commit -m "feat: describe your feature"

# Push feature branch
git push origin feature/my-feature

# Create PR to develop on GitHub
# After merge, GitHub Action automatically deploys to master
```

## Download URLs

### For End Users (Production)

```
https://github.com/code-soup/boxnow-delivery-croatia-woocommerce/archive/refs/heads/master.zip
```

This is the clean, production-ready version without source files.

### For Developers (Development)

```
https://github.com/code-soup/boxnow-delivery-croatia-woocommerce/archive/refs/heads/develop.zip
```

This includes all source files and build tools.

## Manual Build (if needed)

If you need to build locally:

```bash
# Install dependencies
npm ci
composer install --no-dev --optimize-autoloader

# Build production assets
npm run build:prod
```

## Troubleshooting

### Action Fails

Check GitHub Actions tab in repository for error details.

Common issues:
- Missing `npm run build:prod` script (✅ already added)
- Node/PHP version mismatch (configured for Node 18, PHP 8.1)
- Composer dependencies conflict (using `--no-dev` flag)

### Master Branch Not Updated

1. Check GitHub Actions ran successfully
2. Verify you pushed to `develop` branch
3. Check Action logs for errors

### Files Missing in Master

The GitHub Action deliberately excludes:
- `src/` - Source files
- `node_modules/` - Dev dependencies
- Build configs (babel, webpack, eslint, etc.)
- Development docs (`docs/`, `agent-skills/`, `backups/`)

These are only in `develop` branch.

## GitHub Repository Settings

### Recommended Settings

**Branch Protection for `develop`:**
- Require pull request before merging
- Require status checks to pass
- Require conversation resolution before merging

**Branch Protection for `master`:**
- No direct commits (managed by Actions only)
- Optional: Protect against force pushes (but Action uses force push)

### Required Permissions

Repository needs default GitHub Actions permissions:
- Read repository contents
- Write to repository (push to master)

No additional secrets needed.

## Next Steps

1. Push to GitHub: `git push -u origin develop`
2. Wait for GitHub Action to complete (~2-5 minutes)
3. Verify `master` branch created and populated
4. Download from master to test installation
5. Configure branch protection rules
6. Add collaborators if needed

## Support

For workflow issues, see: `docs/git-workflow.md`
For technical issues, see: GitHub Issues

## License

GPL-3.0+
