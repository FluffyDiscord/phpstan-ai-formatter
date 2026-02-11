# Contributing to PHPStan AI Formatter

Thank you for considering contributing! 🎉

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/YOUR-USERNAME/phpstan-ai-formatter.git`
3. Install dependencies: `composer install`
4. Create a branch: `git checkout -b feature/your-feature-name`

## Development

### Running Tests

```bash
composer test
# or
vendor/bin/phpunit
```

### Running PHPStan

```bash
vendor/bin/phpstan analyse
```

### Testing the Formatters

You can test the formatters on a real project:

```bash
# Test text format
vendor/bin/phpstan analyse --error-format=ai

# Test JSON format
vendor/bin/phpstan analyse --error-format=ai-json
```

## Code Style

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Add PHPDoc blocks for public methods
- Keep methods focused and concise

## Pull Request Process

1. **Update tests** - Add tests for new features or bug fixes
2. **Update documentation** - Update README.md if needed
3. **Update CHANGELOG** - Add your changes under [Unreleased]
4. **Ensure CI passes** - All tests and checks must pass
5. **Write clear commit messages** - Use conventional commits format

### Commit Message Format

```
<type>: <description>

[optional body]

[optional footer]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `test`: Adding or updating tests
- `refactor`: Code refactoring
- `chore`: Maintenance tasks

Example:
```
feat: add support for shortening array type unions

Added pattern matching to shorten long array type unions like
array<string|int|float|bool> to array<string|int|...>
```

## Ideas for Contributions

### New Features
- Additional message shortening patterns
- Configuration options for customizing output
- Support for colored output in JSON format
- Export to different formats (Markdown, HTML)
- Integration examples for popular CI/CD platforms

### Improvements
- More comprehensive tests
- Better documentation
- Performance optimizations
- Support for older PHPStan versions

### Bug Fixes
- Check existing issues for known bugs
- Report new bugs with detailed reproduction steps

## Questions?

- Open an issue for questions or discussions
- Check existing issues and PRs first

## License

By contributing, you agree that your contributions will be licensed under the MIT License.
