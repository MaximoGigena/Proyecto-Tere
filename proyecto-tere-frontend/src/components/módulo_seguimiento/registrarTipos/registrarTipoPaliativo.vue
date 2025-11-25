<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Procedimiento Paliativo</h1>

    <form @submit.prevent="esEdicion ? actualizarProcedimiento() : registrarProcedimiento()" class="space-y-4">
      <!-- DATOS OBLIGATORIOS -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Obligatorios</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Columna izquierda -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Nombre del procedimiento paliativo</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="procedimiento.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Control del dolor crónico"
                />
                <font-awesome-icon 
                  :icon="['fas', 'magnifying-glass']" 
                  class="absolute inset-y-0 right-0 mt-3 text-xl flex items-center pr-3 text-gray-400 cursor-pointer hover:text-gray-600"
                />
              </div>
            </div>
          </div>

          <div>
            <label class="block font-medium">Descripción general</label>
            <textarea 
              v-model="procedimiento.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada del procedimiento"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium mb-2">Especie objetivo</label>
            <CarruselEspecieVeterinario v-model="especiesSeleccionadas" />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Objetivo terapéutico principal</label>
            <select v-model="procedimiento.objetivo" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="alivio_dolor">Alivio del dolor</option>
              <option value="mejora_movilidad">Mejora de movilidad</option>
              <option value="soporte_respiratorio">Soporte respiratorio</option>
              <option value="soporte_nutricional">Soporte nutricional</option>
              <option value="acompañamiento">Acompañamiento final</option>
              <option value="otro">Otro</option>
            </select>
            <input 
              v-if="procedimiento.objetivo === 'otro'"
              v-model="procedimiento.objetivoOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique el objetivo"
            />
          </div>

          <div>
            <label class="block font-medium">Frecuencia o duración sugerida</label>
            <div class="flex">
              <input v-model="procedimiento.frecuenciaValor" type="number" min="1" required class="w-1/2 border rounded-l p-2" placeholder="Cantidad" />
              <select v-model="procedimiento.frecuenciaUnidad" required class="w-1/2 border rounded-r p-2">
                <option value="horas">Horas</option>
                <option value="dias">Días</option>
                <option value="semanas">Semanas</option>
                <option value="meses">Meses</option>
                <option value="sesiones">Sesiones</option>
              </select>
            </div>
          </div>

          <div>
            <label class="block font-medium">Indicaciones clínicas comunes</label>
            <textarea 
              v-model="procedimiento.indicaciones" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Condiciones o síntomas que indican este procedimiento"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- DATOS OPCIONALES -->
      <div class="flex items-center my-6">
        <div class="flex-grow border-t border-gray-600"></div>
        <h5 class="px-4 text-center font-bold text-gray-800 whitespace-nowrap">Datos Opcionales</h5>
        <div class="flex-grow border-t border-gray-600"></div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-6">
        <div>
          <label class="block font-medium">Contraindicaciones</label>
          <textarea 
            v-model="procedimiento.contraindicaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Situaciones donde no aplicar este procedimiento"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos o efectos secundarios</label>
          <textarea 
            v-model="procedimiento.riesgos" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Posibles efectos adversos"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recursos necesarios</label>
          <div class="flex gap-2 items-center mb-1">
            <input 
              v-model="recursoTemporal" 
              type="text" 
              class="w-full border rounded p-2" 
              placeholder="Ej: Medicamento X, Equipo de oxígeno, etc."
              @keyup.enter="agregarRecurso"
            />
            <button 
              type="button"
              class="bg-green-500 text-white px-4 py-2 rounded font-bold hover:bg-green-700 transition-colors whitespace-nowrap"
              @click="agregarRecurso"
            >
              + Agregar
            </button>
          </div>
          <div class="flex flex-wrap gap-2 mt-2">
            <div 
              v-for="(recurso, index) in procedimiento.recursos" 
              :key="index"
              class="bg-gray-100 px-3 py-1 rounded-full flex items-center gap-2"
            >
              {{ recurso }}
              <button 
                type="button"
                @click="eliminarRecurso(index)"
                class="text-red-500 hover:text-red-700"
              >
                <font-awesome-icon :icon="['fas', 'times']" />
              </button>
            </div>
          </div>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <textarea 
            v-model="procedimiento.recomendaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Recomendaciones para el equipo médico"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="procedimiento.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante"
          ></textarea>
        </div>
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors">
          {{ esEdicion ? 'Actualizar' : '+' }} Tipo
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router' // ¡Agregar useRoute!
import { useAuth } from '@/composables/useAuth'
import CarruselEspecieVeterinario from '@/components/ElementosGraficos/CarruselEspecieVeterinario.vue'

const especiesSeleccionadas = ref([])

const router = useRouter()
const route = useRoute() // ¡Agregar esta línea!
const { accessToken, isAuthenticated, checkAuth } = useAuth()

const esEdicion = ref(false)
const procedimientoId = ref(null) // Variable para almacenar el ID

// Watch para sincronizar especiesSeleccionadas con procedimiento.especies
watch(especiesSeleccionadas, (newEspecies) => {
  procedimiento.especies = [...newEspecies]
})

// Verificar autenticación y cargar datos si es edición
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      showMessage('Debe iniciar sesión para acceder a esta página', 'error')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }
  
  // Verificar si hay un ID en la ruta (modo edición)
  if (route.params.id) {
    esEdicion.value = true
    procedimientoId.value = route.params.id
    await cargarPaliativo()
  }
})

