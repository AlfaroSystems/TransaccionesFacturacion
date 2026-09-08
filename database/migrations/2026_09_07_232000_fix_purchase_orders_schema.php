<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Fix purchase_orders table
        if (Schema::hasTable('purchase_orders')) {
            if (Schema::hasColumn('purchase_orders', 'id') && !Schema::hasColumn('purchase_orders', 'id_purchase_order')) {
                DB::statement('ALTER TABLE purchase_orders RENAME COLUMN id TO id_purchase_order;');
            }
            
            Schema::table('purchase_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_orders', 'uuid')) {
                    $table->uuid('uuid')->nullable()->unique();
                }
                if (!Schema::hasColumn('purchase_orders', 'purchase_order_code')) {
                    $table->string('purchase_order_code', 50)->nullable()->unique();
                }
                if (!Schema::hasColumn('purchase_orders', 'id_supplier')) {
                    $table->foreignId('id_supplier')->nullable()->constrained('suppliers', 'id_supplier')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('purchase_orders', 'id_branch')) {
                    $table->foreignId('id_branch')->nullable()->constrained('branches', 'id')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchase_orders', 'id_warehouse')) {
                    $table->foreignId('id_warehouse')->nullable()->constrained('warehouses', 'id')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchase_orders', 'id_purchase_quotation')) {
                    $table->foreignId('id_purchase_quotation')->nullable()->constrained('purchase_quotations', 'id_purchase_quotation')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchase_orders', 'id_user')) {
                    $table->foreignId('id_user')->nullable()->constrained('users', 'id')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchase_orders', 'order_date')) {
                    $table->dateTime('order_date')->nullable();
                }
                if (!Schema::hasColumn('purchase_orders', 'expected_date')) {
                    $table->dateTime('expected_date')->nullable();
                }
                if (!Schema::hasColumn('purchase_orders', 'currency')) {
                    $table->string('currency', 3)->default('USD');
                }
                if (!Schema::hasColumn('purchase_orders', 'payment_terms')) {
                    $table->string('payment_terms')->nullable();
                }
                if (!Schema::hasColumn('purchase_orders', 'subtotal')) {
                    $table->decimal('subtotal', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_orders', 'discount')) {
                    $table->decimal('discount', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_orders', 'tax')) {
                    $table->decimal('tax', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_orders', 'additional_expenses')) {
                    $table->decimal('additional_expenses', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_orders', 'total')) {
                    $table->decimal('total', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_orders', 'status')) {
                    $table->string('status', 20)->default('draft');
                }
                if (!Schema::hasColumn('purchase_orders', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        // 2. Fix purchase_order_details table
        if (Schema::hasTable('purchase_order_details')) {
            if (Schema::hasColumn('purchase_order_details', 'id') && !Schema::hasColumn('purchase_order_details', 'id_purchase_order_detail')) {
                DB::statement('ALTER TABLE purchase_order_details RENAME COLUMN id TO id_purchase_order_detail;');
            }
            
            Schema::table('purchase_order_details', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_details', 'id_purchase_order')) {
                    $table->foreignId('id_purchase_order')->nullable()->constrained('purchase_orders', 'id_purchase_order')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('purchase_order_details', 'id_product')) {
                    $table->foreignId('id_product')->nullable()->constrained('products', 'id')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('purchase_order_details', 'quantity')) {
                    $table->decimal('quantity', 14, 4)->default(1);
                }
                if (!Schema::hasColumn('purchase_order_details', 'id_unit')) {
                    $table->foreignId('id_unit')->nullable()->constrained('units', 'id')->nullOnDelete();
                }
                if (!Schema::hasColumn('purchase_order_details', 'unit_price')) {
                    $table->decimal('unit_price', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_order_details', 'discount')) {
                    $table->decimal('discount', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_order_details', 'subtotal')) {
                    $table->decimal('subtotal', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_order_details', 'tax_rate')) {
                    $table->decimal('tax_rate', 8, 2)->default(0);
                }
                if (!Schema::hasColumn('purchase_order_details', 'tax_amount')) {
                    $table->decimal('tax_amount', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_order_details', 'total')) {
                    $table->decimal('total', 14, 4)->default(0);
                }
                if (!Schema::hasColumn('purchase_order_details', 'notes')) {
                    $table->text('notes')->nullable();
                }
            });
        }

        // 3. Fix purchase_order_expenses table
        if (Schema::hasTable('purchase_order_expenses')) {
            if (Schema::hasColumn('purchase_order_expenses', 'id') && !Schema::hasColumn('purchase_order_expenses', 'id_purchase_order_expense')) {
                DB::statement('ALTER TABLE purchase_order_expenses RENAME COLUMN id TO id_purchase_order_expense;');
            }
            
            Schema::table('purchase_order_expenses', function (Blueprint $table) {
                if (!Schema::hasColumn('purchase_order_expenses', 'id_purchase_order')) {
                    $table->foreignId('id_purchase_order')->nullable()->constrained('purchase_orders', 'id_purchase_order')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('purchase_order_expenses', 'id_expense_type')) {
                    $table->foreignId('id_expense_type')->nullable()->constrained('expense_types', 'id_expense_type')->cascadeOnDelete();
                }
                if (!Schema::hasColumn('purchase_order_expenses', 'description')) {
                    $table->string('description', 255)->nullable();
                }
                if (!Schema::hasColumn('purchase_order_expenses', 'amount')) {
                    $table->decimal('amount', 14, 4)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
    }
};
