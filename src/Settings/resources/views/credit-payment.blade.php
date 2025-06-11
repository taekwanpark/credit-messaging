<div class="flex justify-end">
    {{--    <x-orchid-icon path="bs.check-circle" class="text-success"/>--}}
    {{--    <p>Site credit has been saved. Click below to proceed with payment.</p>--}}
    <button
            type="button"
            onclick="window.open('{{ $paymentUrl }}', '_blank')"
            class="btn btn-primary"
    >
        {{number_format(request()->input('purchaseAmount'))}}원 결제하기
    </button>
</div>

<script>
    window.addEventListener('message', function(event) {
        const data = event.data;
        if (data?.type === 'payment_completed') {
            if (data.success) {
                window.location.href = data?.redirectUrl;
            } else {
                console.log('결제에 실패했습니다.');
            }
        }
    });
</script>