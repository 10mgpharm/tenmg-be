<?php

namespace App\Services;

use App\Models\FileUpload;
use BookStack\Exceptions\FileUploadException;
use Exception;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Filesystem\Filesystem as Storage;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage as FacadeStorage;
use Illuminate\Support\Str;
use League\Flysystem\CorruptedPathDetected;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AttachmentService
{
    /**
     * FIleService constructor.
     */
    public function __construct(private FilesystemManager $fileSystem) {}

    /**
     * Get the storage that will be used for storing files.
     */
    protected function getStorageDisk(): Storage
    {
        return $this->fileSystem->disk($this->getStorageDiskName());
    }

    /**
     * Get the name of the storage disk to use.
     */
    protected function getStorageDiskName(): string
    {
        $storageType = config('filesystems.attachments');

        return $storageType;
    }

    /**
     * Change the originally provided path to fit any disk-specific requirements.
     * This also ensures the path is kept to the expected root folders.
     */
    protected function adjustPathForStorageDisk(string $path): string
    {
        return $path;
        // TODO: update implementation
        // $path = static::normalizePath(str_replace('uploads/files/', '', $path));
        // if ($this->getStorageDiskName() === 'local_secure_attachments') {
        //     return $path;
        // }
        // return 'uploads/files/' . $path;
    }

    /**
     * Get an attachment from storage.
     *
     * @throws FileNotFoundException
     */
    public function getFIleFromStorage(FileUpload $attachment): string
    {
        return $this->getStorageDisk()->get($this->adjustPathForStorageDisk($attachment->path));
    }

    /**
     * Store a new attachment upon user upload.
     *
     * @throws FileUploadException
     */
    public function saveNewUpload(UploadedFile $uploadedFile, $model_id = null, $model_type = null, $basePath = 'uploads/files'): FileUpload
    {
        $attachmentName = md5(date('YmdHis')).''.$uploadedFile->getClientOriginalName();
        $attachmentPath = $this->putFileInStorage($uploadedFile, $basePath);

        return FileUpload::forceCreate([
            'url' => $this->getStorageDiskName() == 'public' ? url(FacadeStorage::url($attachmentPath)) : '',
            'path' => $attachmentPath,
            'mime_type' => $uploadedFile->getMimeType(),
            'model_id' => $model_id,
            'model_type' => $model_type,
            'name' => $attachmentName,
            'extension' => $uploadedFile->getClientOriginalExtension(),
            'size' => $uploadedFile->getSize(),
        ]);
    }

    /**
     * Store an upload, saving to a file and deleting any existing uploads
     * attached to that file.
     *
     * @throws FileUploadException
     */
    public function saveUpdatedUpload(UploadedFile $uploadedFile, $model_id, $model_type, FileUpload $attachment, $basePath = 'uploads/files'): FileUpload
    {
        if (! $attachment->external) {
            $this->deleteFileInStorage($attachment);
        }

        $attachmentName = $uploadedFile->getClientOriginalName();
        $attachmentPath = $this->putFileInStorage($uploadedFile, $basePath);

        $attachment->url = $this->getStorageDiskName() == 'public' ? url(FacadeStorage::url($attachmentPath)) : '';
        $attachment->path = $attachmentPath;
        $attachment->mime_type = $uploadedFile->getMimeType();
        $attachment->extension = $uploadedFile->getClientOriginalExtension();
        $attachment->model_id = $model_id;
        $attachment->model_type = $model_type;
        $attachment->name = $attachmentName;
        $attachment->size = $uploadedFile->getSize();
        $attachment->save();

        return $attachment;
    }

    /**
     * Update the details of a file.
     */
    public function updateFile(FileUpload $attachment, array $requestData): FileUpload
    {
        $attachment->url = $requestData['url'];
        $attachment->path = $requestData['path'];
        if (! empty($link)) {
            if (! $attachment->external) {
                $this->deleteFileInStorage($attachment);
            }
        }
        $attachment->save();

        return $attachment->refresh();
    }

    /**
     * Delete a File from the database and storage.
     *
     * @throws Exception
     */
    public function deleteFile(FileUpload $attachment)
    {
        if (! $attachment->external) {
            $this->deleteFileInStorage($attachment);
        }

        $attachment->delete();
    }

    /**
     * Delete a file from the filesystem it sits on.
     * Cleans any empty leftover folders.
     */
    protected function deleteFileInStorage(FileUpload $attachment)
    {
        $storage = $this->getStorageDisk();
        $dirPath = $this->adjustPathForStorageDisk(dirname($attachment->path));

        $storage->delete($this->adjustPathForStorageDisk($attachment->path));
        if (count($storage->allFiles($dirPath)) === 0) {
            $storage->deleteDirectory($dirPath);
        }
    }

    /**
     * Store a file in storage with the given filename.
     *
     * @throws FileUploadException
     */
    protected function putFileInStorage(UploadedFile $uploadedFile, $basePath): string
    {
        $attachmentData = file_get_contents($uploadedFile->getRealPath());

        $storage = $this->getStorageDisk();
        $basePath = $basePath.'/'.date('Y-m-M').'/';

        $uploadFileName = Str::random(16).'.'.$uploadedFile->getClientOriginalExtension();
        while ($storage->exists($this->adjustPathForStorageDisk($basePath.$uploadFileName))) {
            $uploadFileName = Str::random(3).$uploadFileName;
        }

        $attachmentPath = $basePath.$uploadFileName;
        try {
            $storage->put($this->adjustPathForStorageDisk($attachmentPath), $attachmentData);
        } catch (Exception $e) {
            Log::error('Error when attempting file upload:'.$e->getMessage());
            throw new Exception(trans('errors.path_not_writable', ['filePath' => $attachmentPath]));
        }

        return $attachmentPath;
    }

    protected function putResizeFileInStorage(UploadedFile $uploadedFile, $basePath, $width = 100, $height = null): string
    {
        //TODO: handle image resize

        $attachmentData = file_get_contents($uploadedFile->getRealPath());

        $storage = $this->getStorageDisk();
        $basePath = $basePath.'/'.date('Y-m-M').'/';

        $uploadFileName = Str::random(16).'.'.$uploadedFile->getClientOriginalExtension();
        while ($storage->exists($this->adjustPathForStorageDisk($basePath.$uploadFileName))) {
            $uploadFileName = Str::random(3).$uploadFileName;
        }

        $attachmentPath = $basePath.$uploadFileName;
        try {
            $storage->put($this->adjustPathForStorageDisk($attachmentPath), $attachmentData);
        } catch (Exception $e) {
            Log::error('Error when attempting file upload:'.$e->getMessage());
            throw new Exception(trans('errors.path_not_writable', ['filePath' => $attachmentPath]));
        }

        return $attachmentPath;
    }

    /**
     * Get the file validation rules for attachments.
     */
    public function getFileValidationRules(): array
    {
        return [
            'file', 'max:25240', /** 25MB */
        ];
    }

    /**
     * Normalize path.
     *
     * @param  string  $path
     * @return string
     *
     * @throws LogicException
     */
    public static function normalizePath($path)
    {
        return static::normalizeRelativePath($path);
    }

    /**
     * Normalize relative directories in a path.
     *
     * @param  string  $path
     * @return string
     *
     * @throws LogicException
     */
    public static function normalizeRelativePath($path)
    {
        $path = str_replace('\\', '/', $path);
        $path = static::removeFunkyWhiteSpace($path);
        $parts = [];

        foreach (explode('/', $path) as $part) {
            switch ($part) {
                case '':
                case '.':
                    break;

                case '..':
                    if (empty($parts)) {
                        throw new LogicException(
                            'Path is outside of the defined root, path: ['.$path.']'
                        );
                    }
                    array_pop($parts);
                    break;

                default:
                    $parts[] = $part;
                    break;
            }
        }

        $path = implode('/', $parts);

        return $path;
    }

    /**
     * Rejects unprintable characters and invalid unicode characters.
     *
     * @param  string  $path
     * @return string $path
     */
    protected static function removeFunkyWhiteSpace($path)
    {
        if (preg_match('#\p{C}+#u', $path)) {
            throw CorruptedPathDetected::forPath($path);
        }

        return $path;
    }
}
