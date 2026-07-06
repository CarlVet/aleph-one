<?php

namespace App\Console\Commands;

use App\Models\Humans;
use Illuminate\Console\Command;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptExistingHumans extends Command
{
    protected $signature = 'humans:encrypt-existing {--dry-run : Report what would change without writing}';

    protected $description = 'Encrypt existing plaintext sensitive fields on the humans table and backfill the national_id blind index. Idempotent: already-encrypted rows are skipped.';

    /**
     * Columns carrying an `encrypted` cast on the Humans model.
     *
     * @var list<string>
     */
    private const ENCRYPTED_COLUMNS = ['national_id', 'alternate_phone', 'alternate_email'];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $encrypted = 0;
        $hashed = 0;

        DB::table('humans')->orderBy('id')->lazyById(200, 'id')->each(function (object $row) use (&$encrypted, &$hashed, $dryRun): void {
            $updates = [];

            foreach (self::ENCRYPTED_COLUMNS as $column) {
                $value = $row->{$column} ?? null;

                if ($value === null || $value === '' || $this->isEncrypted($value)) {
                    continue;
                }

                $updates[$column] = Crypt::encryptString((string) $value);
                $encrypted++;
            }

            // Backfill the blind index from the plaintext national_id (whether we
            // just encrypted it this run or it was already encrypted previously).
            $plainNationalId = $this->plaintext($updates['national_id'] ?? $row->national_id ?? null);
            $expectedHash = Humans::blindIndex($plainNationalId);

            if (($row->national_id_hash ?? null) !== $expectedHash) {
                $updates['national_id_hash'] = $expectedHash;
                $hashed++;
            }

            if ($updates === [] || $dryRun) {
                return;
            }

            DB::table('humans')->where('id', $row->id)->update($updates);
        });

        $prefix = $dryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Encrypted {$encrypted} field value(s); (re)computed {$hashed} blind index hash(es).");

        return self::SUCCESS;
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }

    private function plaintext(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
