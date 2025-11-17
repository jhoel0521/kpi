<!--
  Componente reutilizable para PuestaEnMarcha
  Maneja 'create' (iniciar) y 'edit' (finalizar)
-->
<form action="{{ $formAction }}" method="POST" class="space-y-4">
    @csrf
    @method($formMethod)

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

    <!-- Campos de solo lectura -->
    <div>
        <label class="block text-sm font-medium text-gray-700">Jornada</label>
        <input type="text" value="{{ $jornada->nombre }}" disabled
            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Máquina</label>
        <input type="text" value="{{ $jornada->maquina->nombre }}" disabled
            class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
    </div>


    <!-- Si estamos CREANDO (Iniciando) -->
    @if (!$puestaEnMarcha->exists)
        <div>
            <label for="ts_inicio" class="block text-sm font-medium text-gray-700">Inicio de Puesta en Marcha</label>
            <input type="datetime-local" name="ts_inicio" id="ts_inicio"
                value="{{ old('ts_inicio', now()->format('Y-m-d\TH:i')) }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
            <label for="cantidad_producida_esperada" class="block text-sm font-medium text-gray-700">Meta de Producción
                (Opcional)</label>
            <input type="number" name="cantidad_producida_esperada" id="cantidad_producida_esperada"
                value="{{ old('cantidad_producida_esperada', $puestaEnMarcha->cantidad_producida_esperada) }}"
                min="0"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
    @endif

    <!-- Si estamos EDITANDO (Finalizando) -->
    @if ($puestaEnMarcha->exists)
        <div>
            <label class="block text-sm font-medium text-gray-700">Inicio (Registrado)</label>
            <input type="datetime-local" value="{{ $puestaEnMarcha->ts_inicio->format('Y-m-d\TH:i') }}" disabled
                class="mt-1 block w-full rounded-md border-gray-300 bg-gray-100 shadow-sm sm:text-sm">
        </div>
        <div>
            <label for="ts_fin" class="block text-sm font-medium text-gray-700">Fin de Puesta en Marcha</label>
            <input type="datetime-local" name="ts_fin" id="ts_fin"
                value="{{ old('ts_fin', now()->format('Y-m-d\TH:i')) }}" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
    @endif


    <!-- Botón de Envío Dinámico -->
    <div class="flex justify-end pt-4 space-x-3">
        <a href="{{ route('jornadas.show', $jornada) }}"
            class="inline-flex justify-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
            Cancelar
        </a>
        <button type="submit"
            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700">
            {{ $submitButtonText }}
        </button>
    </div>
</form>
