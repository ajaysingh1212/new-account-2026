@extends('layouts.admin')
@section('title', 'Create Sub Cost Center')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.sub-cost-centers.index') }}">Sub Cost Centers</a></li><li class="breadcrumb-item active">Create</li>@endsection
@section('content')
@include('admin.sub-cost-centers.partials-form')
@endsection
