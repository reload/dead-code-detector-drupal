.DEFAULT_GOAL := help
.PHONY: analyse-php checks format-php help lint-actions lint-markdown lint-php lint-yaml test-php validate-composer

vendor: composer.json
	composer install --no-interaction
	@touch $@

lint-php: vendor ## Lint PHP code (PHPCS).
	vendor/bin/phpcs

analyse-php: vendor ## Analyse PHP code (PHPStan).
	vendor/bin/phpstan analyse --no-progress

test-php: vendor ## Run PHP tests (PHPUnit).
	vendor/bin/phpunit

format-php: vendor ## Format PHP code (PHPCBF).
	vendor/bin/phpcbf

validate-composer: ## Validate composer.json.
	composer validate --strict

# Ask Git which files count: markdownlint ignores global excludes (core.excludesFile).
lint-markdown: ## Lint Markdown files, like the CI "Lint" workflow does.
	@docker run --rm -v "$(CURDIR)":/workdir davidanson/markdownlint-cli2:v0.18.1 \
		$(shell git ls-files --cached --others --exclude-standard '*.md')

# GITHUB_ACTIONS/GITHUB_WORKFLOW are forwarded so yamllint's "auto" format emits
# inline annotations in CI; both are unset locally, so output stays readable.
lint-yaml: ## Lint YAML files, like the CI "Lint" workflow does.
	@docker run --rm -e GITHUB_ACTIONS -e GITHUB_WORKFLOW \
		-v "$(CURDIR)":/data -w /data tmknom/yamllint:1.35.1 --strict .

lint-actions: ## Lint GitHub Actions workflows, like the CI "Lint" workflow does.
	@docker run --rm -v "$(CURDIR)":/repo -w /repo rhysd/actionlint:1.7.7 -color

checks: validate-composer lint-php analyse-php test-php lint-markdown lint-yaml lint-actions ## Run every check CI runs.

help: ## Display a list of the public targets
# Find lines that starts with a word-character, contains a colon and then a
# doublehash (underscores are not word-characters, so this excludes private
# targets), then strip the hash and print.
	@grep -E -h "^\w.*:.*##" $(MAKEFILE_LIST) | sed -e 's/\(.*\):.*## *\(.*\)/\1|\2/' | column -s '|' -t
