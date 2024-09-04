<?php

namespace App\Traits;

use App\Models\FileUpload;
use App\Services\AttachmentService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

trait FileUploadTrait
{
    protected function processFileUpload(
        AttachmentService $service,
        Request $request,
        $file_key,
        $model_id = null,
        $model_type = null,
        $path = '',
        ?FileUpload $old_attachment = null
    ) {
        $basePath = 'uploads'.$path;
        $fileId = null;
        if ($request->hasFile($file_key)) {
            $validator = Validator::make(
                [
                    $file_key => $request->input($file_key),
                ],
                [
                    $file_key => array_merge(['sometimes', 'nullable'], $service->getFileValidationRules()),
                ]
            );
            if ($validator->fails()) {
                throw new Exception("Invalid file format selected or file size is too large [$file_key]");
            }
            $uploadedFile = $request->file($file_key);
            $attachment = ! $old_attachment ? $service->saveNewUpload($uploadedFile, $model_id, $model_type, $basePath) :
                $service->saveUpdatedUpload($uploadedFile, $model_id, $model_type, $old_attachment, $basePath);
            if ($attachment) {
                $fileId = $attachment->id;
            }
        }

        return $fileId;
    }
}