const procedimiento = reactive({
  nombre: '',
  descripcion: '',
  especies: [],
  objetivo: '',
  objetivoOtro: '',
  indicaciones: '',
  frecuenciaValor: '',
  frecuenciaUnidad: 'dias',
  contraindicaciones: '',
  riesgos: '',
  recursos: [],
  recomendaciones: '',
  observaciones: ''
})

// Función para cargar los datos del procedimiento
const cargarPaliativo = async () => {
  try {
    const response = await fetch(`/api/tipos-procedimiento-paliativo/${procedimientoId.value}`, {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      }
    })

    if (!response.ok) {
      throw new Error('Error al cargar el procedimiento')
    }

    const result = await response.json()

    if (result.success) {
      const datos = result.data
      
      // Mapear los datos del API al formulario
      procedimiento.nombre = datos.nombre || ''
      procedimiento.descripcion = datos.descripcion || ''
      procedimiento.especies = datos.especies || [] // Cambiado de 'especie' a 'especies'
      procedimiento.objetivo = datos.objetivo_terapeutico || ''
      procedimiento.objetivoOtro = datos.objetivo_otro || ''
      procedimiento.indicaciones = datos.indicaciones_clinicas || ''
      procedimiento.frecuenciaValor = datos.frecuencia_valor || ''
      procedimiento.frecuenciaUnidad = datos.frecuencia_unidad || 'dias'
      procedimiento.contraindicaciones = datos.contraindicaciones || ''
      procedimiento.riesgos = datos.riesgos_efectos_secundarios || ''
      procedimiento.recursos = datos.recursos_necesarios || []
      procedimiento.recomendaciones = datos.recomendaciones_clinicas || ''
      procedimiento.observaciones = datos.observaciones || ''

      // ¡IMPORTANTE! Sincronizar especies seleccionadas después de cargar los datos
      if (datos.especies && Array.isArray(datos.especies)) {
        especiesSeleccionadas.value = [...datos.especies]
      }
      
    } else {
      throw new Error(result.message || 'Error al cargar los datos')
    }

  } catch (error) {
    console.error('Error al cargar el procedimiento:', error)
    alert('Error al cargar el procedimiento: ' + error.message)
  }
}

const recursoTemporal = ref('')

const agregarRecurso = () => {
  if (recursoTemporal.value.trim() !== '') {
    procedimiento.recursos.push(recursoTemporal.value.trim())
    recursoTemporal.value = ''
  }
}

const eliminarRecurso = (index) => {
  procedimiento.recursos.splice(index, 1)
}

const cancelar = () => {
  if (confirm('¿Está seguro que desea cancelar? Los datos no guardados se perderán.')) {
    router.back()
  }
}

const registrarProcedimiento = async () => {
  try {
    // Preparar datos según el modelo
    const datosEnvio = {
      nombre: procedimiento.nombre,
      descripcion: procedimiento.descripcion,
       especies: procedimiento.especies,
      objetivo_terapeutico: procedimiento.objetivo,
      objetivo_otro: procedimiento.objetivo === 'otro' ? procedimiento.objetivoOtro : null,
      frecuencia_valor: parseInt(procedimiento.frecuenciaValor),
      frecuencia_unidad: procedimiento.frecuenciaUnidad,
      indicaciones_clinicas: procedimiento.indicaciones,
      contraindicaciones: procedimiento.contraindicaciones || null,
      riesgos_efectos_secundarios: procedimiento.riesgos || null,
      recursos_necesarios: procedimiento.recursos,
      recomendaciones_clinicas: procedimiento.recomendaciones || null,
      observaciones: procedimiento.observaciones || null,
    };

    // Enviar al backend
    const response = await fetch('/api/tipos-procedimiento-paliativo', {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || 'Error al registrar el procedimiento');
    }

    if (result.success) {
      alert('Procedimiento paliativo registrado correctamente');
      router.push('/veterinarios/tipos/paliativos');
    } else {
      throw new Error(result.message);
    }

  } catch (error) {
    console.error('Error:', error);
    alert('Error al registrar el procedimiento: ' + error.message);
  }
};

const actualizarProcedimiento = async () => {
  try {

    if (!procedimiento.especies || procedimiento.especies.length === 0) {
      alert('Debe seleccionar al menos una especie objetivo')
      return
    }

    // Preparar datos según el modelo
    const datosEnvio = {
      nombre: procedimiento.nombre,
      descripcion: procedimiento.descripcion,
      especies: procedimiento.especies, // Cambiado de 'especie' a 'especies'
      objetivo_terapeutico: procedimiento.objetivo,
      objetivo_otro: procedimiento.objetivo === 'otro' ? procedimiento.objetivoOtro : null,
      frecuencia_valor: parseInt(procedimiento.frecuenciaValor),
      frecuencia_unidad: procedimiento.frecuenciaUnidad,
      indicaciones_clinicas: procedimiento.indicaciones,
      contraindicaciones: procedimiento.contraindicaciones || null,
      riesgos_efectos_secundarios: procedimiento.riesgos || null,
      recursos_necesarios: procedimiento.recursos,
      recomendaciones_clinicas: procedimiento.recomendaciones || null,
      observaciones: procedimiento.observaciones || null,
    };

    // Enviar al backend con PUT
    const response = await fetch(`/api/tipos-procedimiento-paliativo/${procedimientoId.value}`, {
      method: 'PUT',
      headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    const result = await response.json();

    if (!response.ok) {
      throw new Error(result.message || 'Error al actualizar el procedimiento');
    }

    if (result.success) {
      alert('Procedimiento actualizado correctamente');
      router.push('/veterinarios/tipos/paliativos');
    } else {
      throw new Error(result.message);
    }

  } catch (error) {
    console.error('Error:', error);
    alert('Error al actualizar el procedimiento: ' + error.message);
  }
};
</script>