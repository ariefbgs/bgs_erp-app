@extends('layouts.app')

@section('title', 'Company Management')

@section('content')
<style>
    .company-logo {
        width: 50px;
        height: 50px;
        object-fit: cover;
    }
</style>

<div class="card">
    <div class="card-header bg-primary text-white">
        <h4>Company Management</h4>
    </div>
    <div class="card-body">
        <a href="{{ route('companies.create') }}" class="btn btn-success mb-3">
            <i class="bi bi-plus-circle"></i> Add Company
        </a>
        
        @if(session('success'))
        <div id="success-alert" class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
        </div>

        <script>
            setTimeout(() => {
                let alert = document.getElementById('success-alert');
                if(alert){
                    let bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
                    bsAlert.close();
                }
            }, 2000);
        </script>
        @endif
        
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center">No</th>
                        <th class="text-center">Logo</th>
                        <th class="text-center">Code</th>
                        <th class="text-center">Company Name</th>
                        <th class="text-center">Email</th>
                        <th class="text-center">Phone</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($companies as $index => $company)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">
                            @if($company->logo && file_exists(public_path($company->logo)))
                                <img src="{{ asset($company->logo) }}" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;">
                            @else
                                <span class="text-muted small">No Logo</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $company->company_code }}</td>
                        <td>{{ $company->name }}</td>
                        <td>{{ $company->email ?? '-' }}</td>
                        <td>{{ $company->phone ?? '-' }}</td>
                        <td class="text-center">
                            @if($company->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <a href="{{ route('companies.show', $company->id) }}" 
                            class="btn btn-info btn-sm">
                                <i class="bi bi-eye"></i>
                            </a>

                            <a href="{{ route('companies.edit', $company->id) }}" 
                            class="btn btn-warning btn-sm">
                                <i class="bi bi-pencil"></i>
                            </a>

                            <form action="{{ route('companies.destroy', $company->id) }}" 
                                method="POST" 
                                class="d-inline">
                                @csrf
                                @method('DELETE')

                                <button type="submit" 
                                        class="btn btn-danger btn-sm"
                                        onclick="return confirm('Are you sure you want to delete this company?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">There is no company data available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection