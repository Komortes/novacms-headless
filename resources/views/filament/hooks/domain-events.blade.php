@php
    $manifestPath = public_path('build/manifest.json');
    $manifest = file_exists($manifestPath) ? json_decode((string) file_get_contents($manifestPath), true) : [];
    $hasDomainEventsAsset = file_exists(public_path('hot'))
        || (is_array($manifest) && array_key_exists('resources/js/app.js', $manifest));
@endphp

@if ($hasDomainEventsAsset)
    @vite('resources/js/app.js')
    <script>
        (() => {
            if (window.__novaCmsDomainEventsBooted === true) {
                return;
            }

            window.__novaCmsDomainEventsBooted = true;

            let bootAttempts = 0;
            const boot = () => {
                if (! window.Echo) {
                    if (bootAttempts++ < 25) {
                        setTimeout(boot, 1200);
                    }
                    return;
                }

                const channelName = @js((string) config('domain_events.broadcast.channel', 'novacms.domain-events'));
                const channel = window.Echo.channel(channelName);

                channel.listen('.domain.event', (envelope) => {
                    window.dispatchEvent(new CustomEvent('novacms-domain-event', {
                        detail: envelope,
                    }));

                    if (window.Livewire?.dispatch) {
                        window.Livewire.dispatch('novacms-domain-event', { envelope });
                    }
                });
            };

            boot();
        })();
    </script>
@endif
