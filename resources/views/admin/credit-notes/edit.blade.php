@extends('layouts.admin')
@section('title','Edit Credit Note')
@section('content')
@include('admin.credit-notes.form', ['formAction' => route('admin.credit-notes.update',$creditNote), 'formMethod' => 'PUT'])
@endsection
