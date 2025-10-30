<!-- DerivacionesProcedimiento.vue -->
<template>
  <div class="p-4 space-y-4">
    <h2 class="text-xl font-semibold text-gray-800">Derivaciones registradas</h2>

    <div v-if="derivaciones.length" class="space-y-3">
      <div
        v-for="derivacion in derivaciones"
        :key="derivacion.id"
        class="bg-white rounded-2xl shadow p-4 border border-gray-200"
      >
        <div class="flex justify-between items-center">
          <div>
            <p class="text-sm text-gray-500">Fecha:</p>
            <p class="text-base font-medium">{{ formatearFecha(derivacion.fecha) }}</p>
          </div>

          <div>
            <p class="text-sm text-gray-500">Destino:</p>
            <p class="text-base font-medium">{{ derivacion.destino.nombre }}</p>
          </div>
        </div>

        <div class="mt-3">
          <p class="text-sm text-gray-500">Motivo de la derivación:</p>
          <p class="text-gray-800">{{ derivacion.motivo || 'No especificado' }}</p>
        </div>

        <div v-if="derivacion.observaciones" class="mt-2">
          <p class="text-sm text-gray-500">Observaciones:</p>
          <p class="text-gray-800">{{ derivacion.observaciones }}</p>
        </div>
      </div>
    </div>

    <div v-else class="text-center text-gray-500">
      <p>No hay derivaciones registradas para este procedimiento.</p>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const router = useRouter()

const props = defineProps({
  procedimiento: {
    type: Object,
    required: true
  }
})

// Verificar y mantener el parámetro 'from' si no está presente
onMounted(() => {
  if (!route.query.from && route.meta.originalFrom) {
    router.replace({
      name: route.name,
      params: route.params,
      query: { ...route.query, from: route.meta.originalFrom }
    })
  }
})




// Lógica: Fetch de las derivaciones
onMounted(async () => {
  try {
    const res = await fetch(`/api/procedimientos/${props.procedimientoId}/derivaciones`)
    if (!res.ok) throw new Error('Error al cargar derivaciones')
    derivaciones.value = await res.json()
  } catch (error) {
    console.error('Error cargando derivaciones:', error)
  }
})

// Utilidad para formato de fecha
const formatearFecha = (fechaISO) => {
  return new Date(fechaISO).toLocaleDateString('es-AR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

const route = useRoute()
const derivaciones = ref([])
const loading = ref(false)
const error = ref(null)

const cargarDerivaciones = async () => {
  try {
    loading.value = true
    if (!props.procedimientoId) {
      throw new Error('ID de procedimiento no definido')
    }
    const response = await fetch(`/api/procedimientos/${props.procedimientoId}/derivaciones`)
    if (!response.ok) throw new Error('Error al cargar derivaciones')
    derivaciones.value = await response.json()
  } catch (err) {
    error.value = err.message
    console.error('Error cargando derivaciones:', err)
  } finally {
    loading.value = false
  }
}

// Cargar al montar el componente
onMounted(cargarDerivaciones)

// Opcional: Recargar cuando cambie el ID
watch(() => props.procedimientoId, cargarDerivaciones)
</script>
