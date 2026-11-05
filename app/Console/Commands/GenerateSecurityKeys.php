<?php

namespace App\Console\Commands;

use App\Support\Security\Keys;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('security:generate-keys')]
#[Description('Generate BLIND_INDEX_KEY and AUDIT_HMAC_KEY in .env (only if empty)')]
class GenerateSecurityKeys extends Command
{
    private const KEYS = ['BLIND_INDEX_KEY', 'AUDIT_HMAC_KEY'];

    public function handle(): int
    {
        $path = $this->laravel->environmentFilePath();

        if (! is_file($path)) {
            $this->error('.env not found.');

            return self::FAILURE;
        }

        $env = (string) file_get_contents($path);

        foreach (self::KEYS as $name) {
            if (preg_match('/^'.$name.'=(.+)$/m', $env, $m) && trim($m[1]) !== '') {
                // Changing an existing key would break search (blind index) or the audit chain.
                $this->line("{$name} already set — left unchanged.");

                continue;
            }

            $line = $name.'='.Keys::generate();

            $env = preg_match('/^'.$name.'=.*$/m', $env)
                ? (string) preg_replace('/^'.$name.'=.*$/m', $line, $env)
                : rtrim($env, "\n")."\n".$line."\n";

            $this->info("{$name} generated.");
        }

        file_put_contents($path, $env);

        return self::SUCCESS;
    }
}
