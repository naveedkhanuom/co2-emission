@extends('layouts.app')

@section('title', 'General Settings')
@section('page-title', 'General Settings')

@push('styles')
<style>
.gset-app { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; max-width: 760px; }
.gset-app .card { background:#fff; border:1px solid var(--gray-200); border-radius:16px; box-shadow:0 2px 8px rgba(0,0,0,.06); }
.gset-app .card .hd { padding:18px 22px; border-bottom:1px solid var(--gray-200); background:linear-gradient(180deg,var(--gray-50),#fff); border-radius:16px 16px 0 0; }
.gset-app .card .hd h2 { font-size:1.1rem; font-weight:700; margin:0; color:var(--gray-800); display:flex; align-items:center; gap:10px; }
.gset-app .card .hd p { margin:4px 0 0; font-size:.85rem; color:var(--gray-600); }
.gset-app .card .bd { padding:22px; }
.gset-app label.form-label { font-weight:600; font-size:.875rem; color:var(--gray-800); }
.gset-app .logo-frame { height:64px; width:auto; max-width:220px; border:1px solid var(--gray-200); background:#fff; border-radius:10px; padding:6px; }
.gset-app .btn-brand { background:linear-gradient(135deg,var(--primary-green) 0%,var(--light-green) 100%); border:none; color:#fff; font-weight:600; padding:10px 22px; border-radius:10px; }
.gset-app .btn-brand:hover { background:linear-gradient(135deg,var(--dark-green) 0%,var(--primary-green) 100%); color:#fff; }
.gset-app .hint { font-size:.8rem; color:var(--gray-600); }
</style>
@endpush

@section('content')
<div id="content">
    @include('layouts.top-nav')

    <div class="container-fluid py-4 gset-app">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card">
            <div class="hd">
                <h2><i class="fas fa-sliders-h" style="color:var(--primary-green)"></i> General Settings</h2>
                <p>Branding shown on the login screen and used as the sidebar fallback logo.</p>
            </div>
            <div class="bd">
                <form method="POST" action="{{ route('settings.general.update') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-4">
                        <label class="form-label" for="app_name">Application Name</label>
                        <input type="text" name="app_name" id="app_name" class="form-control" value="{{ old('app_name', $appName) }}" placeholder="e.g. Green Crescent GHG Monitor">
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Application Logo</label>
                        <div class="d-flex align-items-center gap-3 mb-2">
                            @php
                                $logoUrl = app_logo_url();
                            @endphp
                            <img id="appLogoPreview" src="{{ $logoUrl ?? asset('logo.png') }}" alt="App logo" class="logo-frame"
                                 onerror="this.style.display='none'">
                        </div>
                        <input type="file" name="app_logo" id="app_logo" class="form-control" accept="image/png,image/jpeg,image/webp,image/svg+xml">
                        <div class="hint mt-1">PNG, JPG, WEBP or SVG, up to 2&nbsp;MB.</div>

                        @if($appLogo)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                <label class="form-check-label hint" for="remove_logo">Remove the current logo (revert to default)</label>
                            </div>
                        @endif
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-brand"><i class="fas fa-save me-2"></i>Save Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('app_logo')?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (file) {
            const img = document.getElementById('appLogoPreview');
            img.src = URL.createObjectURL(file);
            img.style.display = '';
        }
    });
</script>
@endsection
