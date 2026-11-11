<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_line_items', function (Blueprint $table) {
            // Percentage, applied to (subtotal - discount). Falls back to the
            // parent document's tax_rate as a default when creating a line
            // item (see DocumentService), so that column stays meaningfully
            // used rather than orphaned by the move to per-line tax.
            $table->decimal('tax_rate', 5, 2)->default(0)->after('unit_price');
            // Flat monetary amount subtracted from this line's subtotal.
            $table->decimal('discount', 12, 2)->default(0)->after('tax_rate');
        });
    }

    public function down(): void
    {
        Schema::table('document_line_items', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'discount']);
        });
    }
};
