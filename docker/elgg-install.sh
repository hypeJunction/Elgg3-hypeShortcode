#!/bin/bash
set -e

# Per-plugin Elgg 3.x install + activation script.
# PLUGIN_ID must be set in the container environment (passed by docker-compose
# from <plugin>/docker/.env). Only that one plugin is activated — no fleet
# activation, no plugin-order.txt, no cross-plugin side effects.

if [ -z "${PLUGIN_ID:-}" ]; then
    echo "ERROR: PLUGIN_ID environment variable is required." >&2
    echo "Set it in docker/.env before starting the stack." >&2
    exit 1
fi

echo "Waiting for MySQL..."
until php -r "new PDO('mysql:host=${ELGG_DB_HOST:-db}', '${ELGG_DB_USER:-elgg}', '${ELGG_DB_PASS:-elgg}');" 2>/dev/null; do
    sleep 1
done
echo "MySQL is ready."

cd /var/www/html

if [ ! -f /var/www/html/.elgg-installed ]; then
    echo "Installing Elgg 3.x..."

    mkdir -p elgg-config
    cat > elgg-config/settings.php <<'SETTINGS_TEMPLATE'
<?php
global $CONFIG;
if (!isset($CONFIG)) {
    $CONFIG = new \stdClass;
}
SETTINGS_TEMPLATE

    cat >> elgg-config/settings.php <<SETTINGS_VALUES
\$CONFIG->dbuser = '${ELGG_DB_USER:-elgg}';
\$CONFIG->dbpass = '${ELGG_DB_PASS:-elgg}';
\$CONFIG->dbname = '${ELGG_DB_NAME:-elgg}';
\$CONFIG->dbhost = '${ELGG_DB_HOST:-db}';
\$CONFIG->dbport = '3306';
\$CONFIG->dbprefix = 'elgg_';
\$CONFIG->dbencoding = 'utf8mb4';
\$CONFIG->dataroot = '${ELGG_DATA_ROOT:-/var/www/data/}';
\$CONFIG->wwwroot = '${ELGG_SITE_URL:-http://localhost:9588/}';
\$CONFIG->cacheroot = '${ELGG_DATA_ROOT:-/var/www/data/}cache/';
\$CONFIG->assetroot = '${ELGG_DATA_ROOT:-/var/www/data/}assets/';
SETTINGS_VALUES

    php -r "
        require_once 'vendor/autoload.php';

        \$params = [
            'dbuser' => '${ELGG_DB_USER:-elgg}',
            'dbpassword' => '${ELGG_DB_PASS:-elgg}',
            'dbname' => '${ELGG_DB_NAME:-elgg}',
            'dbhost' => '${ELGG_DB_HOST:-db}',
            'dbport' => '3306',
            'dbprefix' => 'elgg_',
            'sitename' => 'hypeShortcode Test Site',
            'siteemail' => '${ELGG_ADMIN_EMAIL:-admin@example.com}',
            'wwwroot' => '${ELGG_SITE_URL:-http://localhost:9588/}',
            'dataroot' => '${ELGG_DATA_ROOT:-/var/www/data/}',
            'displayname' => 'Admin',
            'email' => '${ELGG_ADMIN_EMAIL:-admin@example.com}',
            'username' => 'admin',
            'password' => '${ELGG_ADMIN_PASSWORD:-admin12345}',
        ];

        \$installer = new \ElggInstaller();
        \$installer->batchInstall(\$params);
        echo 'Elgg 3.x installed successfully.' . PHP_EOL;
    " 2>&1 || echo "Install completed (check for errors above)."

    echo "Activating plugin: ${PLUGIN_ID}"
    php -r "
        require_once 'vendor/autoload.php';
        \$app = \Elgg\Application::getInstance();
        \$app->bootCore();
        _elgg_services()->plugins->generateEntities();
        // In Elgg 3.x the plugin ID is taken from manifest.xml <id>, which may
        // differ in case from the directory name. Try the directory name first,
        // then scan all plugins for a case-insensitive match.
        \$plugin = elgg_get_plugin_from_id('${PLUGIN_ID}');
        if (!\$plugin) {
            foreach (elgg_get_plugins() as \$p) {
                if (strcasecmp(\$p->getID(), '${PLUGIN_ID}') === 0) {
                    \$plugin = \$p;
                    break;
                }
            }
        }
        if (!\$plugin) {
            echo 'ERROR: plugin ${PLUGIN_ID} not found at /var/www/html/mod/${PLUGIN_ID}' . PHP_EOL;
            exit(1);
        }
        echo 'Found plugin: ' . \$plugin->getID() . PHP_EOL;
        if (\$plugin->isActive()) {
            echo 'Plugin already active.' . PHP_EOL;
        } else {
            \$canActivate = \$plugin->canActivate();
            if (!\$canActivate) {
                echo 'FAILED to activate (canActivate=false): ' . \$plugin->getError() . PHP_EOL;
                exit(1);
            }
            try {
                \$result = \$plugin->activate();
                if (\$result) {
                    echo 'Plugin activated successfully.' . PHP_EOL;
                } else {
                    echo 'FAILED to activate: ' . \$plugin->getError() . PHP_EOL;
                    exit(1);
                }
            } catch (\Throwable \$e) {
                echo 'FAILED to activate: ' . \$e->getMessage() . PHP_EOL;
                exit(1);
            }
        }
    " 2>&1 || echo "Plugin activation completed (check for errors above)."

    echo "Creating PHPUnit test tables (c_i_elgg_ prefix)..."
    php -r "
        \$pdo = new PDO('mysql:host=${ELGG_DB_HOST:-db};dbname=${ELGG_DB_NAME:-elgg}', '${ELGG_DB_USER:-elgg}', '${ELGG_DB_PASS:-elgg}');
        \$stmt = \$pdo->query(\"SHOW TABLES LIKE 'elgg_%'\");
        \$tables = \$stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach (\$tables as \$table) {
            \$newTable = str_replace('elgg_', 'c_i_elgg_', \$table);
            \$pdo->exec(\"DROP TABLE IF EXISTS \$newTable\");
            \$row = \$pdo->query(\"SHOW CREATE TABLE \$table\")->fetch(PDO::FETCH_ASSOC);
            \$pdo->exec(str_replace(\$table, \$newTable, \$row['Create Table']));
        }
        foreach (['entities','metadata','private_settings','entity_relationships','config'] as \$t) {
            \$pdo->exec(\"INSERT INTO c_i_elgg_\$t SELECT * FROM elgg_\$t\");
        }
        echo 'PHPUnit test tables created.' . PHP_EOL;
    " 2>&1 || echo "Test table creation completed (check for errors above)."

    # Hand the data root over to the Apache user. The installer ran as
    # root (entrypoint context) and left every cache subdirectory
    # root-owned, which makes Phpfastcache throw IOException on the
    # first request and the site renders Elgg's "fatal error" stub.
    chown -R www-data:www-data "${ELGG_DATA_ROOT:-/var/www/data/}"
    chmod -R u+rwX,g+rX,o+rX "${ELGG_DATA_ROOT:-/var/www/data/}"

    touch /var/www/html/.elgg-installed
    echo "Elgg 3.x setup complete."
fi

echo "Starting Apache..."
exec apache2-foreground
