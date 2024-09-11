<?php

namespace App\Repositories;

use App\Models\FileUpload;

class FileUploadRepository
{
    public function storeFile(array $data)
    {
        return FileUpload::create($data);
    }

    public function updateFileById(int $id, array $data)
    {
        return FileUpload::whereId($id)->update($data);
    }

    public function getFileById(int $id): ?FileUpload
    {
        return FileUpload::whereId($id)->first();
    }
}
