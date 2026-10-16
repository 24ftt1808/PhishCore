<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\TrustedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrustedFileController extends Controller
{
    /**
     * Admin page listing every file marked as safe. Team members and administrators can see it,
     * but only administrators can remove a mark.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->is_team_member || $user->role === 'admin', 403, 'Only team members and administrators can see the safe files.');

        $search = trim((string) $request->input('search', ''));

        $files = TrustedFile::query()
            ->with('markedByUser:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $like = '%'.addcslashes($search, '%_\\').'%';
                $query->where(fn ($q) => $q->where('file_name', 'like', $like)->orWhere('file_sha256', 'like', $like));
            })
            ->latest()
            ->get();

        return view('safe-files.index', [
            'files' => $files,
            'total' => TrustedFile::count(),
            'search' => $search,
            'canRemove' => $user->role === 'admin',
        ]);
    }

    /** Take the safe mark off from the list page. */
    public function destroyFile(TrustedFile $trustedFile): RedirectResponse
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Only administrators can remove a safe mark.');

        $trustedFile->delete();

        return back()->with('status', 'The safe mark was removed. New scans of that file are checked as normal again.');
    }

    /** Mark the file behind a scan as safe. Administrators only, because it overrides the scan result. */
    public function store(Request $request, Report $report): RedirectResponse
    {
        $this->authorizeTrust($report);

        TrustedFile::firstOrCreate(
            ['file_sha256' => $report->file_sha256],
            ['file_name' => $report->file_name, 'marked_by' => $request->user()->id]
        );

        return back()->with('trusted_file_status', 'This file is now marked as safe. New scans of this exact file will show it as safe. This scan result is not changed.');
    }

    /** Take the safe mark off again. */
    public function destroy(Report $report): RedirectResponse
    {
        $this->authorizeTrust($report);

        TrustedFile::where('file_sha256', $report->file_sha256)->get()->each->delete();

        return back()->with('trusted_file_status', 'The safe mark was removed. New scans of this file are checked as normal again.');
    }

    private function authorizeTrust(Report $report): void
    {
        abort_unless(auth()->user()->role === 'admin', 403, 'Only administrators can mark a file as safe.');
        abort_unless($report->type === 'file' && $report->file_sha256, 404);
    }
}
