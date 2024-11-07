<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class CsvProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // 
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('upload_csv');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate that the file is a CSV
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);

        // Get the uploaded file
        $file = $request->file('csv_file');

          // Open and read the file
        if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
            // Get the header row
            $header = fgetcsv($handle, 1000, ',');

            // Loop through the remaining rows
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                // Combine header and data to associate keys and values
                $rowData = array_combine($header, $data);

                // Map fields as needed
                // For example, if you have a 'users' table:
                /*
                \App\Models\User::create([
                    'name' => $rowData['Name'],
                    'email' => $rowData['Email'],
                    // Add other fields as necessary
                ]);
                */

                // For demonstration, you can output the row data
                // dd($rowData);
                $sectors = $request->sectors;
                

            }

            fclose($handle);
        }

        return back()->with('success', 'CSV file processed successfully.');
    }

        
    // }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
