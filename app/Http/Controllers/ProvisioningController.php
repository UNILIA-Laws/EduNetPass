<?php

namespace App\Http\Controllers;

use App\Mail\SendLogMail;
use App\Services\ProvisioningService;
use App\Services\StudentFileReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

class ProvisioningController extends Controller
{
    public function showForm()
    {
        return view('upload', ['campuses' => config('ldap.campuses')]);
    }

    /** Add ONE student from the web form. */
    public function storeSingle(Request $request, ProvisioningService $service)
    {
        $data = $request->validate([
            'givenName'    => 'required|string|max:100',
            'sn'           => 'required|string|max:100',
            'uid'          => ['required', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/'],
            'mail'         => 'required|email|max:255',
            'Reg'          => 'nullable|string|max:50',
            'userPassword' => 'nullable|string|min:8|max:100',
            'campus'       => ['required', Rule::in(array_keys(config('ldap.campuses')))],
        ], [
            'uid.regex' => 'Username may only contain letters, numbers, dot, dash and underscore.',
        ]);

        try {
            $results = $service->provision([[
                'givenName'    => trim($data['givenName']),
                'sn'           => trim($data['sn']),
                'uid'          => trim($data['uid']),
                'mail'         => trim($data['mail']),
                'Reg'          => trim($data['Reg'] ?? ''),
                'userPassword' => $data['userPassword'] ?? '',
            ]], $data['campus']);
        } catch (Throwable $e) {
            return back()->with('tab', 'single')
                ->withInput($request->except('userPassword'))
                ->withErrors(['ldap' => $e->getMessage()]);
        }

        return back()->with('tab', 'single')->with('results', $results);
    }

    /** Bulk: CSV / XLSX upload. */
    public function upload(Request $request, ProvisioningService $service, StudentFileReader $reader)
    {
        $request->validate([
            'csv_file' => 'required|file|extensions:csv,txt,xlsx|max:5120',
            'campus'   => ['required', Rule::in(array_keys(config('ldap.campuses')))],
        ]);

        try {
            $rows    = $reader->read($request->file('csv_file'));
            $results = $service->provision($rows, $request->input('campus'));
        } catch (InvalidArgumentException $e) {
            return back()->with('tab', 'bulk')->withErrors(['csv_file' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('eduroam.bulk failed', ['error' => $e->getMessage()]);
            return back()->with('tab', 'bulk')->withErrors(['ldap' => $e->getMessage()]);
        }

        $this->sendLogToIct($results, $request->user()?->email);

        return back()->with('tab', 'bulk')->with('results', $results);
    }

    public function downloadSample()
    {
        $columns = ['givenName', 'sn', 'Reg', 'mail', 'uid', 'userPassword'];
        $sample  = [
            ['John', 'Doe', 'BEH/01/001/24', 'john.doe@unilia.ac.mw', 'beh-01-001-24', 'beh-01-001-24'],
            ['Jane', 'Smith', 'BEH/01/002/24', 'jane.smith@unilia.ac.mw', 'beh-01-002-24', ''], // blank = auto-generate
        ];

        return response()->streamDownload(function () use ($columns, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns, ',', '"', '\\');
            foreach ($sample as $row) {
                fputcsv($out, $row, ',', '"', '\\');
            }
            fclose($out);
        }, 'sample_students.csv', ['Content-Type' => 'text/csv']);
    }

    /** Same log file as before, now with LDAP status. Passwords are never written to it. */
    private function sendLogToIct(array $results, ?string $by): void
    {
        $lines = ["=== Eduroam Bulk Provisioning Log ===", 'Date: ' . now(), 'Run by: ' . ($by ?? 'n/a'), ''];
        foreach ($results as $r) {
            $lines[] = sprintf(
                '%-22s %-32s LDAP: %-8s E-mail: %-8s %s',
                $r['uid'], $r['mail'], $r['ldap'], $r['email'], $r['error'] ?? ''
            );
        }

        $file = 'bulk_upload_log_' . now()->format('Y_m_d_H_i_s') . '.txt';
        Storage::disk('local')->put($file, implode("\n", $lines));

        try {
            Mail::to(config('ldap.log_recipient'))->send(new SendLogMail($file));
        } catch (Throwable $e) {
            Log::warning('Could not e-mail provisioning log: ' . $e->getMessage());
        }
    }
}
