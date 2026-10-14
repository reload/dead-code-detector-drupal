# AGENTS.md

A PHPStan extension: Drupal usage providers for
[shipmonk/dead-code-detector][dcd]. This file points to the documentation.
The reasons behind the code are in `docs/design.md`, and a rule that a tool
enforces lives in that tool's configuration; leave both out of here.

## Documentation

- `README.md`: installation, what counts as used, configuration, limitations,
  and the short form of writing a provider.
- `docs/design.md`: the principles, the code structure, how each part is
  checked and tested, and the steps for adding a provider.
- `docs/coverage.md`: Drupal's documented mechanisms mapped to the providers,
  with what is not covered. Change it with the code.
- `docs/extending.md`: how a site writes its own provider, and when a
  mechanism belongs here instead.
- `docs/twig.md`: Twig name matching, its limits and the measurement behind
  it.
- `docs/related-work.md` and `docs/measurements/`: context and evidence; they
  describe a point in time and say which. Only public corpora are named;
  client projects stay out of the documentation.
- `CHANGELOG.md`: written by the release workflow from the commit messages.
  Do not edit it.

## Commands

Run tools through the `make` targets. `make help` lists them, each named
`verb-area` (`lint-php`, `test-mutation`, `dependencies-lowest`), and the
Makefile's comments say what is not obvious about one. Before finishing,
run `make checks`, plus `test-mutation` when code other than a provider or
excluder changed, and `lint-commits` once committed.

[dcd]: https://github.com/shipmonk-rnd/dead-code-detector
