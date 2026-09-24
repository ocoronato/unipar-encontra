<?php

use App\Actions\StoreItemPhotos;
use App\Enums\ApprovalStatus;
use App\Enums\ItemType;
use App\Models\Category;
use App\Models\ItemPhoto;
use App\Models\Location;
use App\Models\LostFoundItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

/*
| Formulário de objeto (perdido ou encontrado), usado para cadastrar e editar.
| - Cadastro: o tipo vem da rota (/objetos/perdido/novo ou /objetos/encontrado/novo).
| - Edição: o objeto vem da rota (/objetos/{item}/editar).
| $item e $type são "Locked": não podem ser alterados pelo navegador.
*/
new class extends Component {
    use WithFileUploads;

    /*
     * O Livewire preenche estas propriedades a partir da rota:
     * - $item na edição (/objetos/{item}/editar);
     * - $type no cadastro (valor 'lost' ou 'found' definido na rota).
     */
    #[Locked]
    public ?LostFoundItem $item = null;

    #[Locked]
    public ?ItemType $type = null;

    public string $title = '';
    public string $category_id = '';
    public string $location_id = '';
    public string $occurred_at = '';
    public string $description = '';

    /** Fotos novas, já validadas. */
    public array $photos = [];

    /** Campo de upload: cada nova seleção é validada e somada a $photos. */
    public array $newPhotos = [];

    /** Edição: fotos atuais marcadas para remoção (só são apagadas ao salvar). */
    public array $removedPhotoIds = [];

    public function mount(): void
    {
        if ($this->item) {
            $this->authorize('update', $this->item);

            $this->type = $this->item->type;
            $this->title = $this->item->title;
            $this->category_id = (string) $this->item->category_id;
            $this->location_id = (string) $this->item->location_id;
            $this->occurred_at = $this->item->occurred_at->toDateString();
            $this->description = $this->item->description;
        } else {
            $this->occurred_at = today()->toDateString();
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('active', true)],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('active', true)],
            'occurred_at' => ['required', 'date', 'before_or_equal:today'],
            'description' => ['required', 'string', 'min:10', 'max:2000'],
            'photos' => ['array', 'max:'.$this->remainingPhotoSlots(countNew: false)],
            'photos.*' => ItemPhoto::rules(),
        ];
    }

    public function messages(): array
    {
        $limit = 'São permitidas no máximo '.ItemPhoto::MAX_PER_ITEM.' fotos por objeto.';

        return ['photos.max' => $limit, 'newPhotos.max' => $limit];
    }

    public function updatedNewPhotos(): void
    {
        try {
            $this->validate([
                'newPhotos' => ['array', 'max:'.$this->remainingPhotoSlots()],
                'newPhotos.*' => ItemPhoto::rules(),
            ]);

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

    public function removeExistingPhoto(int $photoId): void
    {
        $this->removedPhotoIds = array_values(array_unique([...$this->removedPhotoIds, $photoId]));
    }

    public function save(StoreItemPhotos $storePhotos): void
    {
        $this->item
            ? $this->authorize('update', $this->item)
            : $this->authorize('create', LostFoundItem::class);

        $data = Arr::except($this->validate(), 'photos');

        DB::transaction(function () use ($data, $storePhotos) {
            if ($this->item) {
                // Conteúdo alterado precisa passar pela moderação novamente.
                $this->item->fill($data)->forceFill(['approval_status' => ApprovalStatus::Pending])->save();

                // Só apaga fotos deste objeto (o evento do model apaga também o arquivo).
                $this->item->photos()->whereIn('id', $this->removedPhotoIds)->get()->each->delete();

                $item = $this->item;
            } else {
                // status = active e approval_status = pending são os padrões do model.
                $item = Auth::user()->lostFoundItems()->create($data + ['type' => $this->type]);
            }

            $storePhotos($item, $this->photos);
        });

        session()->flash('status', $this->item
            ? 'Objeto atualizado e enviado novamente para análise.'
            : 'Objeto cadastrado com sucesso e enviado para análise.');

        $this->redirectRoute('items.mine', navigate: true);
    }

    /**
     * Fotos atuais do objeto que continuam (edição).
     */
    private function keptPhotos(): Collection
    {
        return $this->item?->photos->whereNotIn('id', $this->removedPhotoIds)->values() ?? collect();
    }

    private function remainingPhotoSlots(bool $countNew = true): int
    {
        return ItemPhoto::MAX_PER_ITEM - $this->keptPhotos()->count() - ($countNew ? count($this->photos) : 0);
    }

    public function rendering($view): void
    {
        $view->title(match (true) {
            $this->item !== null => 'Editar Objeto',
            $this->type === ItemType::Lost => 'Cadastrar Perdido',
            default => 'Cadastrar Encontrado',
        });
    }

    public function with(): array
    {
        return [
            'categories' => Category::active()->orderBy('name')->get(['id', 'name']),
            'locations' => Location::active()->orderBy('name')->get(['id', 'name']),
            'isLost' => $this->type === ItemType::Lost,
            'isEditing' => $this->item !== null,
            'keptPhotos' => $this->keptPhotos(),
            'photoCount' => $this->keptPhotos()->count() + count($this->photos),
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
            <flux:icon :name="$isEditing ? 'pencil-square' : ($isLost ? 'exclamation-circle' : 'hand-raised')" class="size-7" />
        </div>
        <div>
            <flux:heading size="xl" level="1">
                {{ $isEditing ? 'Editar' : 'Cadastrar' }} objeto {{ $isLost ? 'perdido' : 'encontrado' }}
            </flux:heading>
            <flux:subheading>
                @if ($isEditing)
                    Corrija ou complete as informações da sua publicação.
                @elseif ($isLost)
                    Conte o que você perdeu. Quanto mais detalhes, maiores as chances de encontrar.
                @else
                    Obrigado por ajudar! Informe o que você encontrou para que o dono possa recuperá-lo.
                @endif
            </flux:subheading>
        </div>
    </div>

    @if ($isEditing)
        <flux:callout icon="information-circle" color="blue" class="mb-6">
            <flux:callout.text>
                Ao salvar, a publicação volta para análise da administração e fica fora da busca até ser aprovada novamente.
            </flux:callout.text>
        </flux:callout>
    @endif

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

            @if ($photoCount < $maxPhotos)
                <label class="flex cursor-pointer flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed border-zinc-300 p-6 text-center transition hover:border-accent dark:border-zinc-600">
                    <flux:icon.photo class="size-8 text-zinc-400" />
                    <span class="text-sm font-medium text-accent-content">Clique para selecionar fotos</span>
                    <span class="text-xs text-zinc-500">{{ $photoCount }} de {{ $maxPhotos }} fotos</span>
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

            @if ($photoCount > 0)
                <div class="grid grid-cols-3 gap-3 sm:grid-cols-5">
                    @foreach ($keptPhotos as $photo)
                        <x-removable-photo :src="$photo->url()" wire:click="removeExistingPhoto({{ $photo->id }})" wire:key="kept-{{ $photo->id }}" />
                    @endforeach

                    @foreach ($photos as $index => $photo)
                        <x-removable-photo :src="$photo->temporaryUrl()" wire:click="removePhoto({{ $index }})" wire:key="new-{{ $photo->getFilename() }}" />
                    @endforeach
                </div>
            @endif
        </flux:card>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <flux:button :href="$isEditing ? route('items.mine') : route('home')" variant="ghost" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit" variant="primary" :icon="$isEditing ? 'check' : 'paper-airplane'" wire:loading.attr="disabled" wire:target="save, newPhotos">
                {{ $isEditing ? 'Salvar alterações' : 'Enviar para análise' }}
            </flux:button>
        </div>
    </form>
</div>
