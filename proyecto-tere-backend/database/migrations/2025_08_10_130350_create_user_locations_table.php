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
        Schema::create('user_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->onDelete('cascade'); // si borras usuario, se borran sus ubicaciones
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->geometry('location')->nullable();
            $table->float('accuracy')->nullable()->after('longitude');
            $table->timestamp('location_updated_at')->nullable();
            $table->string('country', 100)->nullable();
            $table->string('country_code', 10)->nullable();

            $table->string('state', 100)->nullable();   
            $table->string('city', 100)->nullable();       

            $table->string('source', 20)->default('gps'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_locations');
    }
};
