<template>
  <div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-xl mx-auto bg-white shadow-xl rounded-2xl p-6 space-y-6">
      <h2 class="text-2xl font-bold text-gray-800 text-center">Configuración de Usuario</h2>

      <!-- Nombre de usuario -->
      <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre de usuario</label>
        <input v-model="form.nombre" id="nombre" type="text" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input v-model="form.email" id="email" type="email" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Cambiar contraseña -->
      <div>
        <label class="block text-sm font-medium text-gray-700">Contraseña</label>
        <input v-model="form.password" type="password" placeholder="Nueva contraseña" class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Preferencias -->
      <div class="space-y-2">
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">Notificaciones</span>
          <input type="checkbox" v-model="form.notificaciones" class="form-checkbox text-teal-600 rounded focus:ring-0" />
        </div>
        <div class="flex items-center justify-between">
          <span class="text-sm text-gray-700">Modo oscuro</span>
          <input type="checkbox" v-model="form.modoOscuro" class="form-checkbox text-teal-600 rounded focus:ring-0" />
        </div>
      </div>

      <!-- Botón guardar -->
      <button @click="guardarCambios" class="w-full py-2 px-4 bg-teal-600 text-white font-semibold rounded-xl shadow hover:bg-teal-700 transition">
        Guardar cambios
      </button>
    </div>
  </div>
</template>

<script setup>
import { reactive } from 'vue'

const form = reactive({
  nombre: 'Maxi',
  email: 'maxi@example.com',
  password: '',
  notificaciones: true,
  modoOscuro: false
})

function guardarCambios() {
  console.log('Datos guardados:', form)
  // Aquí podrías enviar la data al backend con axios o fetch
}
</script>
