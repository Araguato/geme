<x-guest-layout>
    <div class="mb-4">
        <div class="text-lg font-semibold">{{ __('ui.license.title') }}</div>
        <div class="text-sm text-gray-600">{{ __('ui.license.subtitle') }}</div>
    </div>

    <div class="mb-4 text-sm text-gray-700">
        <div><strong>{{ __('ui.license.this_pc_code') }}</strong> {{ $status['hwid'] ?? '' }}</div>

        @if(($status['state'] ?? null) === 'active' && ($status['type'] ?? null) === 'permanent')
            <div class="mt-2 text-green-700">{{ __('ui.license.active_permanent') }}</div>
        @elseif(($status['state'] ?? null) === 'active' && ($status['type'] ?? null) === 'trial')
            <div class="mt-2 text-green-700">
                {{ __('ui.license.active_trial') }}
                @if(!is_null($status['expires_at'] ?? null))
                    ({{ __('ui.license.expires_at') }} {{ $status['expires_at'] }})
                @endif
            </div>
        @elseif(($status['state'] ?? null) === 'expired')
            <div class="mt-2 text-red-700">{{ __('ui.license.expired') }}</div>
        @elseif(($status['state'] ?? null) === 'hwid_mismatch')
            <div class="mt-2 text-red-700">{{ __('ui.license.hwid_mismatch') }}</div>
        @elseif(($status['state'] ?? null) === 'clock_rollback')
            <div class="mt-2 text-red-700">{{ __('ui.license.clock_rollback') }}</div>
        @endif
    </div>

    <form method="POST" action="{{ route('license.activate') }}">
        @csrf

        <div>
            <x-input-label for="serial" :value="__('ui.license.serial_label')" />
            <x-text-input id="serial" class="block mt-1 w-full" type="text" name="serial" :value="old('serial')" required autofocus />
            <x-input-error :messages="$errors->get('serial')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('ui.license.activate') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
