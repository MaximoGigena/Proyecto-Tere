<template>
  <div class="min-h-screen bg-gray-100 p-4">
    <div class="max-w-xl mx-auto bg-white shadow-xl rounded-2xl p-6 space-y-6">
      <h2 class="text-2xl font-bold text-gray-800 text-center">Editar Datos Registrados</h2>

      <!-- Nombre -->
      <div>
        <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
        <input v-model="form.nombre" id="nombre" type="text"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
        <input v-model="form.email" id="email" type="email"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Contraseña actual -->
      <div>
        <label for="actual" class="block text-sm font-medium text-gray-700">Contraseña actual</label>
        <input v-model="form.actual" id="actual" type="password"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Nueva contraseña -->
      <div>
        <label for="nueva" class="block text-sm font-medium text-gray-700">Nueva contraseña</label>
        <input v-model="form.nueva" id="nueva" type="password"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Confirmar nueva contraseña -->
      <div>
        <label for="confirmar" class="block text-sm font-medium text-gray-700">Confirmar nueva contraseña</label>
        <input v-model="form.confirmar" id="confirmar" type="password"
          class="mt-1 w-full rounded-xl border-gray-300 shadow-sm focus:ring-teal-500 focus:border-teal-500" />
      </div>

      <!-- Botón -->
      <button @click="guardarCambios"
        class="w-full py-2 px-4 bg-teal-600 text-white font-semibold rounded-xl shadow hover:bg-teal-700 transition">
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
  actual: '',
  nueva: '',
  confirmar: ''
})

function guardarCambios() {
  if (form.nueva !== form.confirmar) {
    alert('La nueva contraseña no coincide con la confirmación.')
    return
  }

  console.log('Datos enviados al backend:', form)
  // Aquí enviarías los datos con axios o fetch
}
</script>
