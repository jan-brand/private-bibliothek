@extends('layouts.app')

@section('title', 'Anmelden')

@section('content')
    <div class="mx-auto max-w-md">
        <livewire:auth.login />
    </div>
@endsection
