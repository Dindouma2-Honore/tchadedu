<x-filament-panels::page>
    <x-filament::section>
        <div class="flex flex-wrap items-end gap-4">
            <div class="min-w-[16rem]">
                <label for="examSessionId" class="fi-fo-field-wrp-label mb-1 block text-sm font-medium text-gray-950 dark:text-white">
                    Session d'examen
                </label>
                <select
                    id="examSessionId"
                    wire:model.live="examSessionId"
                    class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                >
                    <option value="">Toutes les sessions</option>
                    @foreach ($this->getExamSessionOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </x-filament::section>

    @php
        $counts = $this->getStatusCounts();
        $averageDays = $this->getAverageProcessingDays();
    @endphp

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-6">
        <x-filament::card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Total</p>
            <p class="text-2xl font-semibold">{{ $counts['total'] }}</p>
        </x-filament::card>
        <x-filament::card>
            <p class="text-sm text-gray-500 dark:text-gray-400">En cours</p>
            <p class="text-2xl font-semibold text-blue-500">{{ $counts['en_cours'] }}</p>
        </x-filament::card>
        <x-filament::card>
            <p class="text-sm text-gray-500 dark:text-gray-400">À corriger</p>
            <p class="text-2xl font-semibold text-amber-500">{{ $counts['a_corriger'] }}</p>
        </x-filament::card>
        <x-filament::card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Inscrits</p>
            <p class="text-2xl font-semibold text-green-600">{{ $counts['inscrit'] }}</p>
        </x-filament::card>
        <x-filament::card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Rejetés</p>
            <p class="text-2xl font-semibold text-red-500">{{ $counts['rejetee'] }}</p>
        </x-filament::card>
        <x-filament::card>
            <p class="text-sm text-gray-500 dark:text-gray-400">Délai moyen</p>
            <p class="text-2xl font-semibold">{{ $averageDays !== null ? $averageDays.' j' : '—' }}</p>
        </x-filament::card>
    </div>

    <x-filament::section :heading="'Détail par '.mb_strtolower($this->getBreakdownLabel())">
        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full text-start">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-white/10">
                        <th class="px-3 py-2 text-start text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ $this->getBreakdownLabel() }}</th>
                        <th class="px-3 py-2 text-end text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Total</th>
                        <th class="px-3 py-2 text-end text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Inscrits</th>
                        <th class="px-3 py-2 text-end text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">À corriger</th>
                        <th class="px-3 py-2 text-end text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Rejetés</th>
                        <th class="px-3 py-2 text-end text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Taux validation</th>
                        <th class="px-3 py-2 text-end text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Délai moyen</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->getBreakdownRows() as $row)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="px-3 py-2 font-medium">{{ $row['label'] }}</td>
                            <td class="px-3 py-2 text-end">{{ $row['total'] }}</td>
                            <td class="px-3 py-2 text-end text-green-600">{{ $row['inscrits'] }}</td>
                            <td class="px-3 py-2 text-end text-amber-500">{{ $row['a_corriger'] }}</td>
                            <td class="px-3 py-2 text-end text-red-500">{{ $row['rejetees'] }}</td>
                            <td class="px-3 py-2 text-end">{{ $row['taux_validation'] }}%</td>
                            <td class="px-3 py-2 text-end">{{ $row['delai_moyen_jours'] !== null ? $row['delai_moyen_jours'].' j' : '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                Aucune candidature dans votre périmètre pour ce filtre.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
