<?php

namespace App\Http\Controllers;

use App\Models\AppItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

class AppItemController extends Controller
{
    /**
     * Display APP items for the supply officer
     */
    public function index(Request $request): View
    {
        $fiscalYear = $request->input('fiscal_year', date('Y'));

        $categories = AppItem::getCategories($fiscalYear);

        $appItems = AppItem::active()
            ->forFiscalYear($fiscalYear)
            ->orderBy('category')
            ->orderBy('item_name')
            ->get()
            ->groupBy('category');

        $stats = [
            'total_items' => AppItem::forFiscalYear($fiscalYear)->count(),
            'active_items' => AppItem::active()->forFiscalYear($fiscalYear)->count(),
            'categories_count' => $categories->count(),
        ];

        return view('supply.app.index', [
            'appItems' => $appItems,
            'categories' => $categories,
            'fiscalYear' => $fiscalYear,
            'stats' => $stats,
        ]);
    }

    /**
     * Show import form
     */
    public function import(): View
    {
        return view('supply.app.import');
    }

    /**
     * Process CSV import
     */
    public function processImport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'fiscal_year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        // Store the uploaded file temporarily
        $file = $request->file('csv_file');
        $fileName = 'app_import_' . time() . '.csv';
        $filePath = $file->storeAs('imports', $fileName);
        $fullPath = Storage::path($filePath);

        try {
            // Run the import command
            $exitCode = Artisan::call('app:import', [
                'file' => $fullPath,
                '--year' => $validated['fiscal_year'],
            ]);

            // Get the output
            $output = Artisan::output();

            // Clean up the temporary file
            Storage::delete($filePath);

            if ($exitCode === 0) {
                return redirect()
                    ->route('supply.app.index', ['fiscal_year' => $validated['fiscal_year']])
                    ->with('success', 'APP items imported successfully!')
                    ->with('import_output', $output);
            }

            return back()
                ->withInput()
                ->withErrors(['csv_file' => 'Import failed. Please check the file format.'])
                ->with('import_output', $output);
        } catch (\Exception $e) {
            // Clean up the temporary file
            if (Storage::exists($filePath)) {
                Storage::delete($filePath);
            }

            return back()
                ->withInput()
                ->withErrors(['csv_file' => 'Import failed: ' . $e->getMessage()]);
        }
    }
}
