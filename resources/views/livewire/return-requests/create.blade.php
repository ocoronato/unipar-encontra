<?php

use App\Models\LostFoundItem;
use App\Models\ReturnRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

/*
| Solicitação de devolução ("Este objeto é meu").
| A ReturnRequestPolicy impede: objeto perdido, não aprovado, em devolução ou devolvido,
| pedido do próprio autor da publicação e pedidos repetidos.
*/
new #[Title('Solicitar Devolução')] class extends Component {
    #[Locked]
    public LostFoundItem $item;

    public string $message = '';

    public function mount(LostFoundItem $item): void
    {
        $this->authorize('create', [ReturnRequest::class, $item]);
    }

    public function save(): void
    {
        // Verifica de novo: a situação do objeto pode ter mudado desde que a página abriu.
        $this->authorize('create', [ReturnRequest::class, $this->item]);

        $validated = $this->validate([
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ]);

        Auth::user()->returnRequests()->create([
            'lost_found_item_id' => $this->item->id,
            'message' => $validated['message'],
        ]);

        session()->flash('status', 'Solicitação enviada com sucesso! A administração vai analisar e a resposta aparecerá aqui.');

        $this->redirectRoute('return-requests.mine', navigate: true);
    }

    public function with(): array
    {
        return ['item' => $this->item->load(['category', 'location', 'photos'])];
    }
}; ?>

<div class="mx-auto w-full max-w-2xl">
    <div class="mb-6">
        <flux:button variant="ghost" size="sm" icon="arrow-left" :href="route('items.show', $item)" wire:navigate>
            Voltar para o objeto
        </flux:button>
    </div>

    <flux:heading size="xl" level="1">Solicitar devolução</flux:heading>
    <flux:subheading>Conte por que este objeto é seu. A administração vai comparar as informações antes de aprovar.</flux:subheading>

    {{-- Resumo do objeto --}}
    <flux:card class="mt-6 flex items-center gap-4">
        <x-item-thumbnail :item="$item" size="lg" />
        <div class="min-w-0">
            <p class="truncate font-semibold">{{ $item->title }}</p>
            <flux:text class="text-sm">
                {{ $item->category->name }} · {{ $item->location->name }} · Encontrado em {{ $item->occurred_at->format('d/m/Y') }}
            </flux:text>
        </div>
    </flux:card>

    <form wire:submit="save" class="mt-6 space-y-6">
        <flux:card class="space-y-4">
            <flux:textarea
                wire:model="message"
                label="Por que você acredita que este objeto pertence a você?"
                rows="6"
                maxlength="2000"
                required
                placeholder="Ex.: É minha carteira marrom. Dentro há uma foto 3x4, um cartão de ônibus e um recibo da cantina. Perdi na terça, depois da aula no Bloco 2."
            />

            <flux:callout icon="light-bulb" color="blue">
                <flux:callout.text>
                    Informe detalhes que só o dono saberia: marcas, arranhões, adesivos, o que havia dentro,
                    quando e onde você perdeu. Sua mensagem é vista apenas pela administração.
                </flux:callout.text>
            </flux:callout>
        </flux:card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <flux:button :href="route('items.show', $item)" variant="ghost" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="save">
                Enviar solicitação
            </flux:button>
        </div>
    </form>
</div>
