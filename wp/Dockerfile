# Royal Pet World — immutable WordPress image (ADR-003).
# Core version and wp-cli version pinned 2026-10-18; bump both deliberately together.
FROM wordpress:7.1.3-php8.3-apache

ENV WP_CLI_VERSION=2.12.0

RUN set -eu; \
    apt-get update; \
    apt-get install -y --no-install-recommends unzip less; \
    rm -rf /var/lib/apt/lists/*; \
    curl -fsSL -o /usr/local/bin/wp \
      "https://github.com/wp-cli/wp-cli/releases/download/v${WP_CLI_VERSION}/wp-cli-${WP_CLI_VERSION}.phar"; \
    chmod +x /usr/local/bin/wp

# Third-party plugins/themes, pinned in wp/plugins.manifest, from wordpress.org only.
COPY wp/plugins.manifest /tmp/plugins.manifest
RUN set -eu; \
    mkdir -p /usr/src/wordpress/wp-content/plugins /usr/src/wordpress/wp-content/themes; \
    while read -r kind slug version; do \
      case "$kind" in ''|'#'*) continue ;; esac; \
      if [ "$kind" = "plugin" ]; then \
        dest=/usr/src/wordpress/wp-content/plugins; \
        url="https://downloads.wordpress.org/plugin/${slug}.${version}.zip"; \
      else \
        dest=/usr/src/wordpress/wp-content/themes; \
        url="https://downloads.wordpress.org/theme/${slug}.${version}.zip"; \
      fi; \
      curl -fsSL -o /tmp/pkg.zip "$url"; \
      unzip -q -o /tmp/pkg.zip -d "$dest"; \
      rm -f /tmp/pkg.zip; \
    done < /tmp/plugins.manifest

# Custom code — the only parts of wp-content this repo owns.
COPY wp/wp-content/plugins/rpw-core /usr/src/wordpress/wp-content/plugins/rpw-core
COPY wp/wp-content/themes/kadence-child /usr/src/wordpress/wp-content/themes/kadence-child

# Only wp-content/uploads persists (see docker-compose.yml); everything else here
# is re-synced from /usr/src/wordpress by the base image's entrypoint on each boot.
