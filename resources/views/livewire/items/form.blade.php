<?php

use App\Actions\StoreItemPhotos;
use App\Enums\ItemType;
use App\Models\Category;
use App\Models\ItemPhoto;
use App\Models\Location;
use App\Models\LostFoundItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

/*
| Formulário de cadastro de objeto (perdido ou encontrado).
| O tipo vem da rota usada e é "Locked": não pode ser alterado pelo navegador.
*/
new class extends Component {
    use WithFileUploads;

    #[Locked]
    public ItemType $type;

    public string $title = '';
    public string $category_id = '';
    public string $location_id = '';
    public string $occurred_at = '';
    public string $description = '';

    /** Fotos já selecionadas e validadas. */
    public array $photos = [];

    /** Campo de upload: cada nova seleção é validada e somada a $photos. */
    public array $newPhotos = [];

    /**
     * $type vem da rota ('lost' ou 'found') e é convertido para o enum pelo Laravel.
     */
    public function mount(ItemType $type): void
    {
        $this->type = $type;
        $this->occurred_at = today()->toDateString();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('active', true)],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('active', true)],
            'occurred_at' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['array', 'max:'.ItemPhoto::MAX_PER_ITEM],
            'photos.*' => ItemPhoto::rules(),
        ];
    }

    public function updatedNewPhotos(): void
    {
        $remaining = ItemPhoto::MAX_PER_ITEM - count($this->photos);

        try {
            $this->validate(
                ['newPhotos' => ['array', "max:{$remaining}"], 'newPhotos.*' => ItemPhoto::rules()],
                ['newPhotos.max' => 'São permitidas no máximo '.ItemPhoto::MAX_PER_ITEM.' fotos por objeto.'],
            );

            $this->photos = [...$this->photos, ...$this->newPhotos];
        } finally {
            $this->newPhotos = [];
        }
    }

    public function removePhoto(int $index): void
    {
        if (isset($this->photos[$index])) {
            $this->photos[$index]->delete(); // apaga o arquivo temporário
            unset($this->photos[$index]);
            $this->photos = array_values($this->photos);
        }
    }

    public function save(StoreItemPhotos $storePhotos): void
    {
        $this->authorize('create', LostFoundItem::class);

        $data = $this->validate();

        DB::transaction(function () use ($data, $storePhotos) {
            // status = active e approval_status = pending são os padrões do model.
            $item = Auth::user()->lostFoundItems()->create(
                Arr::except($data, 'photos') + ['type' => $this->type],
            );

            $storePhotos($item, $this->photos);
        });

        session()->flash('status', 'Objeto cadastrado com sucesso e enviado para análise.');

        $this->redirectRoute('items.mine', navigate: true);
    }

    public function rendering($view): void
    {
        $view->title($this->type === ItemType::Lost ? 'Cadastrar Perdido' : 'Cadastrar Encontrado');
    }

    public function with(): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->get(['id', 'name']),
            'locations' => Location::active()->orderBy('name')->get(['id', 'name']),
            'isLost' => $this->type === ItemType::Lost,
            'maxPhotos' => ItemPhoto::MAX_PER_ITEM,
        ];
    }
}; ?>

