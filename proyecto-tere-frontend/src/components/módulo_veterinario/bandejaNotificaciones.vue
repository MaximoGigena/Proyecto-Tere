<!-- views/VeterinarioNotificaciones.vue -->
<template>
  <div class="min-h-screen bg-gray-100 p-4">
    <h1 class="text-2xl font-bold mb-4">Notificaciones</h1>

    <div v-if="notificaciones.length === 0" class="text-gray-500">
      No tienes notificaciones por el momento.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="(notif, index) in notificaciones"
        :key="index"
        class="flex items-start gap-3 p-4 rounded-xl shadow-md transition
               hover:bg-gray-50"
        :class="notif.leida ? 'bg-white' : 'bg-blue-50 border border-blue-200'"
      >
        <!-- Icono dinámico -->
        <div class="flex-shrink-0 text-2xl">
          <span v-if="notif.tipo === 'mensaje'">📩</span>
          <span v-else-if="notif.tipo === 'mascota'">🐶</span>
          <span v-else-if="notif.tipo === 'vacuna'">💉</span>
          <span v-else>⚠️</span>
        </div>

        <!-- Contenido -->
        <div class="flex-1">
          <div class="flex justify-between items-center">
            <h2 class="font-semibold text-gray-800">{{ notif.titulo }}</h2>
            <span class="text-xs text-gray-500">{{ notif.fecha }}</span>
          </div>
          <p class="text-gray-600 text-sm">{{ notif.descripcion }}</p>
        </div>

        <!-- Acciones -->
        <div class="flex flex-col gap-2">
          <button
            v-if="!notif.leida"
            @click="marcarLeida(index)"
            class="text-xs bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600"
          >
            Marcar leída
          </button>
          <button
            @click="verDetalle(notif)"
            class="text-xs bg-gray-200 px-2 py-1 rounded hover:bg-gray-300"
          >
            Ver
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";

const notificaciones = ref([
  {
    tipo: "vacuna",
    titulo: "Vacuna próxima",
    descripcion: "La mascota 'Luna' necesita refuerzo de vacuna antirrábica.",
    fecha: "18/09/2025 10:30",
    leida: false,
  },
  {
    tipo: "mascota",
    titulo: "Nueva mascota registrada",
    descripcion: "Un usuario registró a 'Rocky' en tu sistema.",
    fecha: "17/09/2025 14:20",
    leida: true,
  },
  {
    tipo: "mensaje",
    titulo: "Nuevo mensaje",
    descripcion: "Has recibido un mensaje de Juan Pérez.",
    fecha: "16/09/2025 19:45",
    leida: false,
  },
]);

const marcarLeida = (index) => {
  notificaciones.value[index].leida = true;
};

const verDetalle = (notif) => {
  alert(`Detalle:\n${notif.titulo}\n${notif.descripcion}`);
};
</script>
