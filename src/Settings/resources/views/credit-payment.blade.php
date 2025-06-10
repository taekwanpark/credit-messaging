@if($paymentUrl)
    <x-orchid-icon path="bs.check-circle" class="text-success"/>
    <p>Site credit has been saved. Click below to proceed with payment.</p>
    <button
            type="button"
            onclick="window.open('{{ $paymentUrl }}', '_blank')"
            class="btn btn-primary"
    >
        결제하기 (새 창으로)
    </button>
@endif
