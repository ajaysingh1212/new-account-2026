@extends('layouts.admin')
@section('title','Terms & Conditions')
@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title m-0">Terms Master</h3><a href="{{ route('admin.terms.create') }}" class="btn btn-primary btn-sm"><i class="fas fa-plus mr-1"></i> New Terms</a></div>
    <div class="card-body table-responsive">
        <table id="termsTable" class="table table-hover"><thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Default</th><th>Attachment</th><th></th></tr></thead><tbody>
        @foreach($templates as $template)<tr><td><b>{{ $template->title }}</b><br><small>{{ \Illuminate\Support\Str::limit($template->content,80) }}</small></td><td>{{ ucfirst($template->document_type) }}</td><td>{{ ucfirst($template->status) }}</td><td>{{ $template->is_default ? 'Yes' : 'No' }}</td><td>@if($template->attachment)<a target="_blank" href="{{ asset('storage/'.$template->attachment) }}">View</a>@else - @endif</td><td><a href="{{ route('admin.terms.edit',$template) }}" class="btn btn-info btn-sm"><i class="fas fa-edit"></i></a></td></tr>@endforeach
        </tbody></table>
    </div>
</div>
@endsection
@push('scripts')<script>$('#termsTable').DataTable({pageLength:25});</script>@endpush
