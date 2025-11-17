<!--
  Vista 'show' actualizada para usar <x-app-layout>
-->
<x-app-layout title="Ver Jornada: {{ $jornada->nombre }}">

    <div class="max-w-4xl mx-auto p-4 sm:p-6 lg:p-8">

        <!-- Header y Botones de Acción -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    Jornada: {{ $jornada->nombre }}
                </h1>
                <p class="text-sm text-gray-600">
                    Máquina: {{ $jornada->maquina->nombre }} |
                    Estado: <span
                        class="font-semibold @if ($jornada->estado == 'activa') text-green-600 @else text-gray-600 @endif">{{ ucfirst($jornada->estado) }}</span>
                </p>
            </div>
            <div>
                <a href="{{ route('jornadas.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 mr-2">
                    Volver
                </a>
                <a href="{{ route('jornadas.edit', $jornada) }}"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    Editar
                </a>
            </div>
        </div>

        <!-- Detalles Principales -->
        <div class="bg-white shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Detalles de la Jornada</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Inicio</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $jornada->ts_inicio->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Fin</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $jornada->ts_fin ? $jornada->ts_fin->format('d/m/Y H:i:s') : 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Operador (Inicio)</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $jornada->operadorInicio->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Operador (Actual)</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $jornada->operadorActual->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Meta de Producción</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            {{ $jornada->cantidad_producida_esperada ?? 'No definida' }}</dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Puestas en Marcha -->
        <div class="bg-white shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Puestas en Marcha</h3>
                <ul class="divide-y divide-gray-200">
                    @forelse($jornada->puestasEnMarcha as $puesta)
                        <li class="py-3">
                            <p class="text-sm font-medium text-gray-900">ID: {{ $puesta->id }} | Estado:
                                {{ $puesta->estado }}</p>
                            <p class="text-sm text-gray-600">
                                {{ $puesta->ts_inicio->format('H:i') }} -
                                {{ $puesta->ts_fin ? $puesta->ts_fin->format('H:i') : 'Activa' }}
                            </p>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-500">No hay puestas en marcha registradas para esta jornada.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>

        <!-- Historial de Operadores -->
        <div class="bg-white shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Historial de Operadores</h3>
                <ul class="divide-y divide-gray-200">
                    @forelse($jornada->cambiosOperador as $cambio)
                        <li class="py-3">
                            <p class="text-sm font-medium text-gray-900">
                                {{ $cambio->operadorNuevo->name ?? 'N/A' }}
                                <span class="text-gray-600">reemplazó a</span>
                                {{ $cambio->operadorAnterior->name ?? 'N/A' }}
                            </p>
                            <p class="text-sm text-gray-600">
                                {{ $cambio->ts_cambio->format('d/m/Y H:i') }} (Motivo:
                                {{ $cambio->razon ?? 'Sin motivo' }})
                            </p>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-500">No hay cambios de operador registrados.</li>
                    @endforelse
                </ul>
            </div>
        </div>

    </div>
</x-app-layout>
