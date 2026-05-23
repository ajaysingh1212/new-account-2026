@extends('layouts.admin')
@section('title', 'Edit Cost Center')
@section('breadcrumb')<li class="breadcrumb-item"><a href="{{ route('admin.cost-centers.index') }}">Cost Centers</a></li><li class="breadcrumb-item active">Edit</li>@endsection
@section('content')
@include('admin.cost-centers.partials-form')
@endsection
