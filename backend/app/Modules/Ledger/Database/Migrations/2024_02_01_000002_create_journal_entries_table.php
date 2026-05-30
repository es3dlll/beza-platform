<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('reference_type')->nullable();
            $table->string('reference_id')->nullable();
            $table->text('description');
            $table->text('description_ar')->nullable();
            $table->date('entry_date');
            $table->string('currency', 3)->default('SYP');
            $table->bigInteger('total_debit')->default(0);
            $table->bigInteger('total_credit')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->timestamp('posted_at')->nullable();
            $table->text('metadata')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed'])->default('draft');
            $table->ulid('reversal_of')->nullable();
            $table->ulid('created_by')->nullable();
            $table->ulid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('reversal_of')
                ->references('id')
                ->on('journal_entries')
                ->nullOnDelete();

            $table->index(['reference_type', 'reference_id']);
            $table->index('entry_date');
            $table->index('status');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
