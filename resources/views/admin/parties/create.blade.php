@extends('layouts.admin')
@section('title', 'Create Party')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('admin.parties.index') }}">Parties</a></li>
    <li class="breadcrumb-item active">Create</li>
@endsection

@section('content')
@include('admin.parties.partials.form')
@endsection
