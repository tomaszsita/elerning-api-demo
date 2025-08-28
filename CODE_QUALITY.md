# Code Quality Tools

This project uses several code quality tools to maintain consistent code style and catch potential issues.

## Tools Included

### 1. PHP Code Sniffer (PHPCS)
- **Purpose**: Detects coding standard violations
- **Standard**: PSR-12 + Symfony coding standards
- **Configuration**: `phpcs.xml`

### 2. PHP Code Beautifier and Fixer (PHPCBF)
- **Purpose**: Automatically fixes coding standard violations
- **Standard**: Same as PHPCS
- **Configuration**: `phpcs.xml`

### 3. PHP CS Fixer
- **Purpose**: Advanced code style fixer with more rules
- **Configuration**: `.php-cs-fixer.php`

### 4. PHPStan
- **Purpose**: Static analysis tool to catch bugs
- **Configuration**: `phpstan.dist.neon`

## Usage

### Using Make Commands (Recommended)

```bash
# Run all code quality checks
make code-quality

# Run individual tools
make phpcs          # Check coding standards
make phpcbf         # Fix coding standards automatically
make php-cs-fixer   # Run PHP CS Fixer
make phpstan        # Run static analysis

# Fix all code style issues
make fix

# Show help
make help
```

### Using Composer Scripts

```bash
# Run PHPCS
docker-compose exec app composer phpcs

# Fix with PHPCBF
docker-compose exec app composer phpcs:fix

# Run PHP CS Fixer
docker-compose exec app composer php-cs-fixer

# Check with PHP CS Fixer (dry run)
docker-compose exec app composer php-cs-fixer:check
```

### Using Direct Scripts

```bash
# Run PHPCS
./scripts/phpcs.sh

# Fix with PHPCBF
./scripts/phpcbf.sh

# Run PHP CS Fixer
./scripts/php-cs-fixer.sh
```

### Using Docker Commands Directly

```bash
# Run PHPCS
docker-compose exec app vendor/bin/phpcs --standard=phpcs.xml

# Fix with PHPCBF
docker-compose exec app vendor/bin/phpcbf --standard=phpcs.xml

# Run PHP CS Fixer
docker-compose exec app vendor/bin/php-cs-fixer fix

# Run PHPStan
docker-compose exec app vendor/bin/phpstan analyse
```

## Configuration Files

- `phpcs.xml` - PHP Code Sniffer configuration
- `.php-cs-fixer.php` - PHP CS Fixer configuration
- `phpstan.dist.neon` - PHPStan configuration

## IDE Integration

### VS Code
Install these extensions:
- PHP Intelephense
- PHP CS Fixer
- PHP Sniffer

### PhpStorm
- Go to Settings → Tools → External Tools
- Add PHPCS and PHP CS Fixer as external tools
- Configure file watchers for automatic fixing

## Pre-commit Hook (Optional)

You can set up a pre-commit hook to automatically run code quality checks:

```bash
# Create pre-commit hook
cat > .git/hooks/pre-commit << 'EOF'
#!/bin/bash
make code-quality
EOF

chmod +x .git/hooks/pre-commit
```

## Continuous Integration

Add these commands to your CI pipeline:

```yaml
# Example for GitHub Actions
- name: Run Code Quality Checks
  run: |
    make code-quality
```

## Troubleshooting

### Common Issues

1. **Permission denied on scripts**
   ```bash
   chmod +x scripts/*.sh
   ```

2. **Docker container not running**
   ```bash
   docker-compose up -d
   ```

3. **Dependencies not installed**
   ```bash
   docker-compose exec app composer install
   ```

### Ignoring Specific Files

To ignore specific files or directories, update the configuration files:

- `phpcs.xml`: Add `<exclude-pattern>` rules
- `.php-cs-fixer.php`: Update the `exclude` array in the finder
- `phpstan.dist.neon`: Add to the `excludePaths` section
