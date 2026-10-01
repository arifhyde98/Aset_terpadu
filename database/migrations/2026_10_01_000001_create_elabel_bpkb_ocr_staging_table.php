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
        if (!Schema::hasTable('elabel_bpkb_ocr_staging')) {
            Schema::create('elabel_bpkb_ocr_staging', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('bpkb_id')->index();
                $table->string('pdf_filename', 255)->nullable();
                $table->json('raw_extracted_json')->nullable();
                $table->json('diff_fields_json')->nullable();
                $table->enum('status', ['pending', 'applied', 'rejected', 'failed'])->default('pending')->index();
                $table->text('error_message')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();

                $table->foreign('bpkb_id')
                    ->references('id')
                    ->on('elabel_bpkb')
                    ->onDelete('cascade')
                    ->onUpdate('cascade');

                $table->foreign('reviewed_by')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null')
                    ->onUpdate('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elabel_bpkb_ocr_staging');
    }
};
