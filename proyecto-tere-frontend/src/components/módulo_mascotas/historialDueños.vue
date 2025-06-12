<template>
  <div class="bg-white p-6 h-full">
    <div class="w-full h-full bg-white rounded-lg overflow-hidden flex-col">

      <!-- Componente importado -->
      <OrdenDropdown @cambioOrden="ordenAsc = $event" />

      <!-- Owners list -->
      <div
        v-for="(owner, index) in ownersOrdenados"
        :key="index"
        class="p-4 border-b last:border-b-0"
      >
        <div class="mb-2">
          <h2 class="font-semibold text-lg">{{ owner.nombre }}</h2>
        </div>

        <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
          <div>
            <span class="font-medium">Fecha de Adopción:</span> {{ owner.adopcion }}
          </div>
          <div>
            <span class="font-medium">Fecha de Desligo:</span> {{ owner.desligo }}
          </div>
        </div>

        <div class="flex justify-end space-x-2">
          <button
            :class="[
              'px-3 py-1 rounded flex items-center',
              owner.contactable
                ? 'bg-orange-500 hover:bg-orange-600 text-white'
                : 'bg-gray-300 text-gray-700 opacity-50 cursor-not-allowed',
            ]"
            :disabled="!owner.contactable"
          >
            <span class="mr-1">Email</span>
            <font-awesome-icon :icon="['fas', 'envelope']" />
          </button>
          <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded flex items-center">
            <span class="mr-1">whatsApp</span>
            <font-awesome-icon :icon="['fab', 'whatsapp']" />
          </button>
          <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded flex items-center">
            <span class="mr-1">Telegram</span>
            <font-awesome-icon :icon="['fab', 'telegram']" />
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import OrdenDropdown from '@/components/módulo_mascotas/OrdenDropdown.vue'

const ordenAsc = ref(true)

const owners = [
  {
    nombre: 'Nombre del Tutor',
    adopcion: '15/05/2020',
    desligo: '20/10/2022',
    contactable: true,
  },
  {
    nombre: 'Otro Tutor',
    adopcion: '01/03/2023',
    desligo: 'Presente',
    contactable: false,
  },
]

const ownersOrdenados = computed(() => {
  return [...owners].sort((a, b) => {
    const fechaA = new Date(a.adopcion.split('/').reverse().join('-'))
    const fechaB = new Date(b.adopcion.split('/').reverse().join('-'))
    return ordenAsc.value ? fechaA - fechaB : fechaB - fechaA
  })
})
</script>

  