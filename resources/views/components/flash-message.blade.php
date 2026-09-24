{{--
    Mensagem enviada com redirect: session()->flash('status', '...') ou ('error', '...').
    Para ações que não mudam de página, use Flux::toast().
--}}
@if (session('status'))
    <flux:callout variant="success" icon="check-circle" class="mb-6" :heading="session('status')" />
@endif

@if (session('error'))
    <flux:callout variant="danger" icon="x-circle" class="mb-6" :heading="session('error')" />
@endif
