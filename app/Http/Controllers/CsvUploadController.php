<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudentCredentialsMail;
use App\Mail\SendLogMail; // New mailable for sending log
use Illuminate\Support\Facades\Storage;

class CsvUploadController extends Controller
{
    public function showForm()
    {
        return view('upload');
    }

    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('csv_file');
        $path = $file->getRealPath();

        $handle = fopen($path, 'r');
        $headers = fgetcsv($handle); // first row

        $sentEmails = [];
        $failedEmails = [];

        while (($row = fgetcsv($handle)) !== false) {
            $student = array_combine($headers, $row);

            try {
                Mail::to($student['mail'])->send(new StudentCredentialsMail($student));
                $sentEmails[] = $student['mail'];
            } catch (\Exception $e) {
                $failedEmails[] = $student['mail'] . " - " . $e->getMessage();
            }
        }

        fclose($handle);

        // Create log content
        $logContent = "=== Eduroam Bulk Provisioning Log ===\n";
        $logContent .= "Date: " . now() . "\n\n";
        $logContent .= "Sent Emails:\n";
        $logContent .= empty($sentEmails) ? "None\n" : implode("\n", $sentEmails);
        $logContent .= "\n\nFailed Emails:\n";
        $logContent .= empty($failedEmails) ? "None\n" : implode("\n", $failedEmails);

        // Save log to storage
        $logFileName = 'bulk_upload_log_' . now()->format('Y_m_d_H_i_s') . '.txt';
        Storage::disk('local')->put($logFileName, $logContent);

        // Send log to ICT
        Mail::to('ictlaws@unilia.ac.mw')->send(new SendLogMail($logFileName));

        return back()->with([
            'success' => "✅ Emails processed!",
            'report' => [
                'sent' => count($sentEmails),
                'failed' => count($failedEmails)
            ]
        ]);
    }

    // Download sample CSV method remains unchanged
    public function downloadSample()
    {
        $filename = 'sample_students.csv';
        $columns = ['givenName','sn','Reg','mail','uid','userPassword'];
        $sampleData = [
            ['John','Doe','BEH/01/001/24','john.doe@unilia.ac.mw','beh-01-001-24','beh-01-001-24'],
            ['Jane','Smith','BEH/01/002/24','jane.smith@unilia.ac.mw','beh-01-002-24','beh-01-002-24'],
        ];

        $callback = function() use ($columns, $sampleData) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        return response()->stream($callback, 200, $headers);
    }
}
