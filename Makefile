.DEFAULT_GOAL := help
.PHONY: analyse-php checks dependencies-highest dependencies-lowest format-php help lint-actions lint-commits lint-markdown lint-php lint-yaml php-image test-coverage test-mutation test-php validate-composer

vendor: composer.json
	composer install --no-interaction
	@touch $@

# The dependency sets the CI "PHP" workflow installs, for the same runs locally.
dependencies-highest: ## Update to the newest dependencies composer.json allows.
	composer update --no-interaction --no-progress
	@touch vendor

dependencies-lowest: ## Update to the oldest dependencies composer.json allows.
	composer update --no-interaction --no-progress --prefer-lowest --prefer-stable
	@touch vendor

lint-php: vendor ## Lint PHP code (PHPCS).
	vendor/bin/phpcs

analyse-php: vendor ## Analyse PHP code (PHPStan).
	vendor/bin/phpstan analyse --no-progress --memory-limit=1G

test-php: vendor ## Run PHP tests (PHPUnit).
	vendor/bin/phpunit

# No official PHP image ships a coverage driver, and the host PHP may lack one
# too, so the Docker targets run in the image Dockerfile builds from it. On a
# PHP that has a driver, such as CI's, IN_DOCKER= runs them on the host instead.
PHP_IMAGE = dead-code-detector-drupal/php
IN_PHP_IMAGE = docker run --rm -v "$(CURDIR)":/app -w /app $(PHP_IMAGE)
IN_DOCKER ?= yes
PHP_RUNNER = $(if $(IN_DOCKER),$(IN_PHP_IMAGE))
IMAGE_IF_USED = $(if $(IN_DOCKER),php-image)

php-image: ## Build the image the Docker targets run in (see Dockerfile).
	@docker build --quiet --tag $(PHP_IMAGE) - < Dockerfile >/dev/null

test-coverage: vendor $(IMAGE_IF_USED) ## Measure test coverage of src (PHPUnit with pcov, in Docker).
	$(PHP_RUNNER) vendor/bin/phpunit --coverage-text

# The decision classes; AGENTS.md says why mutation testing stops there.
MUTATE ?= $(shell grep -rl '@phpstan-pure' src | sort)

# Parallel: rule-test.neon turns off shipmonk's on-disk usage cache, where parallel runs collide.
test-mutation: vendor $(IMAGE_IF_USED) ## Mutation-test the decision classes (Infection, in Docker; MUTATE= limits it to files).
	$(PHP_RUNNER) vendor/bin/infection --threads=max --show-mutations=max --no-progress $(MUTATE)

format-php: vendor ## Format PHP code (PHPCBF).
	vendor/bin/phpcbf

validate-composer: ## Validate composer.json.
	composer validate --strict

# Ask Git which files count: markdownlint ignores global excludes (core.excludesFile).
# The release workflow writes CHANGELOG.md in its own style.
lint-markdown: ## Lint Markdown files, like the CI "Lint" workflow does.
	@docker run --rm -v "$(CURDIR)":/workdir davidanson/markdownlint-cli2:v0.18.1 \
		$(shell git ls-files --cached --others --exclude-standard '*.md' ':!:CHANGELOG.md')

# Conventional Commits, which the release workflow reads to pick the version.
BASE ?= origin/main
lint-commits: ## Lint the commit messages since BASE (origin/main), like the CI "Lint" workflow does.
	@docker run --rm -v "$(CURDIR)":/app -w /app commitlint/commitlint:21.2.3 --from "$(BASE)" --to HEAD --verbose

# GITHUB_ACTIONS/GITHUB_WORKFLOW are forwarded so yamllint's "auto" format emits
# inline annotations in CI; both are unset locally, so output stays readable.
lint-yaml: ## Lint YAML files, like the CI "Lint" workflow does.
	@docker run --rm -e GITHUB_ACTIONS -e GITHUB_WORKFLOW \
		-v "$(CURDIR)":/data -w /data tmknom/yamllint:1.35.1 --strict .

lint-actions: ## Lint GitHub Actions workflows, like the CI "Lint" workflow does.
	@docker run --rm -v "$(CURDIR)":/repo -w /repo rhysd/actionlint:1.7.7 -color

checks: validate-composer lint-php analyse-php test-php lint-markdown lint-yaml lint-actions ## Run the checks CI runs on every PHP version; lint-commits needs a base.

help: ## Display a list of the public targets
# Find lines that starts with a word-character, contains a colon and then a
# doublehash (underscores are not word-characters, so this excludes private
# targets), then strip the hash and print.
	@grep -E -h "^\w.*:.*##" $(MAKEFILE_LIST) | sed -e 's/\(.*\):.*## *\(.*\)/\1|\2/' | column -s '|' -t
