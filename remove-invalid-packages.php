<?php
set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, $severity, $severity, $file, $line);
});
if (!(isset($argv[1]) && is_file($argv[1]))) {
    echo "Usage" . PHP_EOL;
    echo "php remove-invalid-packages.php /path/to/package.json" . PHP_EOL;
    exit(1);
}
$packageJson = $argv[1];
chdir(dirname($packageJson));

if (!shell_exec('which npm')) {
    throw new Exception('the `npm` command is missing');
}

$packageJsonContents = explode(PHP_EOL, file_get_contents($packageJson));

echo "Working directory: " . getcwd() . PHP_EOL;
for ($i = 0; $i < 1000; $i++) {
    unset($output);
    $command = 'npm install 2>&1';
    echo $command . PHP_EOL;
    exec($command, $output, $returnCode);

    if ($returnCode === 0) {
        break;
    }

    $lines = array_filter($output, function ($line) {
        return (strpos($line, 'is not in the npm registry') !== false) || (strpos($line, 'No matching version found') !== false);
    });

    if (count($lines) !== 1) {
        echo implode(PHP_EOL, $output) . PHP_EOL;
        echo "ERROR, only expected one bad package" . PHP_EOL;
        exit(1);
    }

    // $line contains a strings like
    // - npm ERR! 404  'bs-recipes-server-includes@1.0.0' is not in the npm registry.
    // - npm ERR! notarget No matching version found for gulp.sass@1.0.0
    $line = array_pop($lines);
    list($packageToUninstall,) = explode('@', $line);
    $parts = explode(' ', $packageToUninstall);
    $packageToUninstall = end($parts);
    $packageToUninstall = ltrim($packageToUninstall, "'");

    echo "Trying to remove $packageToUninstall ... ";
    $removed = false;

    $packageJsonContents = array_filter($packageJsonContents, function ($packageJsonLine) use (&$removed, $packageToUninstall) {
        $packageToUninstall = "\"$packageToUninstall\":";
        if (substr(trim($packageJsonLine), 0, strlen($packageToUninstall)) === $packageToUninstall) {
            $removed = true;
            return false;
        }
        return true;
    });
    file_put_contents($packageJson, implode(PHP_EOL, $packageJsonContents));
    if ($removed) {
        echo "SUCCESS" . PHP_EOL;
    } else {
        echo "FAIL" . PHP_EOL;
    }
}

unset($output);
exec('npm install 2>&1', $output, $returnCode);
echo implode(PHP_EOL, $output) . PHP_EOL;
if ($returnCode != 0) {
    echo "Error running npm install" . PHP_EOL;
}
echo "DONE" . PHP_EOL;
exit($returnCode);
