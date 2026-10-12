<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_domains', function (Blueprint $table) {
            $table->string('local_part_pattern', 200)->nullable()->after('domain');
            $table->dropUnique(['domain']);
            $table->unique(['domain', 'local_part_pattern']);
        });
    }

    /** Patterned entries cannot be kept with a unique domain, so they are removed on rollback. */
    public function down(): void
    {
        DB::table('organization_domains')->whereNotNull('local_part_pattern')->delete();

        Schema::table('organization_domains', function (Blueprint $table) {
            $table->dropUnique(['domain', 'local_part_pattern']);
            $table->unique(['domain']);
            $table->dropColumn('local_part_pattern');
        });
    }
};
