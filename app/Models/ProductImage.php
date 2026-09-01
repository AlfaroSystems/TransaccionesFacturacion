<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo Eloquent para representar las imágenes asociadas a productos.
 */
class ProductImage extends Model
{
    use HasFactory;

    protected $table = 'products_images';
    protected $primaryKey = 'id_product_image';

    /**
     * Atributos asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'id_product',
        'path',
        'is_active',
    ];

    /**
     * Conversión automática de tipos de datos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Eventos de ciclo de vida del modelo.
     */
    protected static function booted(): void
    {
        // Generar UUID automático antes de crear el registro de la imagen
        static::creating(function (ProductImage $image) {
            if (empty($image->uuid)) {
                $image->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Obtener el producto al que pertenece esta imagen.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'id_product');
    }
}
