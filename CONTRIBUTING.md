# Contributing

Contributions are welcome. Please open an issue first for larger changes so we can agree on the approach.

## Workflow

1. Fork the repository and create a branch from `main`.
2. Install dependencies: `composer install`.
3. Make your change and add or update tests under `tests/`.
4. Run the suite: `composer test`.
5. Add a line to the `[Unreleased]` section of [CHANGELOG.md](CHANGELOG.md).
6. Open a pull request describing what changed and why.

## Guidelines

- Follow the existing code style (PSR-12, 4-space indentation).
- New endpoint wrappers belong in the matching trait under `src/app/Services/Concerns`, use
  `$this->call()`, document the endpoint (`POST /rest/v2/...`) in the docblock, and get a row in
  the `endpoints` dataset in `tests/Feature/EndpointsTest.php`.
- Tests must never reach the real OTO API: use `Http::fake()` (see `TestCase::fakeOto()`).
- Keep public method signatures backwards compatible; breaking changes need a major version.

## Reporting bugs

Open an issue at <https://github.com/siberfx/laravel-tryoto/issues> with the package, PHP and
Laravel versions, the method you called and the (redacted) OTO response.

Security issues: see [SECURITY.md](SECURITY.md) instead.
