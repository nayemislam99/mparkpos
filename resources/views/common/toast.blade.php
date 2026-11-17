<script>
    @if (Session::has('success'))
        showToast('success', @json(Session::get('success')));
    @endif

    @if (Session::has('info'))
        showToast('info', @json(Session::get('info')));
    @endif

    @if (Session::has('error'))
        showToast('error', @json(Session::get('error')));
    @endif

    @if (Session::has('warning') && is_string(Session::get('warning')))
        showToast('warning', @json(Session::get('warning')));
    @endif
</script>