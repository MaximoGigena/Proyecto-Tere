<template>
  <div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-sm">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-2xl font-light text-gray-800">PERFIL PROFESIONAL</h1>
      <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
        </svg>
      </div>
    </div>

    <div class="flex flex-col md:flex-row gap-8">
      <!-- Columna izquierda - Foto de perfil -->
      <div class="w-full md:w-1/3 flex flex-col items-center">
        <div class="relative mb-4">
          <div class="w-32 h-32 rounded-full bg-gray-100 border-2 border-gray-200 overflow-hidden">
            <img v-if="perfil.foto" :src="perfil.foto" alt="Foto del veterinario" class="w-full h-full object-cover">
            <div v-else class="w-full h-full flex items-center justify-center bg-gray-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd" />
              </svg>
            </div>
          </div>
          <button @click="cambiarFoto" class="absolute bottom-0 right-0 bg-white p-2 rounded-full shadow-sm border border-gray-200 hover:bg-gray-50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
              <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
            </svg>
          </button>
        </div>
        <button @click="cambiarFoto" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Cambiar foto</button>
        
        <div class="mt-6 w-full">
          <h3 class="text-sm font-medium text-gray-500 mb-2">INFORMACIÓN ADICIONAL</h3>
          <div class="space-y-3">
            <div>
              <label class="block text-xs text-gray-500">Años de experiencia</label>
              <input v-model="perfil.experiencia" type="text" class="w-full border-b border-gray-200 py-1 text-sm focus:border-blue-500 focus:outline-none">
            </div>
            <div>
              <label class="block text-xs text-gray-500">Horario de atención</label>
              <input v-model="perfil.horario" type="text" class="w-full border-b border-gray-200 py-1 text-sm focus:border-blue-500 focus:outline-none">
            </div>
          </div>
        </div>
      </div>

      <!-- Columna derecha - Datos del perfil -->
      <div class="w-full md:w-2/3">
        <form @submit.prevent="guardarCambios" class="space-y-6">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Nombre completo</label>
              <input v-model="perfil.nombre" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Número de matrícula</label>
              <input v-model="perfil.matricula" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Especialidad</label>
              <input v-model="perfil.especialidad" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
              <input v-model="perfil.telefono" type="text" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
              <input v-model="perfil.email" type="email" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
              <label class="block text-sm font-medium text-gray-700 mb-1">Dirección del consultorio</label>
              <textarea v-model="perfil.direccion" rows="2" class="w-full border-b border-gray-200 py-2 focus:border-blue-500 focus:outline-none"></textarea>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button type="button" @click="cancelarCambios" class="px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md">
              Cancelar
            </button>
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md shadow-sm">
              Guardar cambios
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const perfil = reactive({
  foto: null,
  nombre: 'Dr. Juan Pérez',
  matricula: 'MP-123456',
  especialidad: 'Medicina Felina y Canina',
  telefono: '+54 9 11 1234-5678',
  email: 'juanperez@veterinaria.com',
  direccion: 'Av. Siempre Viva 742, Springfield',
  experiencia: '12 años',
  horario: 'Lunes a Viernes, 9:00 - 18:00'
})

function cambiarFoto() {
  // Lógica para cambiar la foto
  console.log('Cambiar foto')
}

function guardarCambios() {
  console.log('Datos guardados:', perfil)
  alert('Cambios guardados correctamente.')
}

function cancelarCambios() {
  window.location.reload()
}
</script>

<style scoped>
/* Estilos adicionales si son necesarios */
</style>