# PHPStan AI Formatter

🤖 **AI-optimized error formatter for PHPStan** - Reduce output by up to 80% for AI context windows.

Perfect for Claude, ChatGPT, GitHub Copilot, Cursor, and other AI coding assistants.

## 🚀 Quick Start

**Option 1: MCP Server (Recommended for AI Assistants)**

Enable your AI assistant to run PHPStan directly:

```bash
npm install -g @webkult/phpstan-ai-mcp-server
```

Then configure your AI tool (Claude, Cursor, etc.) - see [MCP Setup Guide](mcp-server/SETUP_GUIDE.md)

**Option 2: PHPStan Extension (Command Line)**

Install in your PHP project:

```bash
composer require --dev webkult/phpstan-ai-formatter
phpstan analyse --error-format=ai
```

## Why?

Standard PHPStan output is verbose and wastes precious tokens when working with AI assistants:

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

This formatter produces compact, token-efficient output:

```
src/UserService.php:23 | Method param $user: User|null ≠ User
src/UserService.php:45 | getUser() → User|null (expected User)
```

**Result: ~80% fewer tokens, same diagnostic value!**

## Installation

```bash
composer require --dev webkult/phpstan-ai-formatter
```

The extension is automatically registered via [phpstan/extension-installer](https://github.com/phpstan/extension-installer).

## Usage

### Compact Text Format

Best for human-readable AI conversations:

```bash
phpstan analyse --error-format=ai
```

**Output example:**
```
PHPStan Analysis (config: phpstan.neon)
Found: 2 errors, 0 warnings

Errors
------
src/UserService.php:23 | Method param $user: User|null ≠ User
  → Consider using strict types
src/UserService.php:45 | getUser() → User|null (expected User)
```

### Structured JSON Format

Best for programmatic AI processing:

```bash
phpstan analyse --error-format=ai-json
```

**Output example:**
```json
{
    "totals": {
        "errors": 2,
        "file_errors": 2,
        "warnings": 0,
        "internal_errors": 0
    },
    "files": {
        "src/UserService.php": [
            {
                "line": 23,
                "message": "Method param $user: User|null ≠ User",
                "tip": "Consider using strict types",
                "ignorable": true
            },
            {
                "line": 45,
                "message": "getUser() → User|null (expected User)",
                "ignorable": false
            }
        ]
    },
    "warnings": []
}
```

## MCP Server Integration

**Model Context Protocol (MCP)** enables AI assistants to run PHPStan directly without manual commands.

### Supported AI Tools

- ✅ **Claude Desktop** - Official Anthropic desktop app
- ✅ **Claude Code** - Official CLI tool
- ✅ **Cursor** - AI-first code editor
- ✅ **VSCode with Cline** - AI coding assistant extension
- ✅ **Windsurf** - Codeium's AI IDE
- ✅ **Continue.dev** - Open-source AI assistant

### How It Works

1. Install MCP server: `npm install -g @webkult/phpstan-ai-mcp-server`
2. Configure your AI tool (one-time setup)
3. Ask your AI: "Analyze my PHP code for errors"
4. AI runs PHPStan automatically and suggests fixes

**Complete setup guide**: [MCP Server Setup](mcp-server/SETUP_GUIDE.md)

## Use Cases

### 🤖 AI-Assisted Code Review

```bash
# Generate compact report
phpstan analyse --error-format=ai > phpstan-report.txt

# Send to your AI assistant
cat phpstan-report.txt | pbcopy
```

Then paste into Claude/ChatGPT with your prompt:
> "Here's my PHPStan analysis. Help me fix these issues systematically."

### 🔄 CI/CD Integration

Add to your GitHub Actions workflow:

```yaml
- name: Run PHPStan
  run: |
    vendor/bin/phpstan analyse --error-format=ai-json > phpstan.json
    
- name: Comment on PR with AI analysis
  run: |
    # Send compact JSON to your AI service for automated code review
    curl -X POST $AI_REVIEW_ENDPOINT -d @phpstan.json
```

### 📝 Documentation Generation

```bash
phpstan analyse --error-format=ai-json | jq '.files | to_entries[]' | \
  ai-tool "Generate documentation for fixing these common errors"
```

## Token Savings Comparison

Tested on a typical Laravel project with 50 errors:

| Format | Token Count | Reduction |
|--------|-------------|-----------|
| Standard `table` | ~12,500 | baseline |
| Standard `json` | ~8,200 | 34% |
| **AI compact** | **~2,800** | **78%** |
| **AI JSON** | **~2,400** | **81%** |

*Token counts measured using GPT-4 tokenizer*

## Message Shortening Examples

The formatter intelligently abbreviates common patterns:

| Original | Shortened |
|----------|-----------|
| `Parameter #1 $user of method save() expects User, User\|null given` | `Method param $user: User\|null ≠ User` |
| `Method getUser() should return User but returns User\|null` | `getUser() → User\|null (expected User)` |
| `Call to an undefined method Repository::find()` | `Undefined method: find()` |
| `Access to an undefined property User::$email` | `Undefined property: $email` |
| `string\|int\|float\|bool\|array\|null` | `string\|int\|float\|...` |

## Configuration

The formatter works out of the box with zero configuration. However, you can customize PHPStan's behavior:

```neon
# phpstan.neon
includes:
    - vendor/webkult/phpstan-ai-formatter/extension.neon

parameters:
    level: max
    paths:
        - src
```

## Requirements

- PHP 7.4 or higher
- PHPStan 1.0 or higher

## Tips for AI Workflows

1. **Use JSON format for automation** - Easier to parse programmatically
2. **Use text format for conversations** - More natural for chat interfaces
3. **Pipe directly to AI tools** - Skip manual copy/paste
4. **Combine with git diff** - Only analyze changed files
5. **Set up pre-commit hooks** - Get instant AI feedback

### Example: Git Pre-commit Hook

```bash
#!/bin/bash
# .git/hooks/pre-commit

# Get changed PHP files
FILES=$(git diff --cached --name-only --diff-filter=ACM | grep '\.php$')

if [ -z "$FILES" ]; then
    exit 0
fi

# Run PHPStan on changed files only
vendor/bin/phpstan analyse --error-format=ai $FILES

# Optionally: Send to AI for auto-review
# vendor/bin/phpstan analyse --error-format=ai-json $FILES | your-ai-tool
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

```bash
git clone https://github.com/webkult/phpstan-ai-formatter.git
cd phpstan-ai-formatter
composer install
```

### Running Tests

```bash
composer test
```

## License

MIT License - see [LICENSE](LICENSE) file for details.

## Acknowledgments

- Built for the [PHPStan](https://phpstan.org/) static analysis tool
- Optimized for [Claude](https://claude.ai/), [ChatGPT](https://chat.openai.com/), and other AI assistants
- Inspired by the need for more efficient AI-assisted development workflows

## Related Projects

- [PHPStan](https://github.com/phpstan/phpstan) - The main static analysis tool
- [Larastan](https://github.com/nunomaduro/larastan) - PHPStan for Laravel
- [Psalm](https://psalm.dev/) - Alternative static analysis tool

---

**Made with 🤖 for developers working with AI assistants**

Found this useful? Star the repo and share with your team! ⭐
