@extends('layouts.admin')

@section('title', 'Company Merges')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Company Merges</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
<div class="container-fluid">

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="row">
        <!-- Add New Merge Form -->
        <div class="col-md-5">
            <div class="card card-outline card-primary">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-link mr-1"></i> New Company Merge</h3></div>
                <div class="card-body">
                    <p class="text-muted small">
                        <i class="fas fa-info-circle"></i>
                        Jab 2 companies merge hoti hain, to dono ek doosre ko stock transfer kar sakti hain.
                    </p>
                    <form action="{{ route('admin.company-merges.store') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label>Company A <span class="text-danger">*</span></label>
                            <select name="company_id" class="form-control select2" required>
                                <option value="">-- Company Select Karein --</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" {{ old('company_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Company B (Merge With) <span class="text-danger">*</span></label>
                            <select name="merged_with_company_id" class="form-control select2" required>
                                <option value="">-- Company Select Karein --</option>
                                @foreach($companies as $c)
                                    <option value="{{ $c->id }}" {{ old('merged_with_company_id') == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional...">{{ old('notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-link"></i> Companies Merge Karein
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Existing Merges List -->
        <div class="col-md-7">
            <div class="card card-outline card-success">
                <div class="card-header"><h3 class="card-title"><i class="fas fa-list mr-1"></i> Existing Merges</h3></div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Company A</th>
                                <th class="text-center"><i class="fas fa-arrows-alt-h"></i></th>
                                <th>Company B</th>
                                <th>Created By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($merges as $m)
                            <tr>
                                <td><strong>{{ $m->company->name }}</strong></td>
                                <td class="text-center text-success"><i class="fas fa-link"></i></td>
                                <td><strong>{{ $m->mergedWith->name }}</strong></td>
                                <td><small>{{ $m->creator->name ?? '-' }}<br>{{ $m->created_at->format('d M Y') }}</small></td>
                                <td>
                                    <form action="{{ route('admin.company-merges.destroy', $m) }}" method="POST"
                                        onsubmit="return confirm('Ye merge hatayein? Dono companies ke beech future transfers nahi honge.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-unlink"></i> Remove
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">Koi merge nahi hai abhi.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>
</section>
@endsection

@push('scripts')
<script>
$(function() {
    $('.select2').select2({ placeholder: '-- Select --', allowClear: true });
});
</script>
@endpush
