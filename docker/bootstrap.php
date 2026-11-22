<?php
declare(strict_types=1);

use APP\install\Install;
use PKP\cliTool\CommandLineTool;
use PKP\config\ConfigParser;

chdir('/var/www/html');
require_once 'lib/pkp/classes/config/ConfigParser.php';

// Apply only the team's settings. Keep installed, app_key and journal settings.
function writeSettings(array $settings): void
{
    $path = 'config.inc.php';
    $lines = file($path);
    if ($lines === false) {
        throw new RuntimeException('Cannot read OJS configuration');
    }
    $output = '';
    $section = '';
    foreach ($lines as $line) {
        if (preg_match('/^\s*\[([\w]+)\]/', $line, $match)) {
            $section = $match[1];
            $output .= $line;
            foreach ($settings[$section] ?? [] as $key => $value) {
                if (strpbrk($value, "\r\n\0") !== false) {
                    throw new RuntimeException("Invalid multiline value for {$key}");
                }
                $output .= $key . ' = "' . addslashes($value) . '"' . "\n";
            }
        } elseif (preg_match('/^\s*(\w+)\s*=/', $line, $match) && isset($settings[$section][$match[1]])) {
            continue;
        } else {
            $output .= $line;
        }
    }
    if (file_put_contents($path, $output, LOCK_EX) !== strlen($output)) {
        throw new RuntimeException('Cannot save OJS configuration');
    }
}

function registerPlugins(mysqli $connection, array $plugins): void
{
    if (!$plugins) {
        return;
    }

    $registered = $connection->prepare('SELECT 1 FROM versions WHERE product_type = ? AND product = ? AND `current` = 1 LIMIT 1');
    foreach ($plugins as $path) {
        [$category, $name] = explode('/', $path, 2);
        $type = 'plugins.' . $category;
        $registered->execute([$type, $name]);
        if ($registered->get_result()->fetch_row()) {
            continue;
        }

        $descriptor = "plugins/{$path}/version.xml";
        $version = simplexml_load_file($descriptor, options: LIBXML_NONET);
        if (!$version || (string)$version->application !== $name || (string)$version->type !== $type) {
            throw new RuntimeException("Plugin name and category must match {$descriptor}");
        }

        $installer = proc_open(
            [PHP_BINARY, 'lib/pkp/tools/installPluginVersion.php', $descriptor],
            [STDIN, STDOUT, STDERR],
            $pipes,
        );
        if (!is_resource($installer) || proc_close($installer) !== 0) {
            throw new RuntimeException("Could not register OJS plugin {$path}");
        }
        $registered->execute([$type, $name]);
        if (!$registered->get_result()->fetch_row()) {
            throw new RuntimeException("OJS plugin {$path} has no registered version");
        }
        echo "Registered OJS plugin {$path}.\n";
    }
}

$plugins = preg_split('/\s+/', trim(getenv('VESTNIK_PLUGINS') ?: ''), -1, PREG_SPLIT_NO_EMPTY);

$url = getenv('OJS_BASE_URL');
$host = parse_url($url, PHP_URL_HOST);
if (!$host || !in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)) {
    throw new RuntimeException('Set OJS_BASE_URL to an absolute HTTP(S) URL');
}

$computed = [
    'OJS_ALLOWED_HOSTS' => json_encode([$host], JSON_THROW_ON_ERROR),
    'OJS_MAIL_DRIVER' => getenv('SMTP_HOST') ? 'smtp' : 'log',
    'SMTP_PORT' => getenv('SMTP_PORT') ?: '25',
];
$template = parse_ini_file(__DIR__ . '/config.inc.php.template', true, INI_SCANNER_RAW);
if ($template === false) {
    throw new RuntimeException('Cannot read the configuration template');
}
$settings = [];
foreach ($template as $section => $fields) {
    foreach ($fields as $key => $token) {
        if (array_key_exists($token, $computed)) {
            $value = $computed[$token];
        } elseif (preg_match('/^(OJS|SMTP)_/', $token)) {
            $value = getenv($token);
            if ($value === false) {
                throw new RuntimeException("Missing environment variable {$token}");
            }
        } else {
            $value = $token;
        }
        $settings[$section][$key] = $value;
    }
}

$db = $settings['database'];
$connection = new mysqli($db['host'], $db['username'], $db['password'], $db['name'], (int)$db['port']);
$locked = $connection->query("SELECT GET_LOCK(CONCAT('vestnik:', LEFT(SHA2(DATABASE(), 256), 48)), 60)")->fetch_row()[0];
if ((int)$locked !== 1) {
    throw new RuntimeException('Another OJS installation is in progress');
}
$config = ConfigParser::readConfig('config.inc.php');
if (!$config) {
    throw new RuntimeException('Cannot read OJS configuration');
}
if ($config['general']['installed'] ?? false) {
    // A lost database volume must not silently turn an installed site into a new one.
    if (!$connection->query('SELECT 1 FROM site LIMIT 1')->num_rows) {
        throw new RuntimeException('Missing OJS site data. Check the database volume.');
    }
    writeSettings($settings);
    registerPlugins($connection, $plugins);
    echo "Existing OJS installation is ready.\n";
    exit(0);
}
if ($connection->query('SHOW TABLES')->num_rows > 0) {
    throw new RuntimeException('OJS is marked uninstalled but the database contains tables. Check the database and app volumes before retrying.');
}
$password = getenv('OJS_ADMIN_PASSWORD');
$username = getenv('OJS_ADMIN_USER');
$email = getenv('OJS_ADMIN_EMAIL');
if (!$password || strlen($password) < 8 || !preg_match('/^[a-z0-9][a-z0-9_-]*$/iD', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new RuntimeException('Check OJS_ADMIN_USER, OJS_ADMIN_EMAIL and OJS_ADMIN_PASSWORD (at least 8 characters) in .env');
}
if (empty($config['general']['app_key'])) {
    $settings['general']['app_key'] = 'base64:' . base64_encode(random_bytes(32));
}
$settings['security'] = ['salt' => bin2hex(random_bytes(32)), 'api_key_secret' => bin2hex(random_bytes(32))];
writeSettings($settings);
unset($settings['general']['app_key']); // The installer generates the permanent key.

require 'tools/bootstrap.php';
new CommandLineTool([]); // Initialise the CLI request and page router as in tools/install.php.
$installer = new Install([
    'locale' => 'ru', 'additionalLocales' => ['en'], 'timeZone' => 'Asia/Novosibirsk',
    'filesDir' => '/var/www/files', 'enableBeacon' => false, 'oaiRepositoryId' => 'ojs.' . $host,
    'adminUsername' => $username, 'adminEmail' => $email, 'adminPassword' => $password,
    'databaseDriver' => 'mysqli', 'databaseHost' => $db['host'], 'databasePort' => (int)$db['port'],
    'databaseName' => $db['name'], 'databaseUsername' => $db['username'], 'databasePassword' => $db['password'],
]);
if (!$installer->execute() || !$installer->wroteConfig()) {
    throw new RuntimeException('OJS installation failed. Check container logs and database state.');
}
// The installer writes a URL based on its CLI request. Restore the configured URL.
writeSettings($settings);
registerPlugins($connection, $plugins);
echo "OJS installed. Sign in with the administrator account from .env.\n";
