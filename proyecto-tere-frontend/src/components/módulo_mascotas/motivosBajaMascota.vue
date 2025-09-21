<!-- components/módulo_mascotas/motivosBajaMascota.vue -->
<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-xl w-96 p-6">
      <h2 class="text-xl font-semibold mb-4 text-center">
        Dar de baja mascota
      </h2>

      <label for="motivo" class="block text-sm font-medium text-gray-700 mb-2">
        Selecciona el motivo de baja
      </label>
      <select
        v-model="motivoSeleccionado"
        id="motivo"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-red-400"
      >
        <option value="" disabled>-- Seleccionar motivo --</option>
        <option v-for="motivo in motivos" :key="motivo.id" :value="motivo.id">
          {{ motivo.descripcion }}
        </option>
      </select>

      <textarea
        v-model="observacion"
        rows="3"
        placeholder="Observaciones (opcional)"
        class="w-full border border-gray-300 rounded-lg px-3 py-2 mb-4 focus:outline-none focus:ring-2 focus:ring-red-400"
      ></textarea>

      <div class="flex justify-end gap-3">
        <button
          @click="cancelar"
          class="px-4 py-2 rounded-lg bg-gray-200 hover:bg-gray-300"
        >
          Cancelar
        </button>
        <button
          @click="confirmarBaja"
          :disabled="!motivoSeleccionado"
          class="px-4 py-2 rounded-lg bg-red-500 text-white hover:bg-red-600 disabled:opacity-50"
        >
          Confirmar
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue"
import axios from "axios"
import router from "@/router"

const emit = defineEmits(["cerrar", "confirmar"])

// 🚀 Constantes locales como fallback
const MOTIVOS_POR_DEFECTO = [
  { id: 1, descripcion: "Fallecimiento de la mascota" },
  { id: 2, descripcion: "Extraviada" },
  { id: 3, descripcion: "Adoptada por otra persona" },
  { id: 4, descripcion: "Traslado de domicilio" },
  { id: 5, descripcion: "Problemas de convivencia" },
]

const motivos = ref([...MOTIVOS_POR_DEFECTO])
const motivoSeleccionado = ref("")
const observacion = ref("")

const confirmarBaja = () => {
  const payload = {
    motivo_id: motivoSeleccionado.value,
    observacion: observacion.value
  }
  emit("confirmar", payload)
}

const cancelar = () => {
  router.back()
}
</script>


