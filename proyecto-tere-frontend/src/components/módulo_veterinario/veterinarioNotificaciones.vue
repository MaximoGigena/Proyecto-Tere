<template>
  <div class="max-w-2xl mx-auto p-8">
    <h1 class="text-2xl font-bold mb-6">Gestión de Notificaciones</h1>

    <form @submit.prevent="guardarPreferencias" class="space-y-4">
      <div v-for="(notificacion, index) in notificaciones" :key="index" class="flex justify-between items-center border p-4 rounded-lg bg-gray-50">
        <span>{{ notificacion.descripcion }}</span>
        <input type="checkbox" v-model="notificacion.activa" class="w-5 h-5" />
      </div>

      <div class="flex justify-end gap-4 pt-4">
        <button type="button" @click="cancelarCambios" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancelar</button>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Guardar Preferencias</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const notificaciones = reactive([
  { descripcion: 'Notificar nuevas citas agendadas', activa: true },
  { descripcion: 'Notificar cancelaciones de citas', activa: true },
  { descripcion: 'Recordatorios de citas próximas', activa: true },
  { descripcion: 'Alertas sobre vacunas pendientes de sus pacientes', activa: false },
  { descripcion: 'Informes nuevos cargados por colegas', activa: false },
  { descripcion: 'Nuevos mensajes o consultas de clientes', activa: true },
  { descripcion: 'Avisos de actualizaciones del sistema', activa: true }
])

function guardarPreferencias() {
  console.log('Preferencias guardadas:', notificaciones)
  alert('Preferencias de notificaciones actualizadas.')
}

function cancelarCambios() {
  window.location.reload()
}
</script>

<style scoped>
h1 {
  color: #333;
}
</style>

