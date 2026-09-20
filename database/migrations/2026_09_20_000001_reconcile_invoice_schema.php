<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReconcileInvoiceSchema extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'notes')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('notes')->nullable();
            });
        }

        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'enable_tax')) {
                    $table->string('enable_tax', 3)->default('no');
                }
                if (! Schema::hasColumn('users', 'tax_ratio')) {
                    $table->decimal('tax_ratio', 8, 4)->default(0);
                }
                if (! Schema::hasColumn('users', 'trn')) {
                    $table->string('trn')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        // These columns might predate this migration on an existing installation.
        // Keep customer and tax data intact when rolling back.
    }
}
