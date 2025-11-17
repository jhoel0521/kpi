<!--
  Este es el componente reutilizable.
  Detecta automáticamente si debe mostrar datos existentes (value/selected)
  o si debe mostrar un formulario vacío.
-->
<form action="{{ $formAction }}" method="POST" class="space-y-4">
    @csrf

    <!-- El método del formulario se define dinámicamente -->
    @if ($jornada->exists)
        @method('PATCH')
    @endif

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

    <!-- Campo: Máquina (Dropdown) -->
    <div>
        <label for="maquina_id" class="block text-sm font-medium text-gray-700">Máquina</label>
        <select name="maquina_id" id="maquina_id" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">Seleccione una máquina</option>
            @foreach ($maquinas as $maquina)
                <option value="{{ $maquina->id }}" @selected(old('maquina_id', $jornada->maquina_id) == $maquina->id)>
                    {{ $maquina->nombre }} (Estado: {{ $maquina->estado }})
                </option>
            @endforeach
        </select>
    </div>

    <!-- Campo: Operador que Inicia (Dropdown) -->
    <div>
        <label for="operador_id_inicio" class="block text-sm font-medium text-gray-700">Operador que Inicia</label>
        <select name="operador_id_inicio" id="operador_id_inicio" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="">Seleccione un operador</option>
            @foreach ($operadores as $operador)
                <option value="{{ $operador->id }}" @selected(old('operador_id_inicio', $jornada->operador_id_inicio) == $operador->id)>
                    {{ $operador->name }}
                </option>
            @endforeach
        </select>
    </div>

    <!-- Campo: Nombre Jornada (Turno) -->
    <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre Jornada (Ej: Turno Día)</label>
        <input type="text" name="nombre" id="nombre" value="{{ old('nombre', $jornada->nombre) }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>

    <!-- Campo: Timestamp Inicio -->
    <div>
        <label for="ts_inicio" class="block text-sm font-medium text-gray-700">Inicio de Jornada</label>
        <input type="datetime-local" name="ts_inicio" id="ts_inicio"
            value="{{ old('ts_inicio', $jornada->ts_inicio ? $jornada->ts_inicio->format('Y-m-d\TH:i') : '') }}"
            required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>

    <!-- Campo: Cantidad Esperada -->
    <div>
        <label for="cantidad_producida_esperada" class="block text-sm font-medium text-gray-700">Meta de Producción
            (Opcional)</label>
        <input type="number" name="cantidad_producida_esperada" id="cantidad_producida_esperada"
            value="{{ old('cantidad_producida_esperada', $jornada->cantidad_producida_esperada) }}" min="0"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
    </div>

    <!-- Botón de Envío Dinámico -->
    <div class="flex justify-end pt-4">
        <button type="submit"
            class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            {{ $submitButtonText }}
        </button>
    </div>
</form>
