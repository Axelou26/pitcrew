<?php

function fixLineLength($file, $maxLength = 120)
{
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $modified = false;

    foreach ($lines as $i => $line) {
        if (strlen($line) > $maxLength) {
            // Ignore les commentaires de documentation et les fixtures
            if (preg_match('/^\s*\*/', $line) || strpos($file, 'DataFixtures') !== false) {
                continue;
            }

            // Traitement des appels de méthodes chaînés
            if (preg_match('/->/', $line)) {
                $indent = preg_match('/^(\s+)/', $line, $matches) ? strlen($matches[1]) : 0;
                $parts = explode('->', $line);
                $newLine = trim($parts[0]);
                foreach (array_slice($parts, 1) as $part) {
                    $newLine .= "\n" . str_repeat(' ', $indent + 4) . '->' . $part;
                }
                $lines[$i] = $newLine;
                $modified = true;
                continue;
            }

            // Traitement des déclarations de fonction avec plusieurs paramètres
            if (preg_match('/^(\s*)(public|private|protected)?\s*function\s+\w+\s*\((.*)\)/', $line, $matches)) {
                $indent = strlen($matches[1]);
                $funcDecl = $matches[1] . ($matches[2] ? $matches[2] . ' ' : '') . 'function ' . trim(explode('(', $matches[0])[1]);
                $params = array_map('trim', explode(',', $matches[3]));

                if (count($params) > 1) {
                    $newLine = $funcDecl . "(\n";
                    foreach ($params as $j => $param) {
                        $newLine .= str_repeat(' ', $indent + 4) . trim($param);
                        if ($j < count($params) - 1) {
                            $newLine .= ",\n";
                        }
                    }
                    $newLine .= "\n" . str_repeat(' ', $indent) . ")";
                    $lines[$i] = $newLine;
                    $modified = true;
                    continue;
                }
            }

            // Traitement des conditions if/elseif longues
            if (preg_match('/^(\s*)(if|elseif)\s*\((.*)\)/', $line, $matches)) {
                $indent = strlen($matches[1]);
                $conditions = explode('&&', $matches[3]);

                if (count($conditions) > 1 || strlen($line) > $maxLength) {
                    $newLine = $matches[1] . $matches[2] . " (\n";
                    foreach ($conditions as $j => $condition) {
                        $condition = trim($condition);
                        $newLine .= str_repeat(' ', $indent + 4) . $condition;
                        if ($j < count($conditions) - 1) {
                            $newLine .= " &&\n";
                        }
                    }
                    $newLine .= "\n" . str_repeat(' ', $indent) . ")";
                    $lines[$i] = $newLine;
                    $modified = true;
                    continue;
                }
            }

            // Traitement des chaînes de caractères longues
            if (preg_match('/^(\s*).*?(\'|")(.*?)(\2)/', $line, $matches)) {
                $indent = strlen($matches[1]);
                $string = $matches[3];

                if (strlen($string) > $maxLength - $indent - 10) {
                    $words = explode(' ', $string);
                    $newLine = $matches[1] . $matches[2];
                    $currentLine = '';

                    foreach ($words as $word) {
                        if (strlen($currentLine . $word) > $maxLength - $indent - 10) {
                            $newLine .= $currentLine . "' .\n" .
                                      str_repeat(' ', $indent + 4) . "'";
                            $currentLine = $word . ' ';
                        } else {
                            $currentLine .= $word . ' ';
                        }
                    }

                    $newLine .= $currentLine . $matches[2];
                    $lines[$i] = $newLine;
                    $modified = true;
                    continue;
                }
            }

            // Traitement des tableaux
            if (preg_match('/^(\s*)(.*?)\[(.*)\]/', $line, $matches)) {
                $indent = strlen($matches[1]);
                $arrayName = $matches[2];
                $items = array_map('trim', explode(',', $matches[3]));

                if (count($items) > 1 || strlen($line) > $maxLength) {
                    $newLine = $matches[1] . $arrayName . "[\n";
                    foreach ($items as $j => $item) {
                        $newLine .= str_repeat(' ', $indent + 4) . trim($item);
                        if ($j < count($items) - 1) {
                            $newLine .= ",\n";
                        }
                    }
                    $newLine .= "\n" . str_repeat(' ', $indent) . "]";
                    $lines[$i] = $newLine;
                    $modified = true;
                }
            }
        }
    }

    if ($modified) {
        file_put_contents($file, implode("\n", $lines));
        echo "Fixed: $file\n";
        return true;
    }
    return false;
}

// Récupérer la liste de tous les fichiers PHP dans src/
$files = [];
$directory = new RecursiveDirectoryIterator('src');
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($regex as $file) {
    $files[] = $file[0];
}

$fixedFiles = 0;
foreach ($files as $file) {
    if (fixLineLength($file)) {
        $fixedFiles++;
    }
}

echo "\nFixed $fixedFiles files!\n";
