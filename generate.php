<?php
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, $severity, $severity, $file, $line);
});
if (!shell_exec('which find')) {
    throw new Exception('the `find` command is missing (https://ss64.com/bash/find.html)');
}

if (!(isset($argv[1]) && is_dir($argv[1]))) {
    echo "Usage" . PHP_EOL;
    echo "php generate.php /path/to/node_modules/" . PHP_EOL;
    exit(1);
}
$nodeModulesDirectory = $argv[1];

/**
 * Get every package.json within the node_modules directory. Some of these will be test files or that kind of thing
 */
exec("find $nodeModulesDirectory -name 'package.json'", $output, $return);
if ($return != 0) {
    echo "Error finding package.json files" . PHP_EOL;
    echo implode(PHP_EOL, $output);
    exit($return);
}

/**
 * Build an associative array mapping the directory hierarchy of the package.json files we've found
 */
$directories = [];
foreach ($output as $packageJsonFile) {
    $deps = &$directories;
    foreach (array_filter(explode(DIRECTORY_SEPARATOR, $packageJsonFile)) as $directory) {
        if ($directory === 'package.json') {
            continue;
        }
        if (!isset($deps[$directory])) {
            $deps[$directory] = [];
        }
        $deps = &$deps[$directory];
    }
}

/**
 * Get top level package.json file for each dir path
 * this means we avoid a package having tests/package.json or tools/package.json etc
 */
foreach ($output as $packageJsonFile) {
    $deps = &$directories;
    foreach (array_filter(explode(DIRECTORY_SEPARATOR, $packageJsonFile)) as $directory) {
        if ($directory === 'package.json') {
            if (is_array($deps) && empty($deps)) {
                $deps = $packageJsonFile; // Leaf node, add our package.json
            } else {
                $deps = $packageJsonFile; // Cut off this part of the tree, we have a package.json at a higher level
            }
            continue;
        }
        if (is_string($deps)) {
            continue; // We have already found a higher priority package.json file
        }
        $deps = &$deps[$directory];
    }
}

/**
 * Print out the json name=>version mapping for placing into your package.json
 */
$dependencies = [];
foreach (deepFlatten($directories) as $packageJsonFile) {
    $packageJsonData = json_decode(file_get_contents($packageJsonFile), true);
    $dependencies[$packageJsonData['name']] = $packageJsonData['version'];
}
ksort($dependencies);
echo json_encode($dependencies, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . PHP_EOL;


/**
 * @link https://stackoverflow.com/a/62569447/4354325
 *
 * @param $items
 * @return array
 */
function deepFlatten($items)
{
    $result = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            $result[] = $item;
        } else {
            $result = array_merge($result, deepFlatten($item));
        }
    }
    return $result;
}
