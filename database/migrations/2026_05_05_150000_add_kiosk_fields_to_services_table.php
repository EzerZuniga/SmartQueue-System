<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            if (! Schema::hasColumn('services', 'ask_name')) {
                $table->boolean('ask_name')->default(false)->after('ask_document');
            }

            if (! Schema::hasColumn('services', 'name_required')) {
                $table->boolean('name_required')->default(false)->after('ask_name');
            }

            if (! Schema::hasColumn('services', 'ask_email')) {
                $table->boolean('ask_email')->default(false)->after('name_required');
            }

            if (! Schema::hasColumn('services', 'ask_phone')) {
                $table->boolean('ask_phone')->default(false)->after('ask_email');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $columns = array_filter(['ask_name', 'name_required', 'ask_email', 'ask_phone'], fn ($column) => Schema::hasColumn('services', $column));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

