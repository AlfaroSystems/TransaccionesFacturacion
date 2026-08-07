<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{


    public function up(): void
    {

        Schema::create('supplier_contacts', function(Blueprint $table){


            $table->id('id_contact');


            $table->unsignedBigInteger('id_supplier');


            $table->string('full_name');


            $table->string('phone');


            $table->string('email')->nullable();


            $table->timestamps();



            $table->foreign('id_supplier')

                ->references('id_supplier')

                ->on('suppliers')

                ->onDelete('cascade');


        });

    }



    public function down(): void
    {

        Schema::dropIfExists('supplier_contacts');

    }


};
