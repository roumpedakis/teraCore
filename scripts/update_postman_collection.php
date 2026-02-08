<?php
// scripts/update_postman_collection.php
// Usage: php update_postman_collection.php --name="Name" --method=GET --path="/users" [--auth=bearer|none] [--group="Folder"] [--tests="js file or inline script"] [--collection="postman/MyData.postman_collection.json"]

$opts = getopt('', ['name:', 'method:', 'path:', 'auth::', 'group::', 'tests::', 'collection::']);

function usage() {
    echo "Usage: php update_postman_collection.php --name=\"Name\" --method=GET --path=\"/users\" [--auth=bearer|none] [--group=\"Folder\"] [--tests=\"script.js or inline\"] [--collection=path]\n";
    exit(1);
}

if (empty($opts['name']) || empty($opts['method']) || empty($opts['path'])) {
    usage();
}

$name = $opts['name'];
$method = strtoupper($opts['method']);
$path = $opts['path'];
$auth = isset($opts['auth']) ? strtolower($opts['auth']) : null;
$group = isset($opts['group']) ? $opts['group'] : null;
$tests = isset($opts['tests']) ? $opts['tests'] : null;
$collectionFile = isset($opts['collection']) ? $opts['collection'] : 'postman/MyData.postman_collection.json';

if (!file_exists($collectionFile)) {
    fwrite(STDERR, "Collection file not found: $collectionFile\n");
    exit(2);
}

$raw = file_get_contents($collectionFile);
$collection = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "Invalid JSON in collection file: " . json_last_error_msg() . "\n");
    exit(3);
}

// Backup
@copy($collectionFile, $collectionFile . '.bak');

// Build URL object
$cleanPath = trim($path, '/');
$pathParts = $cleanPath === '' ? ['/'] : explode('/', $cleanPath);
$urlObj = [
    'raw' => '{{baseUrl}}' . ($path === '/' ? '/' : ('/' . $cleanPath)),
    'host' => ['{{baseUrl}}'],
    'path' => $pathParts
];

// Headers
$headers = [];
if ($auth === 'bearer') {
    $headers[] = ['key' => 'Authorization', 'value' => 'Bearer {{accessToken}}'];
}
$headers[] = ['key' => 'Accept', 'value' => 'application/json'];

$request = [
    'method' => $method,
    'header' => $headers,
    'url' => $urlObj
];

if (in_array($method, ['POST','PUT','PATCH'])) {
    $request['body'] = ['mode' => 'raw', 'raw' => ''];
}

$item = [
    'name' => $name,
    'request' => $request,
    'response' => []
];

// Attach tests if provided. If tests points to a file, include its contents as inline script
if ($tests) {
    $script = $tests;
    if (file_exists($tests)) {
        $script = file_get_contents($tests);
    }
    $item['event'] = [[
        'listen' => 'test',
        'script' => ['exec' => array_map("rtrim", explode("\n", $script)), 'type' => 'text/javascript']
    ]];
}

// If group provided, try to find or create folder
if ($group) {
    $found = false;
    foreach ($collection['item'] as &$colItem) {
        if (isset($colItem['name']) && $colItem['name'] === $group && isset($colItem['item']) && is_array($colItem['item'])) {
            $colItem['item'][] = $item;
            $found = true;
            break;
        }
    }
    unset($colItem);
    if (!$found) {
        // create folder
        $collection['item'][] = ['name' => $group, 'item' => [$item]];
    }
} else {
    $collection['item'][] = $item;
}

// Write back
$newJson = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($newJson === false) {
    fwrite(STDERR, "Failed to encode collection JSON: " . json_last_error_msg() . "\n");
    exit(4);
}
file_put_contents($collectionFile, $newJson);

echo "Added endpoint '$name' ($method $path) to $collectionFile\n";
exit(0);
