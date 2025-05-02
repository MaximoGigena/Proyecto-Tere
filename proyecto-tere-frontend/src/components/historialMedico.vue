<template>
    <div class="p-6">
        <!-- Título -->
        <h1 class="text-2xl font-semibold text-center mb-6">Historial de Médico</h1>

        <!-- Ordenar -->
        <div class="mb-4 text-sm font-semibold text-gray-700 flex items-center gap-1">
            <span>Ordenar</span>
            <i class="fa-solid fa-arrow-up-down"></i>
        </div>

        <!-- Tarjeta médica -->
        <div class="flex items-center bg-red-500 rounded-full px-6 py-4 w-fit shadow-md text-white">
            <!-- Imagen redonda con borde grueso -->
            <div class="w-20 h-20 rounded-full overflow-hidden border-[10px] border-red-700 mr-4 flex-shrink-0">
            <img src="https://cdn.pixabay.com/photo/2021/08/02/11/03/hallelujah-6516410_960_720.jpg" alt="tratamiento" class="w-full h-full object-cover">
            </div>

            <!-- Texto -->
            <div class="text-sm space-y-1">
            <p><span class="font-semibold">Nombre del Tratamiento:</span> Tratamiento X</p>
            <p><span class="font-semibold">Fecha del Tratamiento:</span> xx/xx/xxxx</p>
            </div>
        </div>
    </div>

</template>