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
        Schema::create('notifactions', function (Blueprint $table) {
            $table->id();
            $table->string("msg");
            $table->text('send_to')->comment("a quienes se le enviara, si le ponemos -1 es a todos");
            $table->text("seen_by")->comment("listado de quienes ya vieron la notificación, visto por");
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifactions');
    }
};