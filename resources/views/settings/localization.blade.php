@extends('layout')

@section('content')
    <h1>{{ __('ui.settings.localization_title') }}</h1>
    <p class="text-muted">{{ __('ui.settings.localization_help') }}</p>

    <form action="{{ route('settings.localization.update') }}" method="POST" class="mt-3" style="max-width: 520px;">
        @csrf

        <div class="mb-3">
            <label class="form-label">{{ __('ui.settings.language') }}</label>
            <select name="locale" class="form-select" required>
                @php($currentLocale = old('locale', $locale ?? 'es'))
                <option value="es" {{ $currentLocale === 'es' ? 'selected' : '' }}>Español</option>
                <option value="en" {{ $currentLocale === 'en' ? 'selected' : '' }}>English</option>
                <option value="pt" {{ $currentLocale === 'pt' ? 'selected' : '' }}>Português</option>
                <option value="de" {{ $currentLocale === 'de' ? 'selected' : '' }}>Deutsch</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">{{ __('ui.settings.currency_default') }}</label>
            <select name="currency_default" class="form-select" required>
                @php($currentCurrency = old('currency_default', $currencyDefault ?? 'USD'))
                <option value="USD" {{ strtoupper($currentCurrency) === 'USD' ? 'selected' : '' }}>USD ($)</option>
                <option value="EUR" {{ strtoupper($currentCurrency) === 'EUR' ? 'selected' : '' }}>EUR (€)</option>
                <option value="CLP" {{ strtoupper($currentCurrency) === 'CLP' ? 'selected' : '' }}>CLP (Peso chileno)</option>
                <option value="BRL" {{ strtoupper($currentCurrency) === 'BRL' ? 'selected' : '' }}>BRL (R$)</option>
                <option value="VES" {{ strtoupper($currentCurrency) === 'VES' ? 'selected' : '' }}>VES (Bs)</option>
            </select>
            <div class="form-text">{{ __('ui.settings.currency_default_help') }}</div>
        </div>

        <div class="row g-2">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">{{ __('ui.settings.eur_per_usd') }}</label>
                    <input type="number" step="0.000001" name="eur_per_usd" class="form-control" value="{{ old('eur_per_usd', $eurPerUsd ?? '') }}" placeholder="0.92">
                    <div class="form-text">{{ __('ui.settings.eur_per_usd_help') }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">{{ __('ui.settings.clp_per_usd') }}</label>
                    <input type="number" step="0.000001" name="clp_per_usd" class="form-control" value="{{ old('clp_per_usd', $clpPerUsd ?? '') }}" placeholder="950">
                    <div class="form-text">{{ __('ui.settings.clp_per_usd_help') }}</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">{{ __('ui.settings.brl_per_usd') }}</label>
                    <input type="number" step="0.000001" name="brl_per_usd" class="form-control" value="{{ old('brl_per_usd', $brlPerUsd ?? '') }}" placeholder="5.10">
                    <div class="form-text">{{ __('ui.settings.brl_per_usd_help') }}</div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">{{ __('ui.save') }}</button>
    </form>
@endsection
