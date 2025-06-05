<template>
  <div class="fixed inset-0 z-40 bg-black/70 flex items-center justify-center">
    <!-- CONTENEDOR 1 -->
    <div
      v-if="!mostrarRazones"
      class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80"
    >
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">¿Pasó algo?</h2>
      <button
        @click="cerrar"
        class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition"
      >
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>

      <div
        @click="mostrarRazones = true"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
      >
        <p class="text-red-600 font-bold text-sm tracking-wide">Denunciar Usuario</p>
        <span class="text-black text-2xl font-bold transition-transform duration-150 group-hover:scale-110">
          &gt;
        </span>
      </div>
    </div>

    <!-- CONTENEDOR 2 -->
    <div
      v-else
      class="relative bg-white rounded-2xl shadow-xl px-3 py-4 w-80"
    >
      <h2 class="text-xl text-center font-extrabold text-gray-800 -mt-4">Denunciar Usuario</h2>
      <p class="text-sm text-left text-gray-600 mt-1">
        Queremos que nuestra comunidad sea un espacio seguro para los usarios y las mascotas. Tus denuncias son anónimas.
      </p>
      <button
        @click="cerrar"
        class="absolute top-1 -mt-1 right-3 text-black text-2xl font-bold hover:scale-110 transition"
      >
        <font-awesome-icon :icon="['fas', 'xmark']" />
      </button>

      <div
        v-for="razon in razones"
        :key="razon"
        class="group flex items-center justify-between bg-white hover:bg-red-100 transition rounded-xl px-4 py-3 cursor-pointer mt-3 w-full"
        @click="seleccionarRazon(razon)"
      >
        <p class="text-gray-800 font-medium text-sm">{{ razon }}</p>
        <span class="text-black text-xl font-bold transition-transform duration-150 group-hover:scale-110">
          &gt;
        </span>
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

const razones = [
  'Comportamiento inapropiado',
  'Spam o publicidad',
  'Perfil falso',
  'Contenido inapropiado',
  'Estafa o uso comercial',
]

const seleccionarRazon = (razon) => {
  console.log('Razón seleccionada:', razon)
  // Podés mostrar un nuevo paso o guardar la razón
}
</script>
