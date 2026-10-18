<?php

use App\Models\Category;
use Livewire\Volt\Component;
use Illuminate\Validation\Rule;

new class extends Component {
    public string $bio = '';
    public string $phone = '';
    public string $address = '';
    public ?float $latitude = null;
    public ?float $longitude = null;
    public int $coverage_radius_km = 10;
    public array $selectedCategories = [];

    public bool $hasOther = false;
    public string $other_service = '';

    public function mount(): void
    {
        $user = auth()->user();
        abort_unless($user->isProfessional(), 403);

        $p = $user->professional;

        $this->bio = $p->bio ?? '';
        $this->phone = $p->phone ?? '';
        $this->address = $p->address ?? '';
        $this->latitude = $p->latitude !== null ? (float) $p->latitude : null;
        $this->longitude = $p->longitude !== null ? (float) $p->longitude : null;
        $this->coverage_radius_km = $p->coverage_radius_km;
        $this->selectedCategories = $p->categories()->pluck('categories.id')->map(fn($id) => (string) $id)->toArray();
        $this->other_service = $p->other_service ?? '';
        $this->hasOther = $this->other_service !== '';
    }

    public function with(): array
    {
        return ['categories' => Category::orderBy('name')->get()];
    }

    public function save(): void
    {
        $this->validate(
            [
                'bio' => ['nullable', 'string', 'max:1000'],
                'phone' => ['required', 'string', 'max:20'],
                'address' => ['required', 'string', 'max:255'],
                'latitude' => ['required', 'numeric', 'between:-90,90'],
                'longitude' => ['required', 'numeric', 'between:-180,180'],
                'coverage_radius_km' => ['required', 'integer', 'between:1,100'],
                'selectedCategories' => ['array'],
                'selectedCategories.*' => ['exists:categories,id'],
                'other_service' => [Rule::requiredIf($this->hasOther), 'nullable', 'string', 'max:100'],
            ],
            [
                'latitude.required' => 'Marca tu ubicación en el mapa.',
                'other_service.required' => 'Escribe cuál es el servicio que ofreces.',
            ],
        );

        if (empty($this->selectedCategories) && !$this->hasOther) {
            $this->addError('selectedCategories', 'Elige al menos una categoría o indica otro servicio.');
            return;
        }

        $professional = auth()->user()->professional;

        $professional->update([
            'bio' => $this->bio,
            'other_service' => $this->hasOther ? trim($this->other_service) : null,
            'phone' => $this->phone,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'coverage_radius_km' => $this->coverage_radius_km,
        ]);
    }
}; ?>

<div>
    <form wire:submit="save" class="space-y-6">

        @if (session('status'))
            <div class="p-3 bg-green-100 text-green-800 rounded-md text-sm">
                {{ session('status') }}
            </div>
        @endif

        <div>
            <x-input-label for="phone" value="Teléfono" />
            <x-text-input wire:model="phone" id="phone" type="text" class="block mt-1 w-full" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="bio" value="Sobre ti" />
            <textarea wire:model="bio" id="bio" rows="3"
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
            <x-input-error :messages="$errors->get('bio')" class="mt-2" />
        </div>

        <div>
            <x-input-label value="Servicios que ofreces" />
            <div class="mt-2">
                @foreach ($categories as $category)
                    <label class="inline-flex items-center mr-4 mb-2">
                        <input type="checkbox" wire:model="selectedCategories" value="{{ $category->id }}"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm">
                        <span class="ms-2 text-sm text-gray-700">{{ $category->name }}</span>
                    </label>
                @endforeach
                
                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="checkbox" wire:model.live="hasOther"
                            class="rounded border-gray-300 text-indigo-600 shadow-sm">
                        <span class="ms-2 text-sm text-gray-700">Otro</span>
                    </label>

                    @if ($hasOther)
                        <x-text-input wire:model="other_service" type="text" maxlength="100"
                            class="block mt-2 w-full"
                            placeholder="Escribe el servicio que ofreces (ej: Instalación de cámaras)" />
                        <x-input-error :messages="$errors->get('other_service')" class="mt-2" />
                    @endif
                </div>
            </div>
            <x-input-error :messages="$errors->get('selectedCategories')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="address" value="Dirección" />
            <div class="flex gap-2 mt-1">
                <x-text-input wire:model="address" id="address" type="text" class="block w-full"
                    placeholder="Ej: Av. 9 de Octubre, Guayaquil" />
                <button type="button" id="search-btn"
                    class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm whitespace-nowrap">
                    Buscar
                </button>
            </div>
            <x-input-error :messages="$errors->get('address')" class="mt-2" />
            <p id="search-error" class="mt-2 text-sm text-red-600"></p>
        </div>

        <div>
            <x-input-label value="Tu ubicación (haz clic en el mapa o arrastra el marcador)" />
            <div wire:ignore class="mt-1">
                <div id="map" style="height: 400px;" class="rounded-md"></div>
            </div>
            <x-input-error :messages="$errors->get('latitude')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="radius" value="Radio de cobertura (km)" />
            <x-text-input wire:model.live="coverage_radius_km" id="radius" type="number" min="1"
                max="100" class="block mt-1 w-32" />
            <x-input-error :messages="$errors->get('coverage_radius_km')" class="mt-2" />
        </div>

        <x-primary-button>Guardar perfil</x-primary-button>
    </form>

    @script
        <script>
            const hasPoint = $wire.latitude !== null && $wire.longitude !== null;
            const startLat = hasPoint ? $wire.latitude : -2.1894; // Guayaquil por defecto
            const startLng = hasPoint ? $wire.longitude : -79.8891;

            const map = L.map(document.getElementById('map'))
                .setView([startLat, startLng], hasPoint ? 15 : 12);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors',
            }).addTo(map);

            let marker = null;
            let circle = null;

            const draw = (lat, lng) => {
                if (!marker) {
                    marker = L.marker([lat, lng], {
                        draggable: true
                    }).addTo(map);
                    circle = L.circle([lat, lng], {
                        radius: $wire.coverage_radius_km * 1000
                    }).addTo(map);
                    marker.on('dragend', () => {
                        const p = marker.getLatLng();
                        setPoint(p.lat, p.lng, false);
                    });
                } else {
                    marker.setLatLng([lat, lng]);
                    circle.setLatLng([lat, lng]);
                }
            };

            const setPoint = (lat, lng, recenter = true) => {
                $wire.latitude = lat;
                $wire.longitude = lng;
                draw(lat, lng);
                if (recenter) map.setView([lat, lng], 16);
            };

            if (hasPoint) draw(startLat, startLng);

            map.on('click', (e) => setPoint(e.latlng.lat, e.latlng.lng, false));

            $wire.$watch('coverage_radius_km', (value) => {
                if (circle && value) circle.setRadius(value * 1000);
            });

            document.getElementById('search-btn').addEventListener('click', async () => {
                const errorEl = document.getElementById('search-error');
                errorEl.textContent = '';

                const query = $wire.address;
                if (!query) return;

                try {
                    const url =
                        'https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=ec&q=' +
                        encodeURIComponent(query);
                    const res = await fetch(url);
                    const data = await res.json();

                    if (data.length === 0) {
                        errorEl.textContent =
                            'No se encontró esa dirección. Prueba con otra o marca el punto en el mapa.';
                        return;
                    }
                    setPoint(parseFloat(data[0].lat), parseFloat(data[0].lon));
                } catch (e) {
                    errorEl.textContent = 'No se pudo buscar la dirección. Marca el punto directamente en el mapa.';
                }
            });
        </script>
    @endscript
</div>
