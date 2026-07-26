@extends('layouts.app')

@section('title', 'Medium bearbeiten')

@section('content')
    <livewire:media.edit :media="$media" />
@endsection
