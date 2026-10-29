<?php

namespace App\Http\Controllers;

use App\Mail\SendLogMail;
use App\Services\LdapService;
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
    /** GET /enroll : add ONE user */
    public function showForm(LdapService $ldap)
    {
        return $this->formView('users.create', $ldap);
    }

    /** GET /enroll/bulk : upload a CSV / XLSX */
    public function bulkUpload(LdapService $ldap)
    {
        return $this->formView('users.bulk', $ldap);
    }

    /** Both pages need the campuses (read from LDAP) and the user types. */
    private function formView(string $view, LdapService $ldap)
    {
        $campuses = [];
        $error    = null;

        try {
            $campuses = $ldap->campuses();
        } catch (Throwable $e) {
            $error = $e->getMessage();
            Log::error('eduroam.campuses failed', ['error' => $error]);
        }

        $page = view($view, [
            'campuses' => $campuses,
            'types'    => config('ldap.user_types'),
        ]);

        return $error ? $page->withErrors(['ldap' => $error]) : $page;
    }

    /** Add ONE user (any type) from the web form. */
    public function storeSingle(Request $request, ProvisioningService $service, LdapService $ldap)
    {
        try {
            $campusKeys = array_keys($ldap->campuses());
        } catch (Throwable $e) {
            return back()->with('tab', 'single')->withInput($request->except('userPassword'))
                ->withErrors(['ldap' => $e->getMessage()]);
        }

        $data = $request->validate([
            'type'         => ['required', Rule::in(array_keys(config('ldap.user_types')))],
            'givenName'    => 'required|string|max:100',
            'sn'           => 'required|string|max:100',
            'uid'          => ['required', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]{0,63}$/'],
            'mail'         => 'required|email|max:255',
            'Reg'          => 'nullable|string|max:50',   // optional, only used in the e-mail
            'userPassword' => 'nullable|string|min:8|max:100',
            'campus'       => ['required', Rule::in($campusKeys)],
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
            ]], $data['campus'], $data['type']);
        } catch (Throwable $e) {
            return back()->with('tab', 'single')
                ->withInput($request->except('userPassword'))
                ->withErrors(['ldap' => $e->getMessage()]);
        }

        return back()->with('tab', 'single')->with('results', $results);
    }

    /** Bulk: CSV / XLSX upload. A "type" column in the file overrides the dropdown per row. */
    public function upload(Request $request, ProvisioningService $service, StudentFileReader $reader, LdapService $ldap)
    {
        try {
            $campusKeys = array_keys($ldap->campuses());
        } catch (Throwable $e) {
            return back()->with('tab', 'bulk')->withErrors(['ldap' => $e->getMessage()]);
        }

        $request->validate([
            'csv_file' => 'required|file|extensions:csv,txt,xlsx|max:5120',
            'campus'   => ['required', Rule::in($campusKeys)],
            'type'     => ['required', Rule::in(array_keys(config('ldap.user_types')))],
        ]);

        try {
            $rows    = $reader->read($request->file('csv_file'));
            $results = $service->provision($rows, $request->input('campus'), $request->input('type'));
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
        $columns = ['givenName', 'sn', 'Reg', 'mail', 'uid', 'userPassword', 'type'];
        $sample  = [
            ['John', 'Doe', 'BEH/01/001/24', 'john.doe@unilia.ac.mw', 'beh-01-001-24', 'beh-01-001-24', 'student'],
            ['Jane', 'Smith', '', 'jane.smith@unilia.ac.mw', 'jsmith', '', 'staff'], // blank password = auto-generate, Reg is optional
            ['Mary', 'Banda', '', 'mary.banda@unilia.ac.mw', 'mbanda', '', ''],       // blank type = dropdown default
        ];

        return response()->streamDownload(function () use ($columns, $sample) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $columns, ',', '"', '\\');
            foreach ($sample as $row) {
                fputcsv($out, $row, ',', '"', '\\');
            }
            fclose($out);
        }, 'sample_users.csv', ['Content-Type' => 'text/csv']);
    }

    /** Log file with LDAP status. Passwords are never written to it. */
    private function sendLogToIct(array $results, ?string $by): void
    {
        $lines = ["=== Eduroam Bulk Provisioning Log ===", 'Date: ' . now(), 'Run by: ' . ($by ?? 'n/a'), ''];
        foreach ($results as $r) {
            $lines[] = sprintf(
                '%-22s %-8s %-32s LDAP: %-8s E-mail: %-8s %s',
                $r['uid'], $r['type'] ?? '', $r['mail'], $r['ldap'], $r['email'], $r['error'] ?? ''
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