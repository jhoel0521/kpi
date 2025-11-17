<x-app-layout title="Cambiar Operador de Jornada">

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-bold mb-4">Registrar Cambio de Operador</h1>
        <p class="text-gray-600 mb-4">
            Cambiando operador para la jornada <strong>{{ $jornada->nombre }}</strong> (Máquina:
            {{ $jornada->maquina->nombre }})
        </p>

        <div class="p-6 bg-white border rounded-lg shadow-sm">
            <form action="{{ route('jornadas.cambios-operador.store', $jornada) }}" method="POST" class="space-y-4">
                @csrf

                <!-- Mostrar errores de validación -->
                @if ($errors->any())
                    <div class="p-4 bg-red-100 text-red-700 border border-red-300 rounded">
                        <strong>¡Error!</strong>
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Operador Actual (Solo lectura) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Operador Actual</label>
                    <input type="text" value="{{ $jornada->operadorActual->name ?? 'N/A' }}" disabled
                        class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
                </div>

                <!-- Campo: Operador Nuevo (Dropdown) -->
                <div>
                    <label for="operador_nuevo_id" class="block text-sm font-medium text-gray-700">Nuevo
                        Operador</label>
                    <select name="operador_nuevo_id" id="operador_nuevo_id" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">Seleccione un nuevo operador</option>
                        @foreach ($operadores as $operador)
                            <option value="{{ $operador->id }}" @selected(old('operador_nuevo_id') == $operador->id)>
                                {{ $operador->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Campo: Razón del Cambio -->
                <div>
                    <label for="razon" class="block text-sm font-medium text-gray-700">Razón (Ej: Cambio de turno,
                        relevo)</label>
                    <input type="text" name="razon" id="razon" value="{{ old('razon') }}"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>

                <!-- Botón de Envío -->
                <div class="flex justify-end pt-4 space-x-3">
                    <a href="{{ route('jornadas.show', $jornada) }}"
                        class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                        Cancelar
                    </a>
                    <button type="submit"
                        class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
                        Confirmar Cambio
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
