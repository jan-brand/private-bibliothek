@extends('layouts.app')

@section('title', 'Exemplar bearbeiten')

@section('content')
    <div class="space-y-6">
        <livewire:copies.barcode-scanner :copy="$copy" />
        <livewire:copies.edit :copy="$copy" />
    </div>
@endsection
