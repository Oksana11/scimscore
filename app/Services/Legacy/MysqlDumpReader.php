<?php

namespace App\Services\Legacy;

use Generator;
use RuntimeException;

/**
 * Streams rows from `INSERT INTO ... VALUES (...),(...);` statements of a mysqldump file
 * without executing any SQL. Only the requested tables are parsed; everything else
 * (users, password hashes, sessions…) is skipped unread.
 */
final class MysqlDumpReader
{
    public function __construct(private readonly string $path)
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException("Dump file not readable: {$path}");
        }
    }

    /**
     * @return Generator<int, list<string|null>>
     */
    public function rows(string $table): Generator
    {
        $prefix = 'INSERT INTO `'.$table.'` VALUES ';
        $handle = fopen($this->path, 'rb');

        try {
            while (($line = fgets($handle)) !== false) {
                if (! str_starts_with($line, $prefix)) {
                    continue;
                }
                // mysqldump writes one INSERT statement per line (extended inserts).
                yield from $this->parseValues(substr($line, strlen($prefix)));
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return Generator<int, list<string|null>>
     */
    private function parseValues(string $s): Generator
    {
        $len = strlen($s);
        $i = 0;

        while ($i < $len) {
            // seek tuple start
            while ($i < $len && $s[$i] !== '(') {
                if ($s[$i] === ';') {
                    return;
                }
                $i++;
            }
            $i++; // skip '('
            $row = [];

            while ($i < $len) {
                $c = $s[$i];

                if ($c === "'") {
                    $i++;
                    $buf = '';
                    while ($i < $len) {
                        $ch = $s[$i];
                        if ($ch === '\\' && $i + 1 < $len) {
                            $next = $s[$i + 1];
                            $buf .= match ($next) {
                                'n' => "\n", 'r' => "\r", 't' => "\t", '0' => "\0", 'Z' => "\x1A",
                                default => $next,
                            };
                            $i += 2;

                            continue;
                        }
                        if ($ch === "'") {
                            if ($i + 1 < $len && $s[$i + 1] === "'") { // '' escape
                                $buf .= "'";
                                $i += 2;

                                continue;
                            }
                            $i++;
                            break;
                        }
                        $buf .= $ch;
                        $i++;
                    }
                    $row[] = $buf;
                } else {
                    $start = $i;
                    while ($i < $len && $s[$i] !== ',' && $s[$i] !== ')') {
                        $i++;
                    }
                    $token = trim(substr($s, $start, $i - $start));
                    $row[] = strtoupper($token) === 'NULL' ? null : $token;
                }

                // after a value: ',' (next value) or ')' (end of tuple)
                while ($i < $len && $s[$i] !== ',' && $s[$i] !== ')') {
                    $i++;
                }
                if ($i >= $len) {
                    break;
                }
                if ($s[$i] === ')') {
                    $i++;
                    yield $row;
                    break;
                }
                $i++; // skip ','
            }
        }
    }
}
