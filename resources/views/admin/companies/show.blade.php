@extends('layouts.admin')
@section('title', 'Company Details')

@section('content')

<div class="row justify-content-center">
<div class="col-md-10">

<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title m-0">
            <i class="fas fa-building me-2 text-purple"></i>
            Company Details
        </h3>

        <a href="{{ route('admin.companies.edit', $company->id) }}"
           class="btn btn-primary btn-sm">
            <i class="fas fa-edit me-1"></i> Edit
        </a>
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-3 text-center">

                @if($company->logo)
                    <img src="{{ Storage::url($company->logo) }}"
                         alt="Company Logo"
                         class="img-fluid mb-3"
                         style="max-height:120px;">
                @else
                    <div style="font-size:80px;color:#7C3AED;">
                        <i class="fas fa-building"></i>
                    </div>
                @endif

            </div>

            <div class="col-md-9">

                <table class="table table-bordered">

                    <tr>
                        <th width="220">Company Name</th>
                        <td>{{ $company->name }}</td>
                    </tr>

                    <tr>
                        <th>Email</th>
                        <td>{{ $company->email ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>GST Number</th>
                        <td>{{ $company->gst_number ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>PAN Number</th>
                        <td>{{ $company->pan_number ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>Currency</th>
                        <td>{{ $company->currency }}</td>
                    </tr>

                    <tr>
                        <th>Address</th>
                        <td>{{ $company->address ?? '-' }}</td>
                    </tr>

                    <tr>
                        <th>Created At</th>
                        <td>{{ $company->created_at->format('d M Y h:i A') }}</td>
                    </tr>

                </table>

            </div>

        </div>

    </div>

</div>

</div>
</div>

@endsection
