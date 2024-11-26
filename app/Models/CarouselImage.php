<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CarouselImage extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'carousel_images';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'image_file_id',
        'created_by_id',
        'updated_by_id',
    ];

    /**
     * Get the image file associated with the product.
     */
    public function image()
    {
        return $this->belongsTo(FileUpload::class, 'image_file_id');
    }

    /**
     * Get the URL of the image.
     */
    public function imageUrl(): Attribute
    {
        return new Attribute(
            get: fn () => $this->image?->url
        );
    }
}
