@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite('resources/js/app.js')
    <script>
        (() => {
            if (window.__novaCmsDomainEventsBooted === true) {
                return;
            }

            window.__novaCmsDomainEventsBooted = true;

            const boot = () => {
                if (! window.Echo) {
                    setTimeout(boot, 1200);
                    return;
                }

                const channelName = @js((string) config('domain_events.broadcast.channel', 'novacms.domain-events'));
                const channel = window.Echo.channel(channelName);

                channel.listen('.domain.event', (envelope) => {
                    window.dispatchEvent(new CustomEvent('novacms-domain-event', {
                        detail: envelope,
                    }));

                    if (window.Livewire?.dispatch) {
                        window.Livewire.dispatch('novacms-domain-event');
                    }
                });
            };

            boot();
        })();
    </script>
@endif
