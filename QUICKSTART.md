# Quick Start Guide

Get started with PHPStan AI Formatter in 60 seconds.

## Installation

```bash
composer require --dev hackbard/phpstan-ai-formatter
```

That's it! The extension is automatically registered.

## Basic Usage

### For AI Chat (Claude, ChatGPT)

```bash
# Generate compact report
phpstan analyse --error-format=ai

# Copy output and paste into your AI assistant
```

### For Automated Processing

```bash
# Generate JSON output
phpstan analyse --error-format=ai-json > analysis.json

# Use with jq or other tools
cat analysis.json | jq '.files'
```

## Example Output

**Before (standard format):**
```
 ------ -----------------------------------------------------------------------
  Line   src/UserService.php
 ------ -----------------------------------------------------------------------
  23     Parameter #1 $user of method App\UserRepository::save() expects
         App\Entity\User, App\Entity\User|null given.
  45     Method App\UserService::getUser() should return App\Entity\User
         but returns App\Entity\User|null.
 ------ -----------------------------------------------------------------------
```

**After (ai format):**
```
PHPStan Analysis
Found: 2 errors, 0 warnings

Errors
------
src/UserService.php:23 | Method param $user: User|null ≠ User
src/UserService.php:45 | getUser() → User|null (expected User)
```

**Token savings: 78%** ✅

## Common Workflows

### 1. Quick Fix with AI

```bash
phpstan analyse --error-format=ai | pbcopy
# Paste into Claude: "Fix these PHPStan errors"
```

### 2. CI/CD Integration

```yaml
# .github/workflows/phpstan.yml
- run: vendor/bin/phpstan analyse --error-format=ai-json > report.json
- run: gh pr comment --body-file report.json
```

### 3. Pre-commit Hook

```bash
# .git/hooks/pre-commit
#!/bin/bash
vendor/bin/phpstan analyse --error-format=ai $(git diff --cached --name-only | grep '.php$')
```

## Tips

- Use `ai` format for human reading
- Use `ai-json` format for automation
- Combine with `--level=max` for thorough analysis
- Filter by path: `phpstan analyse src/Controllers --error-format=ai`

## Need Help?

- 📖 [Full Documentation](README.md)
- 🐛 [Report Issues](https://github.com/hackbard/phpstan-ai-formatter/issues)
- 💬 [Discussions](https://github.com/hackbard/phpstan-ai-formatter/discussions)

## Next Steps

1. Try it on your project: `phpstan analyse --error-format=ai`
2. Configure PHPStan level: [PHPStan Config](https://phpstan.org/config-reference)
3. Integrate with your AI workflow
4. Share with your team! ⭐
