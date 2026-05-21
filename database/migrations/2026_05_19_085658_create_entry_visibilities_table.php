<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('entry_visibilities', function (Blueprint $table) {
            $table->id();
            $table->string('entry_type');          // App\Models\SaleInvoice
            $table->unsignedBigInteger('entry_id');
            $table->unsignedBigInteger('company_id');
            $table->boolean('visible_to_all_company')->default(false);
            $table->json('visible_to_roles')->nullable();  // [1,2,3]
            $table->json('visible_to_users')->nullable();  // [1,5,9]
            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->unique(['entry_type', 'entry_id']);
            $table->timestamps();
        });
    }

    public function down(): void { Schema::dropIfExists('entry_visibilities'); }
};
