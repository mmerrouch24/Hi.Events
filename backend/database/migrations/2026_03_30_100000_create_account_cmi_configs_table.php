<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_cmi_configs', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_id');
            $table->text('store_key');
            $table->string('gateway_url')->default('https://payment.cmi.co.ma/fim/est3Dgate');
            $table->string('currency_numeric_code', 3)->default('504');
            $table->string('language', 5)->default('fr');
            $table->string('transaction_type', 32)->default('PreAuth');
            $table->string('store_type', 32)->default('3D_PAY_HOSTING');
            $table->string('hash_algorithm', 16)->default('ver3');
            $table->boolean('auto_redirect')->default(true);
            $table->boolean('is_enabled')->default(true);
            $table->string('mode', 16)->default('live');
            $table->string('bill_to_company')->nullable();
            $table->string('bill_to_street1')->nullable();
            $table->string('bill_to_city')->nullable();
            $table->string('bill_to_state_prov')->nullable();
            $table->string('bill_to_postal_code')->nullable();
            $table->string('bill_to_country', 3)->default('504');
            $table->timestamps();

            $table->unique('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_cmi_configs');
    }
};
