<!-- components/usuario/DatosOpcionales.vue -->
<template>
  <div>
    <div class="flex items-center my-6">
      <div class="flex-grow border-t border-gray-600"></div>
      <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">
        Datos Opcionales
      </h5>
      <div class="flex-grow border-t border-gray-600"></div>
    </div>

    <p class="mb-4">
      Si bien estos datos son opcionales, completarlos nos ayuda a conocer mejor tus intereses,
      hábitos y estilo de vida, lo que puede mejorar la experiencia en la plataforma.
    </p>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">

      <!-- Ocupación -->
      <div>
        <label class="block font-medium">Ocupación</label>
        <input
          v-model="usuario.ocupacion"
          type="text"
          class="w-full border rounded p-2"
          placeholder="Ej: Estudiante, Médico, Freelancer"
        />
      </div>

      <!-- Tipo de vivienda -->
      <div>
        <label class="block font-medium">Tipo de vivienda</label>
        <select
          v-model="usuario.tipoVivienda"
          class="w-full border rounded p-2"
        >
          <option disabled value="">Seleccionar</option>
          <option value="departamento">Departamento</option>
          <option value="casa">Casa</option>
          <option value="campo">Casa en el campo</option>
          <option value="otro">Otro</option>
        </select>
      </div>

      <!-- Experiencia con mascotas -->
      <div>
        <label class="block font-medium">Experiencia con mascotas</label>
        <select
          v-model="usuario.experienciaMascotas"
          class="w-full border rounded p-2"
        >
          <option disabled value="">Seleccionar</option>
          <option value="nueva">Nunca tuve mascotas</option>
          <option value="poca">Tuve pocas veces</option>
          <option value="media">Tengo experiencia moderada</option>
          <option value="alta">Experto/a en cuidado animal</option>
        </select>
      </div>

      <!-- Convive con niños -->
      <div>
        <label class="block font-medium">Convive con niños</label>
        <select
          v-model="usuario.conviveConNiños"
          class="w-full border rounded p-2"
        >
          <option disabled value="">Seleccionar</option>
          <option value="si">Sí</option>
          <option value="no">No</option>
        </select>
      </div>

      <!-- Convive con mascotas -->
      <div>
        <label class="block font-medium">Convive con otras mascotas</label>
        <select
          v-model="usuario.conviveConMascotas"
          class="w-full border rounded p-2"
        >
          <option disabled value="">Seleccionar</option>
          <option value="si">Sí</option>
          <option value="no">No</option>
        </select>
      </div>

      <!-- Descripción -->
      <div class="col-span-full">
        <label class="block font-medium mb-1">Descripción</label>
        <textarea
          v-model="usuario.descripcion"
          rows="4"
          maxlength="500"
          placeholder="Contanos más sobre vos: tu estilo de vida, motivaciones para adoptar, experiencia con animales, etc."
          class="w-full border rounded p-2 resize-none focus:outline-none focus:ring"
        ></textarea>

        <p class="text-sm text-gray-500 text-right mt-1">
          {{ usuario.descripcion?.length || 0 }}/500 caracteres
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, defineProps, defineEmits, watch } from 'vue'

// Props para recibir datos existentes
const props = defineProps({
  datosIniciales: {
    type: Object,
    default: () => ({})
  }
})

// Emits para enviar datos actualizados
const emit = defineEmits(['datosActualizados'])

// Estado reactivo para los datos opcionales
const usuario = reactive({
  ocupacion: props.datosIniciales.ocupacion || '',
  tipoVivienda: props.datosIniciales.tipoVivienda || '',
  experienciaMascotas: props.datosIniciales.experienciaMascotas || '',
  conviveConNiños: props.datosIniciales.conviveConNiños || '',
  conviveConMascotas: props.datosIniciales.conviveConMascotas || '',
  descripcion: props.datosIniciales.descripcion || ''
})

// Watcher para emitir cambios
watch(usuario, (nuevosDatos) => {
  emit('datosActualizados', nuevosDatos)
}, { deep: true })

// Método para obtener datos (útil para el componente padre)
const obtenerDatos = () => {
  return { ...usuario }
}

// Método para limpiar datos
const limpiarDatos = () => {
  usuario.ocupacion = ''
  usuario.tipoVivienda = ''
  usuario.experienciaMascotas = ''
  usuario.conviveConNiños = ''
  usuario.conviveConMascotas = ''
  usuario.descripcion = ''
}

// Exponer métodos al componente padre
defineExpose({
  obtenerDatos,
  limpiarDatos
})
</script>