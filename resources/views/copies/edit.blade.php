@extends('layouts.app')

@section('title', 'Exemplar bearbeiten')

@section('content')
    <livewire:copies.edit :copy="$copy" />
@endsection
