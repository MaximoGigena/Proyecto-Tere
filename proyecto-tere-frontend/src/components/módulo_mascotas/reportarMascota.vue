<template>
  <div class="fixed inset-0 z-40 bg-black/70 flex items-center justify-center">
    <!-- CONTENEDOR 1 -->
    <div v-if="!mostrarRazones && !razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">¿Pasó algo?</h2>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <div
        @click="mostrarRazones = true"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
      >
        <p class="text-red-600 font-bold text-sm tracking-wide">Denunciar mascota</p>
        <span class="text-black text-2xl font-bold transition-transform duration-150 group-hover:scale-110">&gt;</span>
      </div>
    </div>

    <!-- CONTENEDOR 2 -->
    <div v-else-if="mostrarRazones && !razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">Denunciar Mascota</h2>
      <p class="text-sm text-left text-gray-600 mt-1">
        Queremos que nuestra comunidad sea un espacio seguro para los usuarios y las mascotas. Tus denuncias son anónimas.
      </p>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <div
        v-for="razon in razones"
        :key="razon"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
        @click="seleccionarRazon(razon)"
      >
        <p class="text-gray-800 font-medium text-sm">{{ razon }}</p>
        <span class="text-black text-xl font-bold transition-transform duration-150 group-hover:scale-110">&gt;</span>
      </div>
    </div>

    <!-- CONTENEDOR 3 -->
    <div v-else-if="razonSeleccionada && !causaSeleccionada" class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">{{ razonSeleccionada }}</h2>
      <p class="text-sm text-left text-gray-600 mt-1 mb-2">
        Seleccioná el motivo específico de la denuncia.
      </p>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <div
        v-for="causa in causasEspecificas[razonSeleccionada] || []"
        :key="causa"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
        @click="seleccionarCausa(causa)"
      >
        <p class="text-gray-800 font-medium text-sm">{{ causa }}</p>
        <span class="text-black text-xl font-bold transition-transform duration-150 group-hover:scale-110">&gt;</span>
      </div>
      <div class="mt-4 text-center">
        <button @click="razonSeleccionada = null" class="text-xs text-blue-500 hover:underline">Volver</button>
      </div>
    </div>

    <!-- CONTENEDOR 4 -->
    <div v-else class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80">
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">Descripción opcional</h2>
      <p class="text-sm text-left text-gray-600 mt-1 mb-2">
        Podés darnos más detalles sobre esta denuncia. Esto nos ayuda a evaluar mejor el caso.
      </p>
      <button @click="cerrar" class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition">
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>
      <textarea
        v-model="descripcion"
        rows="4"
        placeholder="Escribí aquí..."
        class="w-full mt-2 text-sm border rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"
      ></textarea>

      <button
        @click="enviarDenuncia"
        class="w-full bg-red-600 text-white font-bold rounded-xl py-2 mt-4 hover:bg-red-700 transition"
      >
        Enviar denuncia
      </button>

      <div class="mt-2 text-center">
        <button @click="causaSeleccionada = null" class="text-xs text-blue-500 hover:underline">Volver</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

const emit = defineEmits(['close'])

const cerrar = () => {
  emit('close')
}

const mostrarRazones = ref(false)
const razonSeleccionada = ref(null)
const causaSeleccionada = ref(null)
const descripcion = ref('')

const razones = [
  'Maltrato Animal',
  'Perfil falso',
  'Contenido inapropiado',
  'Estafa o uso comercial',
  'Mascota ilegal',
]

const causasEspecificas = {
  'Maltrato Animal': ['Heridas visibles', 'Condiciones insalubres', 'Violencia en fotos/videos', 'Negligencia', 'Abandono', 'Explotación', 'Otro'],
  'Perfil falso': ['Fotos robadas', 'Fotos/Videos generadas por IA', 'Información falsa', 'Oferta sospechosa', 'Otro'],
  'Contenido inapropiado': ['Lenguaje ofensivo', 'Contenido sexual', 'Violencia explícita', 'Discriminación', 'Otro'],
  'Estafa o uso comercial': ['Venta encubierta', 'Publicidad engañosa', 'Cobro por servicios falsos', 'Intento de fraude', 'Otro'],
  'Mascota ilegal': ['Especie prohibida', 'Falta de permisos', 'Tráfico ilegal', 'Otro'],
}

const seleccionarRazon = (razon) => {
  razonSeleccionada.value = razon
}

const seleccionarCausa = (causa) => {
  causaSeleccionada.value = causa
}

const enviarDenuncia = () => {
  console.log('Razón:', razonSeleccionada.value)
  console.log('Causa específica:', causaSeleccionada.value)
  console.log('Descripción:', descripcion.value)

  // Aquí podés emitir un evento o enviar la data a un backend
  cerrar()

  // Reset para futuros usos
  mostrarRazones.value = false
  razonSeleccionada.value = null
  causaSeleccionada.value = null
  descripcion.value = ''
}
</script>
