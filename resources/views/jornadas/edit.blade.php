<x-app-layout title="Editar Jornada: {{ $jornada->nombre }}">

    <div class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">
        <h1 class="text-2xl font-bold mb-4">Editar Jornada: {{ $jornada->nombre }}</h1>

        <div class="p-6 bg-white border rounded-lg shadow-sm">
            <x-jornada-form :jornada="$jornada" :maquinas="$maquinas" :operadores="$operadores" />
        </div>
    </div>

</x-app-layout>
