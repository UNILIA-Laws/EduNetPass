<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\StudentCredentialsMail;

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
}
