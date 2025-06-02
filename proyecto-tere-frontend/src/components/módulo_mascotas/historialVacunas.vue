<template>
    <div class="p-6">
        <!-- Título -->
        <h1 class="text-2xl font-semibold text-center mb-6">Historial de Vacunación</h1>

        <!-- Ordenar -->
        <div class="mb-4 text-sm font-semibold text-gray-700 flex items-center gap-1">
            <span>Ordenar</span>
            <i class="fa-solid fa-arrow-up-down"></i>
        </div>

        <!-- Tarjeta de vacuna -->
        <div class="flex items-center bg-green-100 rounded-full px-6 py-4 w-fit shadow-md">
            <!-- Imagen redonda -->
            <div class="w-20 h-20 rounded-full overflow-hidden border-4 border-green-300 mr-4 flex-shrink-0">
            <img src="https://cdn.pixabay.com/photo/2022/11/08/07/53/desk-7577945_1280.jpg" alt="vacuna" class="w-full h-full object-cover">
            </div>

            <!-- Texto -->
            <div class="text-sm text-black space-y-1">
            <p><span class="font-semibold">Nombre de la vacuna:</span> Vacuna X</p>
            <p><span class="font-semibold">Fecha de la vacuna:</span> xx/xx/xxxx</p>
            <p><span class="font-semibold">Fecha de renovación:</span> xx/xx/xxxx</p>
            </div>
        </div>
    </div>

</template>

<script setup>
</script>
