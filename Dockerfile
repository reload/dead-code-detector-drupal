# Image for `make test-coverage` and `make test-mutation`: the official image
# plus a coverage driver, which no official image ships.
FROM php:8.4-cli

RUN pecl install pcov \
    && docker-php-ext-enable pcov \
    && echo 'memory_limit = 2G' > "$PHP_INI_DIR/conf.d/memory-limit.ini"
