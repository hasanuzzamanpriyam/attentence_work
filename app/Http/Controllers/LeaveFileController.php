<?php

namespace App\Http\Controllers;

use App\Helper\Files;
use App\Helper\Reply;
use App\Traits\IconTrait;
use Illuminate\Http\Request;
use App\Models\LeaveFile;

class LeaveFileController extends AccountBaseController
{
    use IconTrait;

    public function __construct()
    {
        parent::__construct();
        $this->pageTitle = 'app.menu.leaves';
    }

    /**
     * Store newly uploaded leave file(s) in storage.
     */
    public function store(Request $request)
    {
        if ($request->hasFile('file')) {
            $leaveIds = $request->input('leave_ids', []);

            foreach ($request->file as $fileData) {
                foreach ($leaveIds as $leaveId) {
                    $file = new LeaveFile();
                    $file->leave_id = $leaveId;
                    $file->user_id = user()->id;

                    $filename = Files::uploadLocalOrS3($fileData, LeaveFile::FILE_PATH . '/' . $leaveId);

                    $file->filename = $fileData->getClientOriginalName();
                    $file->hashname = $filename;
                    $file->size = $fileData->getSize();
                    $file->save();
                }
            }
        }

        return Reply::success(__('messages.fileUploaded'));
    }

    /**
     * Remove the specified leave file from storage.
     */
    public function destroy($id)
    {
        $file = LeaveFile::findOrFail($id);

        $this->deletePermission = user()->permission('delete_leave');
        abort_403(!($this->deletePermission == 'all'
            || ($this->deletePermission == 'added' && $file->added_by == user()->id)
            || ($this->deletePermission == 'owned' && $file->user_id == user()->id)
        ));

        Files::deleteFile($file->hashname, LeaveFile::FILE_PATH . '/' . $file->leave_id);

        LeaveFile::destroy($id);

        return Reply::success(__('messages.deleteSuccess'));
    }

    /**
     * Download the specified leave file.
     */
    public function download($id)
    {
        $file = LeaveFile::whereRaw('md5(id) = ?', [$id])->firstOrFail();

        $this->viewPermission = user()->permission('view_leave');
        abort_403(!($this->viewPermission == 'all'
            || ($this->viewPermission == 'added' && $file->added_by == user()->id)
            || ($this->viewPermission == 'owned' && $file->user_id == user()->id)
            || ($this->viewPermission == 'both' && ($file->user_id == user()->id || $file->added_by == user()->id))
        ));

        return download_local_s3($file, LeaveFile::FILE_PATH . '/' . $file->leave_id . '/' . $file->hashname);
    }
}
