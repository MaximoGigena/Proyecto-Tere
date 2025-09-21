<!-- gestionarTipos.vue -->
<template>
  <div class="p-6">
    <h1 class="text-2xl font-bold mb-6">Gestión de Procedimientos Médicos</h1>

    <!-- Carrusel de categorías -->
    <div class="flex overflow-x-auto space-x-6 pb-4 mb-6 border-b border-gray-300 scrollbar-thin">
      <router-link
        v-for="cat in categorias"
        :key="cat.nombre"
        :to="{ name: cat.ruta }"
        class="flex flex-col items-center min-w-[80px] px-2 py-2 rounded-lg transition-colors duration-200"
        :class="$route.name === cat.ruta 
          ? 'bg-blue-600 text-white' 
          : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
      >
        <component :is="cat.icono" class="w-7 h-7 mb-1" />
        <span class="text-xs text-center whitespace-nowrap">{{ cat.nombre }}</span>
      </router-link>
    </div>

    
    <!-- Contenido de la ruta activa -->
    <router-view v-slot="{ Component }">
      <div class="mb-4">
        <component :is="Component" />
      </div>
    </router-view>

  </div>
</template>

<script setup>
import { Syringe, Pill, Activity, Stethoscope, Shield, Scissors, FlaskConical, HeartPulse, Bandage } from "lucide-vue-next"

// Definimos categorías con icono y ruta
const categorias = [
  { nombre: "Vacunas", ruta: "tipoVacunas", icono: Syringe },
  { nombre: "Desparasitaciones", ruta: "tipoDesparasitaciones", icono: Shield },
  { nombre: "Revisiones", ruta: "tipoRevisiones", icono: Stethoscope },
  { nombre: "Alergias", ruta: "tipoAlergias", icono: Activity },
  { nombre: "Cirugías", ruta: "tipoCirugias", icono: Scissors },
  { nombre: "Terapias", ruta: "tipoTerapias", icono: HeartPulse },
  { nombre: "Fármacos", ruta: "tipoFarmacos", icono: Pill },
  { nombre: "Diagnósticos", ruta: "tipoDiagnosticos", icono: FlaskConical },
  { nombre: "Paliativos", ruta: "tipoPaliativos", icono: Bandage },
]
</script>

<style scoped>
/* Scrollbar casi invisible */
.scrollbar-thin::-webkit-scrollbar {
  height: 4px;
}
.scrollbar-thin::-webkit-scrollbar-thumb {
  background: rgba(0,0,0,0.2);
  border-radius: 10px;
}
</style>

