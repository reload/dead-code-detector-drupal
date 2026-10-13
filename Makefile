.DEFAULT_GOAL := help
.PHONY: analyse-php checks dependencies-highest dependencies-lowest format-php help lint-actions lint-commits lint-links lint-markdown lint-php lint-yaml php-image test-coverage test-mutation test-php validate-composer

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
RUN_IN = docker compose --progress quiet run --rm
IN_DOCKER ?= yes
PHP_RUNNER = $(if $(IN_DOCKER),$(RUN_IN) php)
IMAGE_IF_USED = $(if $(IN_DOCKER),php-image)

php-image: ## Build the image the Docker targets run in (see Dockerfile).
	@docker compose --progress quiet build php

test-coverage: vendor $(IMAGE_IF_USED) ## Measure test coverage of src (PHPUnit with pcov, in Docker).
	$(PHP_RUNNER) vendor/bin/phpunit --coverage-text

# The decision classes; AGENTS.md says why mutation testing stops there.
MUTATE ?= $(shell grep -rl '@phpstan-pure' src | sort)

# Parallel: rule-test.neon turns off dead-code-detector's on-disk usage cache, where parallel runs collide.
test-mutation: vendor $(IMAGE_IF_USED) ## Mutation-test the decision classes (Infection, in Docker; MUTATE= limits it to files).
	$(PHP_RUNNER) vendor/bin/infection --threads=max --show-mutations=max --log-verbosity=all --no-progress $(MUTATE)

format-php: vendor ## Format PHP code (PHPCBF).
	vendor/bin/phpcbf

validate-composer: ## Validate composer.json.
	composer validate --strict

# Ask Git which files count: markdownlint ignores global excludes (core.excludesFile).
# The release workflow writes CHANGELOG.md in its own style.
lint-markdown: ## Lint Markdown files, like the CI "Lint" workflow does.
	@$(RUN_IN) markdownlint \
		$(shell git ls-files --cached --others --exclude-standard '*.md' ':!:CHANGELOG.md')

# Every link in the Markdown, including the targets of fragments, resolves.
lint-links: ## Check the links in Markdown files, like the CI "Lint" workflow does.
	@$(RUN_IN) lychee \
		--no-progress --include-fragments --max-concurrency 4 \
		$(shell git ls-files --cached --others --exclude-standard '*.md' ':!:CHANGELOG.md')

# Conventional Commits, which the release workflow reads to pick the version.
BASE ?= origin/main
lint-commits: ## Lint the commit messages since BASE (origin/main), like the CI "Lint" workflow does.
	@$(RUN_IN) commitlint --from "$(BASE)" --to HEAD --verbose

lint-yaml: ## Lint YAML files, like the CI "Lint" workflow does.
	@$(RUN_IN) yamllint --strict .

lint-actions: ## Lint GitHub Actions workflows, like the CI "Lint" workflow does.
	@$(RUN_IN) actionlint -color

checks: validate-composer lint-php analyse-php test-php lint-markdown lint-links lint-yaml lint-actions ## Run the checks CI runs on every PHP version; lint-commits needs a base.

help: ## Display a list of the public targets
# Find lines that starts with a word-character, contains a colon and then a
# doublehash (underscores are not word-characters, so this excludes private
# targets), then strip the hash and print.
	@grep -E -h "^\w.*:.*##" $(MAKEFILE_LIST) | sed -e 's/\(.*\):.*## *\(.*\)/\1|\2/' | column -s '|' -t
