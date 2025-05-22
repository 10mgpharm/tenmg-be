<?php

namespace App\Models;

use App\Traits\FileUploadTrait;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FileUpload extends Model
{
    use FileUploadTrait, HasFactory, SoftDeletes;

    protected $table = 'file_uploads';

    protected $guarded = [];

    protected $appends = [
        'virtual_name',
    ];

    public function document_type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id', 'id');
    }

    public function getUrlAttribute()
    {
        $path = $this->getAttribute('path');
        if (! $path) {
            return null;
        }

        return $this->getTemporaryUrl($this->getAttribute('path'));
    }

    protected function virtualName(): Attribute
    {
        $_this = $this;

        return new Attribute(
            get: function () use ($_this) {
                $path = explode('/', $_this->path);

                return $path[count($path) - 1];
            }
        );
    }

}
