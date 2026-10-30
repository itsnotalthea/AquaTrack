@php
    $allErrors = $errors->all();
@endphp

<div id="appFlash" class="flash-stack" aria-live="polite" aria-atomic="true">
    @if (session('status'))
        <div class="flash flash-success" data-auto-dismiss="8000" role="status">
            <x-icon name="check" />
            <div class="flash-body">{{ session('status') }}</div>
            <button type="button" class="flash-close" data-flash-close aria-label="Dismiss">
                <x-icon name="close" />
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="flash flash-error" data-auto-dismiss="8000" role="alert">
            <x-icon name="warning-diamond" />
            <div class="flash-body">{{ session('error') }}</div>
            <button type="button" class="flash-close" data-flash-close aria-label="Dismiss">
                <x-icon name="close" />
            </button>
        </div>
    @endif

    @if ($allErrors !== [])
        <div class="flash flash-error" data-auto-dismiss="8000" role="alert">
            <x-icon name="warning-diamond" />
            <div class="flash-body">
                <strong>Please fix the following:</strong>
                <ul>
                    @foreach ($allErrors as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="flash-close" data-flash-close aria-label="Dismiss">
                <x-icon name="close" />
            </button>
        </div>
    @endif
</div>
