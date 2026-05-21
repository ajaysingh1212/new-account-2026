<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('user_type', ['super_admin', 'admin', 'user'])->default('user')->after('email');
            $table->string('profile_pic')->nullable()->after('user_type');
            $table->string('background_pic')->nullable()->after('profile_pic');
            $table->string('phone', 20)->nullable()->after('background_pic');
            $table->text('address')->nullable()->after('phone');
            $table->string('facebook')->nullable()->after('address');
            $table->string('twitter')->nullable()->after('facebook');
            $table->string('linkedin')->nullable()->after('twitter');
            $table->string('instagram')->nullable()->after('linkedin');
            $table->string('website')->nullable()->after('instagram');
            $table->unsignedBigInteger('current_company_id')->nullable()->after('website');
            $table->boolean('is_active')->default(true)->after('current_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'user_type','profile_pic','background_pic','phone','address',
                'facebook','twitter','linkedin','instagram','website',
                'current_company_id','is_active'
            ]);
        });
    }
};
