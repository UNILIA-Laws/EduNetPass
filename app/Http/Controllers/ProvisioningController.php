<?php

namespace App\Http\Controllers;

use App\Mail\SendLogMail;
use App\Models\ActivityLog;
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
            Log::error('MyLDAP.campuses failed', ['error' => $error]);
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
            Log::error('MyLDAP.provision campuses failed', ['error' => $e->getMessage(), 'by' => $request->user()?->email]);

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

        $uid = trim($data['uid']);

        try {
            $results = $service->provision([[
                'givenName'    => trim($data['givenName']),
                'sn'           => trim($data['sn']),
                'uid'          => $uid,
                'mail'         => trim($data['mail']),
                'Reg'          => trim($data['Reg'] ?? ''),
                'userPassword' => $data['userPassword'] ?? '',
            ]], $data['campus'], $data['type']);
        } catch (Throwable $e) {
            Log::error('MyLDAP.user provision failed', [
                'uid' => $uid, 'type' => $data['type'], 'campus' => $data['campus'],
                'error' => $e->getMessage(), 'by' => $request->user()?->email,
            ]);
            ActivityLog::record('user.provision_failed', $uid, [
                'type' => $data['type'], 'campus' => $data['campus'],
            ]);

            return back()->with('tab', 'single')
                ->withInput($request->except('userPassword'))
                ->withErrors(['ldap' => $e->getMessage()]);
        }

        // Passwords are never logged, whether typed or generated
        $r = $results[0] ?? [];

        Log::info('MyLDAP.user provisioned', [
            'uid' => $uid, 'type' => $data['type'], 'campus' => $data['campus'],
            'ldap' => $r['ldap'] ?? null, 'email' => $r['email'] ?? null, 'error' => $r['error'] ?? null,
            'by' => $request->user()?->email,
        ]);
        ActivityLog::record('user.provisioned', $uid, array_filter([
            'type'   => $data['type'],
            'campus' => $data['campus'],
            'ldap'   => $r['ldap'] ?? null,
            'email'  => $r['email'] ?? null,
            'error'  => ! empty($r['error']) ? 'yes' : null,
        ]));

        return back()->with('tab', 'single')->with('results', $results);
    }

    /** Bulk: CSV / XLSX upload. A "type" column in the file overrides the dropdown per row. */
    public function upload(Request $request, ProvisioningService $service, StudentFileReader $reader, LdapService $ldap)
    {
        try {
            $campusKeys = array_keys($ldap->campuses());
        } catch (Throwable $e) {
            Log::error('MyLDAP.bulk campuses failed', ['error' => $e->getMessage(), 'by' => $request->user()?->email]);

            return back()->with('tab', 'bulk')->withErrors(['ldap' => $e->getMessage()]);
        }

        $request->validate([
            'csv_file' => 'required|file|extensions:csv,txt,xlsx|max:5120',
            'campus'   => ['required', Rule::in($campusKeys)],
            'type'     => ['required', Rule::in(array_keys(config('ldap.user_types')))],
        ]);

        $fileName = $request->file('csv_file')->getClientOriginalName();
        $campus   = $request->input('campus');
        $type     = $request->input('type');

        try {
            $rows    = $reader->read($request->file('csv_file'));
            $results = $service->provision($rows, $campus, $type);
        } catch (InvalidArgumentException $e) {
            Log::warning('MyLDAP.bulk file rejected', [
                'file' => $fileName, 'error' => $e->getMessage(), 'by' => $request->user()?->email,
            ]);
            ActivityLog::record('user.bulk_rejected', $fileName, ['campus' => $campus, 'type' => $type]);

            return back()->with('tab', 'bulk')->withErrors(['csv_file' => $e->getMessage()]);
        } catch (Throwable $e) {
            Log::error('MyLDAP.bulk failed', [
                'file' => $fileName, 'error' => $e->getMessage(), 'by' => $request->user()?->email,
            ]);
            ActivityLog::record('user.bulk_failed', $fileName, ['campus' => $campus, 'type' => $type]);

            return back()->with('tab', 'bulk')->withErrors(['ldap' => $e->getMessage()]);
        }

        $logFile = $this->sendLogToIct($results, $request->user()?->email);

        // Counts per LDAP status, e.g. ['created' => 40, 'failed' => 2] (whatever statuses your service returns)
        $ldapCounts  = collect($results)->countBy(fn ($r) => (string) ($r['ldap'] ?? 'unknown'))->all();
        $emailCounts = collect($results)->countBy(fn ($r) => (string) ($r['email'] ?? 'unknown'))->all();
        $withErrors  = collect($results)->filter(fn ($r) => ! empty($r['error']))->count();

        Log::info('MyLDAP.bulk provisioned', [
            'file' => $fileName, 'rows' => count($results), 'campus' => $campus, 'type' => $type,
            'ldap' => $ldapCounts, 'email' => $emailCounts, 'log_file' => $logFile,
            'by' => $request->user()?->email,
        ]);
        ActivityLog::record('user.bulk_provisioned', $fileName, array_filter([
            'rows'     => count($results),
            'campus'   => $campus,
            'type'     => $type,
            'ldap'     => $ldapCounts,
            'email'    => $emailCounts,
            'errors'   => $withErrors ?: null,
            'log_file' => $logFile,
        ]));

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

    /** Log file with LDAP status. Passwords are never written to it. Returns the file name. */
    private function sendLogToIct(array $results, ?string $by): string
    {
        $lines = ["=== MyLDAP Bulk Provisioning Log ===", 'Date: ' . now(), 'Run by: ' . ($by ?? 'n/a'), ''];
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

        return $file;
    }
}