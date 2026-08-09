<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'sku',
        'original_code',
        'internal_code',
        'barcode',
        'name',
        'size',
        'dimensions',
        'presentation',
        'description',
        'id_category',
        'id_sub_category',
        'purchase_unit',
        'sale_unit',
        'purchase_price',
        'sale_price',
        'stock',
        'min_stock',
        'is_active',
    ];

    /**
     * Casts de atributos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Boot del modelo para la lógica de negocio automática.
     */
    protected static function booted(): void
    {
        // Al registrar un nuevo producto (evento creating)
        static::creating(function (Product $product) {
            // 1. Generar automáticamente un UUID v4 si no fue provisto
            if (empty($product->uuid)) {
                $product->uuid = (string) Str::uuid();
            }

            // 2. Controlar y formatear el SKU
            if (empty($product->sku)) {
                $product->sku = static::generateUniqueSku();
            } else {
                $product->sku = strtoupper(trim($product->sku));
            }

            // Validar unicidad del SKU antes de guardar a nivel de modelo
            if (static::where('sku', $product->sku)->exists()) {
                throw new InvalidArgumentException("El SKU '{$product->sku}' ya se encuentra registrado.");
            }
        });

        // Al actualizar el producto (evento updating)
        static::updating(function (Product $product) {
            if ($product->isDirty('sku')) {
                $product->sku = strtoupper(trim($product->sku));

                // Validar unicidad del SKU excluyendo el registro actual
                $exists = static::where('sku', $product->sku)
                    ->where('id', '!=', $product->id)
                    ->exists();

                if ($exists) {
                    throw new InvalidArgumentException("El SKU '{$product->sku}' ya está siendo utilizado por otro producto.");
                }
            }
        });
    }

    /**
     * Generador automático de SKU único con prefijo y formato estándar.
     * Ejemplo: PRD-2026-A1B2C
     */
    public static function generateUniqueSku(string $prefix = 'PRD'): string
    {
        do {
            $sku = strtoupper($prefix . '-' . date('Y') . '-' . Str::random(5));
        } while (static::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Buscar un producto por su SKU.
     */
    public static function findBySku(string $sku): ?self
    {
        return static::where('sku', strtoupper(trim($sku)))->first();
    }

    /**
     * Buscar un producto por su UUID.
     */
    public static function findByUuid(string $uuid): ?self
    {
        return static::where('uuid', $uuid)->first();
    }

    /* =========================================================================
     *  RELACIONES CRUZADAS (ELOQUENT RELATIONSHIPS)
     * ========================================================================= */

    /**
     * Relación con Categoría (Category).
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'id_category');
    }

    /**
     * Relación con Subcategoría (SubCategory).
     */
    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(SubCategory::class, 'id_sub_category');
    }

    /**
     * Relación con Unidad de Medida para Compras (Unit).
     */
    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit');
    }

    /**
     * Relación con Unidad de Medida para Ventas (Unit).
     */
    public function saleUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'sale_unit');
    }

    /**
     * Relación con Ubicaciones en bodega (Location).
     */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'product_location')
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    /* =========================================================================
     *  SCOPES & ACCESORS
     * ========================================================================= */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock');
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock <= $this->min_stock;
    }
}
