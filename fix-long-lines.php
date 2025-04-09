<?php

function fixLongLines($file, $maxLength = 120)
{
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $modified = false;

    foreach ($lines as $i => &$line) {
        if (strlen($line) > $maxLength) {
            // Si c'est une chaîne de caractères
            if (preg_match('/^(\s*)(.*?)([\'"]\s*[,;]?\s*)$/', $line, $matches)) {
                $indent = $matches[1];
                $string = $matches[2];
                $ending = $matches[3];

                if (strlen($string) > $maxLength - strlen($indent) - strlen($ending)) {
                    $line = $indent . substr($string, 0, $maxLength - strlen($indent) - strlen($ending) - 3) . '...' . $ending;
                    $modified = true;
                }
            }
            // Si c'est un appel de méthode ou une concaténation
            elseif (preg_match('/^(\s*)(.+?)(->|\.)(.+)$/', $line, $matches)) {
                $indent = $matches[1];
                $object = $matches[2];
                $operator = $matches[3];
                $method = $matches[4];

                if (strlen($line) > $maxLength) {
                    $line = $indent . $object . "\n" . $indent . "    " . $operator . $method;
                    $modified = true;
                }
            }
            // Si c'est une assignation
            elseif (preg_match('/^(\s*)(.+?)(=)(.+)$/', $line, $matches)) {
                $indent = $matches[1];
                $var = $matches[2];
                $equals = $matches[3];
                $value = $matches[4];

                if (strlen($line) > $maxLength) {
                    $line = $indent . $var . "\n" . $indent . "    " . $equals . " " . $value;
                    $modified = true;
                }
            }
        }
    }

    if ($modified) {
        file_put_contents($file, implode("\n", $lines));
        echo "Fixed: $file\n";
    }
}

function processDirectory($dir)
{
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($files as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            fixLongLines($file->getPathname());
        }
    }
}

processDirectory(__DIR__ . '/src');
