@extends('layouts.app')

@section('title', '500')
@section('page', '500')

@section('content')

@endsection

@push('scripts')
<script type="module">
        import Alpine from 'alpinejs';

        document.addEventListener('alpine:init', () => {
            Alpine.data('errorCountdown', () => ({
                countdown: 30,
                _timer: null,

                start() {
                    this._timer = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            clearInterval(this._timer);
                            window.location.reload();
                        }
                    }, 1000);
                },

                destroy() {
                    if (this._timer) clearInterval(this._timer);
                },
            }));
        });
    </script>
@endpush