<div class="mx-auto w-full max-w-3xl">
    <div class="mb-6 flex items-start gap-4">
        <div @class([
            'rounded-xl p-3',
            'bg-orange-100 text-orange-700 dark:bg-orange-400/15 dark:text-orange-300' => $isLost,
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-400/15 dark:text-emerald-300' => ! $isLost,
        ])>
            <flux:icon :name="$isLost ? 'exclamation-circle' : 'hand-raised'" class="size-7" />
        </div>
        <div>
            <flux:heading size="xl" level="1">{{ $isLost ? 'Cadastrar objeto perdido' : 'Cadastrar objeto encontrado' }}</flux:heading>
            <flux:subheading>
                {{ $isLost
                    ? 'Conte o que você perdeu. Quanto mais detalhes, maiores as chances de encontrar.'
                    : 'Obrigado por ajudar! Informe o que você encontrou para que o dono possa recuperá-lo.' }}
            </flux:subheading>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        <flux:card class="space-y-6">
            <flux:input
                wire:model="title"
                label="Título"
                :placeholder="$isLost ? 'Ex.: Carteira marrom de couro' : 'Ex.: Chave com chaveiro azul'"
                maxlength="150"
                required
            />

            <div class="grid gap-6 sm:grid-cols-3">
                <flux:select wire:model="category_id" label="Categoria" placeholder="Selecione..." required>
                    @foreach ($categories as $category)
                        <flux:select.option :value="$category->id">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="location_id" :label="$isLost ? 'Onde perdeu?' : 'Onde encontrou?'" placeholder="Selecione..." required>
                    @foreach ($locations as $location)
                        <flux:select.option :value="$location->id">{{ $location->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input
                    type="date"
                    wire:model="occurred_at"
                    :label="$isLost ? 'Quando perdeu?' : 'Quando encontrou?'"
                    max="{{ today()->toDateString() }}"
                    required
                />
            </div>

            <flux:textarea
                wire:model="description"
                label="Descrição"
                rows="5"
                maxlength="2000"
                required
                :placeholder="$isLost
                    ? 'Descreva o objeto: cor, marca, tamanho, detalhes marcantes e o que havia dentro, se for o caso.'
                    : 'Descreva o objeto e o local exato onde foi encontrado.'"
            />

            @unless ($isLost)
                <flux:callout icon="light-bulb" color="blue">
                    <flux:callout.text>
                        Dica: não descreva detalhes que só o dono saberia (como o conteúdo de uma carteira).
                        Eles serão usados pela administração para confirmar quem é o verdadeiro dono.
                    </flux:callout.text>
                </flux:callout>
            @endunless
        </flux:card>

        {{-- Fotos --}}
        <flux:card class="space-y-4">
            <div>
                <flux:heading>Fotos <span class="font-normal text-zinc-500">(opcional)</span></flux:heading>
                <flux:text class="mt-1">
                    Até {{ $maxPhotos }} fotos JPG, PNG ou WEBP, com no máximo 5 MB cada.
                    Evite fotos que mostrem dados pessoais, como números de documentos.
                </flux:text>
            </div>

            @if (count($photos) < $maxPhotos)
                <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-zinc-300 p-6 text-center transition hover:border-accent dark:border-zinc-600">
                    <flux:icon.photo class="size-8 text-zinc-400" />
                    <span class="text-sm font-medium text-accent-content">Clique para selecionar fotos</span>
                    <span class="text-xs text-zinc-500">{{ count($photos) }} de {{ $maxPhotos }} selecionadas</span>
                    <input type="file" wire:model="newPhotos" multiple accept="image/jpeg,image/png,image/webp" class="sr-only">
                </label>
            @endif

            <div wire:loading.flex wire:target="newPhotos" class="items-center gap-2 text-sm text-zinc-500">
                <flux:icon.arrow-path class="size-4 animate-spin" /> Enviando fotos...
            </div>

            {{-- Erros das fotos (as chaves têm índice: newPhotos.0, photos.1...) --}}
            @foreach (collect($errors->get('newPhotos'))->merge($errors->get('newPhotos.*'))->merge($errors->get('photos'))->merge($errors->get('photos.*'))->flatten()->unique() as $message)
                <p class="text-sm font-medium text-red-500 dark:text-red-400">{{ $message }}</p>
            @endforeach

            @if ($photos)
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                    @foreach ($photos as $index => $photo)
                        <div class="relative" wire:key="photo-{{ $photo->getFilename() }}">
                            <img src="{{ $photo->temporaryUrl() }}" alt="Foto {{ $index + 1 }}" class="aspect-square w-full rounded-lg object-cover">
                            <button
                                type="button"
                                wire:click="removePhoto({{ $index }})"
                                class="absolute top-1 right-1 rounded-full bg-black/60 p-1 text-white hover:bg-black"
                                aria-label="Remover foto {{ $index + 1 }}"
                            >
                                <flux:icon.x-mark variant="micro" />
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <flux:button :href="route('home')" variant="ghost" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit" variant="primary" icon="paper-airplane" wire:loading.attr="disabled" wire:target="save, newPhotos">
                Enviar para análise
            </flux:button>
        </div>
    </form>
</div>
