<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The questionnaire used so far (and by the original scimscore.com) is SCIM IV, not SCIM III
 * (customer, 26 Nov 2026). Relabel every stored assessment; the scoring itself is unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('assessments')->where('scim_version', 'III')->update(['scim_version' => 'IV']);
        DB::table('assessments')->where('calc_version', 'iii-2026.1')->update(['calc_version' => 'iv-2026.1']);
    }

    public function down(): void
    {
        DB::table('assessments')->where('calc_version', 'iv-2026.1')->update(['calc_version' => 'iii-2026.1']);
        DB::table('assessments')->where('scim_version', 'IV')->update(['scim_version' => 'III']);
    }
};
