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

        <!-- Acciones Disponibles -->
        <div class="bg-white shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Acciones Disponibles</h3>
                <div class="flex flex-wrap gap-3">
                    @if ($jornada->estado === 'activa' && $jornada->puestasEnMarcha->where('estado', 'en_marcha')->count() === 0)
                        <a href="{{ route('jornadas.puestas-en-marcha.create', $jornada) }}"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Iniciar Puesta en Marcha
                        </a>
                    @endif

                    @if ($jornada->puestasEnMarcha->where('estado', 'en_marcha')->count() > 0)
                        @php
                            $paradasActivas = $jornada->puestasEnMarcha
                                ->where('estado', 'en_marcha')
                                ->flatMap->incidenciasParada->whereNull('ts_fin_parada');
                        @endphp

                        @if ($paradasActivas->count() > 0)
                            @php $paradaActiva = $paradasActivas->first(); @endphp
                            @if($paradaActiva && $paradaActiva->id)
                                <button type="button"
                                    onclick="openFinalizarParadaModal({{ $paradaActiva->id }})"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Finalizar Parada
                                </button>
                            @endif
                        @else
                            <button type="button"
                                onclick="openRegistroParadaModal({{ $jornada->puestasEnMarcha->where('estado', 'en_marcha')->first()->id }})"
                                class="inline-flex items-center px-4 py-2 bg-yellow-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 focus:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Registrar Parada
                            </button>
                        @endif

                        <button type="button"
                            onclick="openRegistroProduccionModal({{ $jornada->puestasEnMarcha->where('estado', 'en_marcha')->first()->id }})"
                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Registrar Producción
                        </button>
                    @endif
                </div>
            </div>
        </div>

        <!-- Puestas en Marcha -->
        <div class="bg-white shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-semibold mb-4">Puestas en Marcha</h3>
                <div class="space-y-4">
                    @forelse($jornada->puestasEnMarcha as $puesta)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">ID: {{ $puesta->id }} | Estado:
                                        {{ $puesta->estado }}</p>
                                    <p class="text-sm text-gray-600">
                                        {{ $puesta->ts_inicio->format('d/m/Y H:i') }} -
                                        {{ $puesta->ts_fin ? $puesta->ts_fin->format('d/m/Y H:i') : 'Activa' }}
                                    </p>
                                </div>
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if ($puesta->estado === 'en_marcha') bg-green-100 text-green-800
                                    @elseif($puesta->estado === 'finalizada') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ ucfirst(str_replace('_', ' ', $puesta->estado)) }}
                                </span>
                            </div>

                            <!-- Detalles de Producción -->
                            @if ($puesta->detallesProduccion->count() > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Registros de Producción</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Timestamp</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Producida</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Buena</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Fallada</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Tasa Defectos</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($puesta->detallesProduccion as $detalle)
                                                    <tr>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $detalle->ts->format('H:i:s') }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ number_format($detalle->cantidad_producida, 2) }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ number_format($detalle->cantidad_buena, 2) }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ number_format($detalle->cantidad_fallada, 2) }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ number_format($detalle->tasa_defectos, 2) }}%</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 mt-2">No hay registros de producción para esta puesta en
                                    marcha.</p>
                            @endif

                            <!-- Incidencias de Parada -->
                            @if ($puesta->incidenciasParada->count() > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-2">Incidencias de Parada</h4>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Inicio</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Fin</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Motivo</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Duración</th>
                                                    <th
                                                        class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                        Notas</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($puesta->incidenciasParada as $incidencia)
                                                    <tr>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $incidencia->ts_inicio_parada->format('H:i:s') }}</td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $incidencia->ts_fin_parada ? $incidencia->ts_fin_parada->format('H:i:s') : 'Activa' }}
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            {{ ucfirst(str_replace('_', ' ', $incidencia->motivo)) }}
                                                        </td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-sm text-gray-900">
                                                            @if ($incidencia->duracion_segundos)
                                                                {{ floor($incidencia->duracion_segundos / 3600) }}h
                                                                {{ floor(($incidencia->duracion_segundos % 3600) / 60) }}m
                                                                {{ $incidencia->duracion_segundos % 60 }}s
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td class="px-3 py-2 text-sm text-gray-900">
                                                            {{ $incidencia->notas ?: '-' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @else
                                <p class="text-sm text-gray-500 mt-2">No hay incidencias de parada registradas para esta
                                    puesta en marcha.</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No hay puestas en marcha registradas para esta jornada.</p>
                    @endforelse
                </div>
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

    <!-- Modal para Registrar Parada -->
    <div id="registroParadaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Parada</h3>
                <form id="registroParadaForm">
                    @csrf
                    <input type="hidden" id="puestaEnMarchaIdParada" name="puesta_en_marcha_id">
                    <div class="mb-4">
                        <label for="motivo" class="block text-sm font-medium text-gray-700">Motivo de la Parada</label>
                        <select id="motivo" name="motivo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                            <option value="">Seleccione un motivo</option>
                            <option value="falla_electrica">Falla Eléctrica</option>
                            <option value="falta_material">Falta de Material</option>
                            <option value="atasco">Atasco</option>
                            <option value="mantenimiento">Mantenimiento</option>
                            <option value="cambio_herramientas">Cambio de Herramientas</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="notas" class="block text-sm font-medium text-gray-700">Notas (opcional)</label>
                        <textarea id="notas" name="notas" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Describa la parada..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRegistroParadaModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-yellow-600 text-white rounded-md hover:bg-yellow-700">Registrar Parada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Finalizar Parada -->
    <div id="finalizarParadaModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Finalizar Parada</h3>
                <form id="finalizarParadaForm">
                    @csrf
                    <input type="hidden" id="incidenciaParadaId" name="incidencia_parada_id">
                    <div class="mb-4">
                        <label for="notas_finalizacion" class="block text-sm font-medium text-gray-700">Notas de
                            Finalización (opcional)</label>
                        <textarea id="notas_finalizacion" name="notas_finalizacion" rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Describa cómo se solucionó la parada..."></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeFinalizarParadaModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Finalizar
                            Parada</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para Registrar Producción -->
    <div id="registroProduccionModal"
        class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Registrar Datos de Producción</h3>
                <form id="registroProduccionForm">
                    @csrf
                    <input type="hidden" id="puestaEnMarchaIdProduccion" name="puesta_en_marcha_id">
                    <div class="mb-4">
                        <label for="ts" class="block text-sm font-medium text-gray-700">Timestamp</label>
                        <input type="datetime-local" id="ts" name="ts"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="cantidad_producida" class="block text-sm font-medium text-gray-700">Cantidad
                            Producida</label>
                        <input type="number" id="cantidad_producida" name="cantidad_producida" min="0"
                            step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            required>
                    </div>
                    <div class="mb-4">
                        <label for="cantidad_buena" class="block text-sm font-medium text-gray-700">Cantidad
                            Buena</label>
                        <input type="number" id="cantidad_buena" name="cantidad_buena" min="0"
                            step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="mb-4">
                        <label for="cantidad_fallada" class="block text-sm font-medium text-gray-700">Cantidad
                            Fallada</label>
                        <input type="number" id="cantidad_fallada" name="cantidad_fallada" min="0"
                            step="0.01"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeRegistroProduccionModal()"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">Cancelar</button>
                        <button type="submit"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Registrar
                            Producción</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openRegistroParadaModal(puestaEnMarchaId) {
            document.getElementById('puestaEnMarchaIdParada').value = puestaEnMarchaId;
            document.getElementById('registroParadaModal').classList.remove('hidden');
        }

        function openFinalizarParadaModal(incidenciaParadaId) {
            if (!incidenciaParadaId) {
                alert('Error: ID de parada no válido.');
                return;
            }
            document.getElementById('incidenciaParadaId').value = incidenciaParadaId;
            document.getElementById('finalizarParadaModal').classList.remove('hidden');
        }

        function closeFinalizarParadaModal() {
            document.getElementById('finalizarParadaModal').classList.add('hidden');
            document.getElementById('finalizarParadaForm').reset();
        }

        function closeRegistroParadaModal() {
            document.getElementById('registroParadaModal').classList.add('hidden');
            document.getElementById('registroParadaForm').reset();
        }

        function openRegistroProduccionModal(puestaEnMarchaId) {
            document.getElementById('puestaEnMarchaIdProduccion').value = puestaEnMarchaId;
            // Set current timestamp
            const now = new Date();
            const timestamp = now.toISOString().slice(0, 16); // Format for datetime-local
            document.getElementById('ts').value = timestamp;
            document.getElementById('registroProduccionModal').classList.remove('hidden');
        }

        function closeRegistroProduccionModal() {
            document.getElementById('registroProduccionModal').classList.add('hidden');
            document.getElementById('registroProduccionForm').reset();
        }

        // Manejar envío del formulario de parada
        document.getElementById('registroParadaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const puestaEnMarchaId = formData.get('puesta_en_marcha_id');

            fetch(`/api/puestas-en-marcha/${puestaEnMarchaId}/incidencias-parada`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Parada registrada exitosamente');
                        closeRegistroParadaModal();
                        location.reload();
                    } else if (data.errors) {
                        const errors = Object.values(data.errors).flat().join('\n');
                        alert('Errores de validación:\n' + errors);
                    } else {
                        alert('Error al registrar parada: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al registrar parada: ' + error.message);
                });
        });

        // Manejar envío del formulario de finalizar parada
        document.getElementById('finalizarParadaForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.textContent = 'Finalizando...';

            const formData = new FormData(this);
            const incidenciaParadaId = formData.get('incidencia_parada_id');

            fetch(`/incidencias-parada/${incidenciaParadaId}`, {
                    method: 'PATCH',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Parada finalizada exitosamente');
                        closeFinalizarParadaModal();
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Error desconocido'));
                        submitButton.disabled = false;
                        submitButton.textContent = 'Finalizar Parada';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al finalizar parada: ' + error.message);
                    submitButton.disabled = false;
                    submitButton.textContent = 'Finalizar Parada';
                });
        });

        // Manejar envío del formulario de producción
        document.getElementById('registroProduccionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const cantidadProducida = formData.get('cantidad_producida');
            const cantidadBuena = formData.get('cantidad_buena');

            // Validación básica
            if (cantidadBuena && cantidadProducida && parseFloat(cantidadBuena) > parseFloat(cantidadProducida)) {
                alert('La cantidad buena no puede ser mayor que la cantidad producida.');
                return;
            }

            const puestaEnMarchaId = formData.get('puesta_en_marcha_id');

            fetch(`/api/puestas-en-marcha/${puestaEnMarchaId}/produccion-detalle`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert('Producción registrada exitosamente');
                        closeRegistroProduccionModal();
                        location.reload();
                    } else if (data.errors) {
                        const errors = Object.values(data.errors).flat().join('\n');
                        alert('Errores de validación:\n' + errors);
                    } else {
                        alert('Error al registrar producción: ' + (data.message || 'Error desconocido'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al registrar producción: ' + error.message);
                });
        });
    </script>
</x-app-layout>
