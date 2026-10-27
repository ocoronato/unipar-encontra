/*
 * Efeitos visuais com Alpine (já incluído pelo Livewire). Sem bibliotecas externas.
 */
document.addEventListener('alpine:init', () => {
    const reduceMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // x-spotlight: guarda a posição do mouse em --mx/--my para o brilho da classe .spotlight.
    window.Alpine.directive('spotlight', (el) => {
        el.addEventListener('pointermove', (event) => {
            const rect = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${event.clientX - rect.left}px`);
            el.style.setProperty('--my', `${event.clientY - rect.top}px`);
        });
    });

    // x-data="countUp(42)": o número sobe de 0 até o valor (o HTML já traz o valor final).
    window.Alpine.data('countUp', (target) => ({
        current: target,

        init() {
            if (target <= 0 || reduceMotion()) {
                return;
            }

            const start = performance.now();
            const duration = 900;
            this.current = 0;

            const step = (now) => {
                const progress = Math.min(1, (now - start) / duration);
                this.current = Math.round(target * (1 - Math.pow(1 - progress, 3)));

                if (progress < 1) {
                    requestAnimationFrame(step);
                }
            };

            requestAnimationFrame(step);
        },
    }));
});
