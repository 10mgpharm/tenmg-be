<?php

namespace App\Http\Controllers;

use App\Imports\UserDataImport;
use App\Models\Business;
use App\Models\FileUpload;
use App\Services\AttachmentService;
use App\Traits\FileUploadTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UploadExampleController extends Controller
{
    use FileUploadTrait;

    public function __construct(private AttachmentService $attachmentService)
    {
        $this->attachmentService = $attachmentService;
    }

    /**
     * Example: Upload picture example
     */
    public function uploadPicture(Request $request): RedirectResponse
    {
        $user = $request->user();
        $modelId = $user->id;
        $modelType = '\App\Models\User';

        if ($request->avatar) {
            $oldFile = $user->file; //?FileUpload $file | null
            $fileId = $this->processFileUpload(
                $this->attachmentService,
                $request,
                'avatar',
                $modelId,
                $modelType,
                '/profiles',
                $oldFile
            );

            if ($fileId) {
                $user->avatar_id = $fileId;
                $user->save();
            }
        }

        return back()->with('success', 'Picture updated successfully');
    }

    /**
     *  Example: Upload excel and get data without saving data to DB
     */
    public function uploadExcelAndGetData(Request $request, Business $business): JsonResponse
    {
        try {
            if (! $request->file('excel_import_file')) {
                throw new Exception('Please select excel file to upload');
            }

            // use array
            $array = (new UserDataImport(business: $business))->toArray(request()->file('excel_import_file'), null, \Maatwebsite\Excel\Excel::XLSX);

            // or collection
            $collection = (new UserDataImport(business: $business))->toCollection(request()->file('excel_import_file'), null, \Maatwebsite\Excel\Excel::XLSX);

            return response()->json([
                'data' => [
                    'collection' => $collection,
                    'array' => $array,
                ],
                'message' => 'Data retrieved',
            ], 200);
        } catch (\Throwable $th) {
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Example: Upload excel and save data to DB
     */
    public function uploadExcelAndSaveData(Request $request, Business $business): RedirectResponse
    {
        try {
            $fileKey = 'excel_import_file';
            if (! $request->file($fileKey)) {
                throw new Exception('Please select excel file to upload');
            }

            DB::beginTransaction();

            Excel::import(new UserDataImport(business: $business), request()->file($fileKey), null, \Maatwebsite\Excel\Excel::XLSX);

            // store excel file
            $user = $request->user();
            $modelId = $user->id;
            $modelType = '\App\Models\User';
            $fileId = $this->processFileUpload(
                service: $this->attachmentService,
                request: $request,
                file_key: $fileKey,
                model_id: $modelId,
                model_type: $modelType,
                path: '/excels_uploads',
                old_attachment: null
            );
            $file = FileUpload::find($fileId) ?? null;

            $actionPerformed = 'uploaded';
            $currentDate = Carbon::now()->format('F d, Y h:i A');

            // Example logging activity
            activity('asset')
                ->causedBy($user)
                ->performedOn($file)
                ->withProperties([
                    'action' => $actionPerformed,
                    'auditor' => "{$user->name} {$actionPerformed} excel for user import on {$currentDate}",
                    'file_path' => $file->url,
                    'is_system_user' => $user->is_system_user,
                ])
                ->event('excel_upload')
                ->log("You :properties.action excel for user import on {$currentDate}");

            DB::commit();

            return back()->with('success', 'User data uploaded successfully');
        } catch (\Throwable $th) {
            DB::rollBack();

            return back()->with('error', $th->getMessage());
        }
    }
}
