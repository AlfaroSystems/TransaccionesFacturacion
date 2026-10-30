<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Purchase;
use App\Models\Retaceo;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DashboardController extends Controller
{
    public function index()
    {
        // Los documentos se filtran por la sucursal del usuario (BranchScope); los usuarios, con accessible()
        $userCount = User::accessible()->count();

        $roleCount = Role::count();
        $productCount = Product::count();
        $supplierCount = Supplier::count();

        $purchaseOrderCount = PurchaseOrder::count();
        $purchaseRequestCount = PurchaseRequest::count();
        $purchaseCount = Purchase::count();
        $retaceoCount = Retaceo::count();

        // Datos para el gráfico de Rendimiento Mensual (últimos 6 meses); son datos de
        // órdenes de compra, así que solo se calculan para quien puede ver ese módulo
        $chartLabels = [];
        $chartData = [];

        if (Gate::allows('purchase_orders.ver')) {
            for ($i = 5; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $chartLabels[] = strtolower($date->locale('es')->isoFormat('MMMM'));

                $chartData[] = PurchaseOrder::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
            }
        }

        return view('dashboard', compact(
            'userCount',
            'roleCount',
            'productCount',
            'supplierCount',
            'purchaseOrderCount',
            'purchaseRequestCount',
            'purchaseCount',
            'retaceoCount',
            'chartLabels',
            'chartData'
        ));
    }
}
