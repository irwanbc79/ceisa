@props([
    'id' => null,
    'name' => null,
    'model' => null,
    'options' => [],
    'placeholder' => '-- Pilih --',
    'required' => false,
])

@php
    $isJsExpr = is_string($options) && !str_starts_with($options, '[');
    $jsOptions = $isJsExpr ? $options : json_encode($options);
@endphp

<div x-data="{
    open: false,
    search: '',
    optionsList: [],
    selectedVal: null,
    selectedLabel: '',
    init() {
        // Load options
        @if($isJsExpr)
            this.optionsList = {{ $jsOptions }} || [];
            this.$watch('{{ $jsOptions }}', val => {
                this.optionsList = val || [];
                this.updateLabel();
            });
        @else
            this.optionsList = {!! $jsOptions !!} || [];
        @endif

        // Watch external model changes
        this.$watch('{{ $model }}', val => {
            this.selectedVal = val;
            this.updateLabel();
        });
        
        this.selectedVal = this.{{ $model }};
        this.updateLabel();
    },
    updateLabel() {
        const option = this.optionsList.find(opt => opt.code === this.selectedVal || opt.value === this.selectedVal || opt.id === this.selectedVal);
        if (option) {
            const lbl = option.label || option.name || option.uraian || option.value || '';
            const cd = option.code || '';
            this.selectedLabel = cd && cd !== lbl ? cd + ' - ' + lbl : lbl || cd;
        } else {
            this.selectedLabel = this.selectedVal || '{{ $placeholder }}';
        }
    },
    get filteredOptions() {
        if (!this.search) return this.optionsList;
        return this.optionsList.filter(opt => {
            const label = (opt.label || opt.name || opt.uraian || opt.value || '').toLowerCase();
            const code = (opt.code || opt.id || '').toLowerCase();
            const searchLower = this.search.toLowerCase();
            return label.includes(searchLower) || code.includes(searchLower);
        });
    },
    select(val) {
        this.selectedVal = val;
        this.{{ $model }} = val;
        this.updateLabel();
        this.open = false;
        this.search = '';
    }
}" class="relative mt-1">
    <!-- Trigger Button -->
    <button type="button" @click="open = !open" 
            class="w-full flex items-center justify-between bg-white/90 border border-slate-200/80 rounded-xl px-4 py-2 text-sm shadow-sm hover:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 text-left transition-all duration-200"
            :class="open ? 'ring-2 ring-indigo-500/20 border-indigo-400' : ''">
        <span x-text="selectedLabel" class="truncate font-medium text-slate-700"></span>
        <svg class="h-4 w-4 text-slate-400 transition-transform duration-200" :class="open ? 'rotate-180 text-indigo-500' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
    </button>

    <!-- Hidden Input for Form Submission -->
    <input type="hidden" id="{{ $id }}" {{ $attributes->merge(['name' => $name]) }} x-model="selectedVal" @if($required) required @endif>

    <!-- Dropdown Panel -->
    <div x-show="open" @click.outside="open = false" x-cloak
         class="absolute z-50 mt-1 w-full bg-white/95 backdrop-blur-md border border-slate-200/80 rounded-xl shadow-xl py-2 px-2 max-h-72 overflow-hidden flex flex-col transition-all duration-200"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <!-- Search Input -->
        <div class="relative mb-2 shrink-0">
            <input type="text" x-model="search" placeholder="Cari..."
                   class="w-full pl-8 pr-4 py-1.5 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500/10 placeholder-slate-400 bg-white">
            <svg class="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>

        <!-- Options List -->
        <div class="overflow-y-auto flex-1 max-h-48 space-y-0.5 scrollbar-thin">
            <template x-for="opt in filteredOptions" :key="opt.code || opt.value || opt.id">
                <button type="button" @click="select(opt.code || opt.value || opt.id)"
                        class="w-full text-left px-3 py-2 text-xs rounded-lg hover:bg-indigo-50 hover:text-indigo-900 transition-colors flex items-center justify-between"
                        :class="(opt.code || opt.value || opt.id) === selectedVal ? 'bg-indigo-50/80 text-indigo-700 font-bold' : 'text-slate-600'">
                    <span x-text="opt.code && opt.code !== (opt.label || opt.name || opt.uraian || opt.value) ? opt.code + ' - ' + (opt.label || opt.name || opt.uraian || opt.value) : (opt.label || opt.name || opt.uraian || opt.value || opt.code)"></span>
                    <span x-show="(opt.code || opt.value || opt.id) === selectedVal" class="text-indigo-600">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                        </svg>
                    </span>
                </button>
            </template>
            <div x-show="filteredOptions.length === 0" class="text-center py-4 text-xs text-slate-400 italic">
                Data tidak ditemukan
            </div>
        </div>
    </div>
</div>
