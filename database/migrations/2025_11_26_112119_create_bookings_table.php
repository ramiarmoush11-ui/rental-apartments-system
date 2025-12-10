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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained('apartments')->nullOnDelete();
            $table->enum('enType', ['Owner', 'Renter']);
            $table->enum('enStatus', ['Pending', 'Cancelled', 'Accepted', 'AwaitingPayment'])->nullable();//عدل المشروع كامل مع 'Cancelled'
            $table->float('rate')->nullable();
            $table->date('startTerm')->nullable();
            $table->date('endTerm')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
