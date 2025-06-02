<template>
  <div class="min-h-screen bg-gradient-to-b from-teal-50 to-white p-6 flex flex-col items-center">
    <div class="w-full max-w-xl bg-white shadow-xl rounded-2xl p-6 space-y-6">
      <h1 class="text-3xl font-bold text-teal-700 text-center">Ayudanos con tu donación</h1>

      <p class="text-gray-600 text-center">
        Tu aporte ayuda a que más mascotas encuentren un hogar responsable.
      </p>

      <!-- Montos sugeridos -->
      <div class="flex justify-center gap-4 flex-wrap">
        <button
          v-for="monto in montosSugeridos"
          :key="monto"
          @click="montoSeleccionado = monto"
          :class="[
            'px-4 py-2 rounded-full border transition-all duration-200',
            montoSeleccionado === monto
              ? 'bg-teal-600 text-white border-teal-600'
              : 'bg-white text-teal-600 border-teal-300 hover:bg-teal-50'
          ]"
        >
          ${{ monto }}
        </button>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="donar">
        <div class="space-y-4">
          <input
            v-model="nombre"
            type="text"
            placeholder="Tu nombre"
            class="w-full border border-teal-200 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400"
            required
          />

          <input
            v-model="email"
            type="email"
            placeholder="Tu email"
            class="w-full border border-teal-200 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400"
            required
          />

          <input
            v-model.number="montoSeleccionado"
            type="number"
            min="1"
            placeholder="Monto personalizado"
            class="w-full border border-teal-200 p-3 rounded-xl focus:outline-none focus:ring-2 focus:ring-teal-400"
          />

          <button
            type="submit"
            class="w-full bg-teal-600 text-white py-3 rounded-xl hover:bg-teal-700 transition"
          >
            Donar ahora
          </button>
        </div>
      </form>

      <p class="text-xs text-gray-400 text-center">
        Todas las donaciones son utilizadas para mejorar el bienestar y cuidado de los animales.
      </p>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const nombre = ref('')
const email = ref('')
const montoSeleccionado = ref(null)
const montosSugeridos = [500, 1000, 2500, 5000]

const donar = () => {
  if (!montoSeleccionado.value || montoSeleccionado.value < 1) {
    alert('Por favor ingresá un monto válido.')
    return
  }

  // En una versión real, podrías redirigir a un link de pago aquí
  console.log('Donación realizada por:', {
    nombre: nombre.value,
    email: email.value,
    monto: montoSeleccionado.value
  })

  alert(`¡Gracias, ${nombre.value}! Tu aporte de $${montoSeleccionado.value} marca la diferencia.`)

  // Limpiar formulario
  nombre.value = ''
  email.value = ''
  montoSeleccionado.value = null
}
</script>
