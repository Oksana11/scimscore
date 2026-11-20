<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Content pages edited in the admin panel. Body is Markdown; raw HTML is stripped on render.
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->string('title', 150);
            $table->longText('body');
            $table->string('version', 32)->nullable(); // Terms of Use only: users re-accept when it changes
            $table->boolean('show_in_header')->default(false);
            $table->boolean('show_in_footer')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_published')->default(true);
            $table->boolean('is_system')->default(false); // cannot be deleted, slug fixed
            $table->foreignId('updated_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();
        DB::table('pages')->insert([
            [
                'slug' => 'about',
                'title' => 'About Us',
                'body' => "## Web Applications of the Spinal Cord Independence Measure\n\n"
                    .'The Spinal Cord Independence Measure (SCIM) was developed at the Loewenstein Rehabilitation Medical Center (LRMC). '
                    .'Use of any of its versions, in any language, is free for clinical and academic purposes. '
                    ."If used for profit, including clinical trials sponsored by a commercial firm, it requires the written agreement of LRMC and it is subject to a fee.\n\n"
                    ."This website offers SCIM applications at no charge. A fee may be charged for certain SCIM applications in the future.\n\n"
                    ."The application currently available is calculation of the SCIM Grade (A-H). Enter the scores for the various SCIM tasks and click the SCIM Grade button.\n\n"
                    .'Check back with the SCIMSCORE site from time to time for additional applications and for valuable information for spinal cord injured patients and their caregivers.',
                'version' => null,
                'show_in_header' => true,
                'show_in_footer' => true,
                'sort' => 10,
                'is_published' => true,
                'is_system' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'terms',
                'title' => 'Terms of Use',
                'body' => 'The data entered into the system is stored anonymously and used for research, statistics and system improvement. '
                    .'Do not enter any identifying details of patients (names, ID numbers, phone numbers, addresses or emails).',
                'version' => (string) config('scim.terms_version'),
                'show_in_header' => false,
                'show_in_footer' => true,
                'sort' => 20,
                'is_published' => true,
                'is_system' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
