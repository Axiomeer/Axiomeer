@extends('layouts.app')

@section('title', $page)
@section('page-title', $page)

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <iconify-icon icon="iconamoon:construction-duotone" class="fs-48 text-warning d-block mb-3"></iconify-icon>
                <h4 class="fw-bold mb-2">{{ $page }}</h4>
                <p class="text-muted mb-0">This page will be built in a later stage.</p>
            </div>
        </div>
    </div>
</div>
@endsection
