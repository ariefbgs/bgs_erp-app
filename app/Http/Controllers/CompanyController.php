<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    private $fileFields = ['logo', 'ttd_sales_support', 'ttd_do', 'ttd_inv', 'ttd_inv2'];

    public function index()
    {
        $companies = Company::orderBy('id', 'desc')->get();
        return view('companies.index', compact('companies'));
    }

    public function create()
    {
        return view('companies.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateAndPrepare($request);
        $files = $this->handleFileUploads($request);
        $companyData = array_merge($data, $files);
        Company::create($companyData);
        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil ditambahkan');
    }

    public function edit(Company $company)
    {
        return view('companies.edit', compact('company'));
    }

    public function update(Request $request, Company $company)
    {
        $data = $this->validateAndPrepare($request, $company->id);
        $files = $this->handleFileUploads($request, $company);

        // Proses clear file
        foreach ($this->fileFields as $field) {
            $clearKey = 'clear_' . $field;
            if ($request->has($clearKey) && $request->boolean($clearKey)) {
                if ($company->$field && file_exists(public_path($company->$field))) {
                    unlink(public_path($company->$field));
                }
                $files[$field] = null;
            }
        }

        $companyData = array_merge($data, $files);
        $company->update($companyData);
        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil diupdate');
    }

    public function show(Company $company)
    {
        return view('companies.show', compact('company'));
    }

    public function destroy(Company $company)
    {
        foreach ($this->fileFields as $field) {
            if ($company->$field && file_exists(public_path($company->$field))) {
                unlink(public_path($company->$field));
            }
        }
        $company->delete();
        return redirect()->route('companies.index')->with('success', 'Perusahaan berhasil dihapus');
    }

    private function validateAndPrepare(Request $request, $id = null)
    {
        $rules = [
            'company_code' => ['required', Rule::unique('companies', 'company_code')->ignore($id)],
            'name'         => 'required',
            'pic_sales_support' => 'nullable|string',
            'pic_do'            => 'nullable|string',
            'pic_inv'           => 'nullable|string',
            'logo'              => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ttd_sales_support' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ttd_do'            => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ttd_inv'           => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'ttd_inv2'          => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        $request->validate($rules);
        $data = $request->except($this->fileFields);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    private function handleFileUploads(Request $request, Company $company = null)
    {
        $basePath = 'uploads/companies';
        $folderMap = [
            'logo'              => 'logo',
            'ttd_sales_support' => 'ttd_sales_support',
            'ttd_do'            => 'ttd_do',
            'ttd_inv'           => 'ttd_inv',
            'ttd_inv2'          => 'ttd_inv2',
        ];

        $paths = [];

        foreach ($this->fileFields as $field) {
            if ($request->hasFile($field)) {
                if ($company && $company->$field && file_exists(public_path($company->$field))) {
                    unlink(public_path($company->$field));
                }

                $subFolder = $folderMap[$field];
                $file = $request->file($field);
                $filename = time() . "_{$field}_" . uniqid() . '.' . $file->getClientOriginalExtension();
                $uploadDir = public_path("{$basePath}/{$subFolder}");
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $file->move($uploadDir, $filename);
                $paths[$field] = "{$basePath}/{$subFolder}/{$filename}";
            } elseif ($company) {
                $paths[$field] = $company->$field;
            }
        }

        return $paths;
    }
}