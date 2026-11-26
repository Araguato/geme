@php
    $onboardingSteps = \App\Services\OnboardingChecklist::steps();
    $onboardingDone = count(array_filter($onboardingSteps, fn ($s) => $s['done']));
@endphp
@if($onboardingDone < count($onboardingSteps))
<div class="card mb-3 border-primary" id="onboarding-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h5 mb-0"><i class="bi bi-rocket-takeoff"></i> Primeros pasos</h2>
            <span class="badge text-bg-primary">{{ $onboardingDone }} de {{ count($onboardingSteps) }}</span>
        </div>
        <div class="progress mb-3" style="height:6px;">
            <div class="progress-bar" style="width: {{ round($onboardingDone / count($onboardingSteps) * 100) }}%"></div>
        </div>
        <ul class="list-group list-group-flush">
            @foreach($onboardingSteps as $step)
                <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                    <div>
                        @if($step['done'])
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span class="text-muted">{{ $step['label'] }}</span>
                        @else
                            <i class="bi bi-circle text-secondary"></i>
                            <strong>{{ $step['label'] }}</strong>
                            <div class="small text-muted">{{ $step['hint'] }}</div>
                        @endif
                    </div>
                    @unless($step['done'])
                        <a href="{{ $step['route'] }}" class="btn btn-sm btn-outline-primary">Configurar</a>
                    @endunless
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endif
