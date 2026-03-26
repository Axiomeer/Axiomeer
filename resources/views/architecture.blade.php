@extends('layouts.app')

@section('title', 'System Architecture')
@section('page-title', 'System Architecture')

@push('styles')
<style>
    .arch-page-header {
        background: linear-gradient(135deg, rgba(var(--bs-primary-rgb), 0.08), rgba(var(--bs-info-rgb), 0.04));
        border: 1px solid var(--bs-border-color);
        border-radius: 12px;
        padding: 20px 24px;
    }
</style>
@endpush

@section('content')

<div class="row mb-3">
    <div class="col-12">
        <div class="arch-page-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div>
                <h4 class="fw-bold mb-1">
                    <iconify-icon icon="iconamoon:layers-duotone" class="text-primary me-2"></iconify-icon>
                    System Architecture
                </h4>
                <p class="text-muted fs-13 mb-0">
                    End-to-end view of the Axiomeer multi-agent RAG pipeline — from user query to grounded, verified answer.
                </p>
            </div>
            <div class="d-flex gap-2">
                <span class="badge bg-primary-subtle text-primary fs-11 px-3 py-2">
                    <iconify-icon icon="iconamoon:lightning-2-duotone" class="me-1"></iconify-icon>
                    Laravel 12 / PHP 8.2
                </span>
                <span class="badge bg-info-subtle text-info fs-11 px-3 py-2">
                    <iconify-icon icon="iconamoon:cloud-duotone" class="me-1"></iconify-icon>
                    Azure AI Platform
                </span>
                <span class="badge bg-success-subtle text-success fs-11 px-3 py-2">
                    <iconify-icon icon="iconamoon:shield-yes-duotone" class="me-1"></iconify-icon>
                    Three-Ring Defense
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-3 p-lg-4">
                @include('partials.architecture-diagram')
            </div>
        </div>
    </div>
</div>

{{-- Component Stats Row --}}
<div class="row g-3 mt-1">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <iconify-icon icon="iconamoon:settings-duotone" class="fs-28 text-purple mb-2" style="color:#8b5cf6;"></iconify-icon>
                <h4 class="fw-bold mb-0">4</h4>
                <div class="text-muted fs-12">AI Agents</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <iconify-icon icon="iconamoon:shield-yes-duotone" class="fs-28 mb-2" style="color:#10b981;"></iconify-icon>
                <h4 class="fw-bold mb-0">3</h4>
                <div class="text-muted fs-12">Defense Rings</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <iconify-icon icon="iconamoon:cloud-duotone" class="fs-28 mb-2" style="color:#0ea5e9;"></iconify-icon>
                <h4 class="fw-bold mb-0">8</h4>
                <div class="text-muted fs-12">Azure Services</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-3">
                <iconify-icon icon="iconamoon:layers-duotone" class="fs-28 mb-2 text-primary"></iconify-icon>
                <h4 class="fw-bold mb-0">6</h4>
                <div class="text-muted fs-12">Architecture Layers</div>
            </div>
        </div>
    </div>
</div>

@endsection
