<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cmi_payments', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_cmi_config_id')->nullable()->constrained('account_cmi_configs')->nullOnDelete();
            $table->string('oid')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3);
            $table->string('status', 32)->default('INITIATED');
            $table->boolean('hash_verified')->nullable();
            $table->string('proc_return_code', 16)->nullable();
            $table->string('response', 64)->nullable();
            $table->string('auth_code', 64)->nullable();
            $table->string('trans_id', 128)->nullable();
            $table->string('md_status', 16)->nullable();
            $table->json('request_payload')->nullable();
            $table->json('raw_callback_payload')->nullable();
            $table->json('raw_return_payload')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cmi_payments');
    }
};
