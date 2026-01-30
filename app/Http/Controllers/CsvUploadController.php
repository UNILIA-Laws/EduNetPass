<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudentCredentialsMail;
use Illuminate\Support\Facades\Response;

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

        $count = 0;

        while (($row = fgetcsv($handle)) !== false) {
            $student = array_combine($headers, $row);

            Mail::to($student['mail'])->send(new StudentCredentialsMail($student));

            $count++;
        }

        fclose($handle);

        return back()->with('success', "✅ Emails sent successfully to {$count} students.");
    }


    //downlaod sample csv
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
