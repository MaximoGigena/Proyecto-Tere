<template>
  <div class="w-full bg-gray-600 shadow-md fixed top-0 left-0 right-0 z-50">
    <div class="max-w-6xl mx-auto flex items-center">
      <img src="@/assets/Logo_Pagina_Oscura.png" alt="Logo TERE" class="h-10 mt-8 -ml-16 w-auto origin-left transform scale-625" />
    </div>
  </div>

  <div class="max-w-6xl mt-20 mx-auto p-6 max-h-[90vh] overflow-y-auto">
    <h1 class="text-4xl font-bold mb-4">{{ esEdicion ? 'Editar' : 'Registrar' }} Tipo de Diagnóstico</h1>

    <form @submit.prevent="confirmarAccion" class="space-y-4">
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
            <label class="block font-medium">Nombre del diagnóstico</label>
            <div class="flex gap-2">
              <div class="relative w-full">
                <input 
                  v-model="diagnostico.nombre" 
                  type="text" 
                  required 
                  class="w-full border rounded p-2 pr-10" 
                  placeholder="Ej: Moquillo canino, Insuficiencia renal crónica"
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
              v-model="diagnostico.descripcion" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Descripción detallada del diagnóstico"
            ></textarea>
          </div>

          <div>
            <label class="block font-medium">Clasificación</label>
            <select v-model="diagnostico.clasificacion" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="infeccioso">Infeccioso</option>
              <option value="genetico">Genético</option>
              <option value="nutricional">Nutricional</option>
              <option value="ambiental">Ambiental</option>
              <option value="traumatico">Traumático</option>
              <option value="degenerativo">Degenerativo</option>
              <option value="neoplasico">Neoplásico</option>
              <option value="otro">Otro</option>
            </select>
            <input 
              v-if="diagnostico.clasificacion === 'otro'"
              v-model="diagnostico.clasificacionOtro"
              type="text"
              class="w-full border rounded p-2 mt-2"
              placeholder="Especifique la clasificación"
            />
          </div>
        </div>

        <!-- Columna derecha -->
        <div class="space-y-4">
          <div>
            <label class="block font-medium">Especie afectada</label>
            <select v-model="diagnostico.especie" class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="canino">Canino</option>
              <option value="felino">Felino</option>
              <option value="ave">Ave</option>
              <option value="roedor">Roedor</option>
              <option value="exotico">Exótico</option>
              <option value="todos">Todos</option>
              <option value="ninguna">No aplica</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Evolución típica</label>
            <select v-model="diagnostico.evolucion" required class="w-full border rounded p-2">
              <option value="">Seleccione una opción</option>
              <option value="aguda">Aguda</option>
              <option value="cronica">Crónica</option>
              <option value="recurrente">Recurrente</option>
              <option value="autolimitada">Autolimitada</option>
              <option value="progresiva">Progresiva</option>
            </select>
          </div>

          <div>
            <label class="block font-medium">Criterios diagnósticos principales</label>
            <textarea 
              v-model="diagnostico.criterios_diagnosticos" 
              rows="3" 
              required 
              class="w-full border rounded p-2" 
              placeholder="Síntomas característicos, exámenes requeridos, etc."
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
          <label class="block font-medium">Tratamiento sugerido estándar</label>
          <textarea 
            v-model="diagnostico.tratamiento_sugerido" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Protocolo de tratamiento recomendado"
          ></textarea>
        </div>

        <div>
          <label class="block font-medium">Riesgos o complicaciones asociadas</label>
          <textarea 
            v-model="diagnostico.riesgos_complicaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Complicaciones comunes de este diagnóstico"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Recomendaciones clínicas</label>
          <textarea 
            v-model="diagnostico.recomendaciones_clinicas" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Recomendaciones para el manejo clínico"
          ></textarea>
        </div>

        <div class="col-span-full">
          <label class="block font-medium">Observaciones adicionales</label>
          <textarea 
            v-model="diagnostico.observaciones" 
            rows="3" 
            class="w-full border rounded p-2" 
            placeholder="Otra información relevante sobre este diagnóstico"
          ></textarea>
        </div>       
      </div>

      <div class="pt-4 flex items-center justify-center gap-4">
        <button type="button" @click="cancelar" class="bg-gray-500 text-white font-bold text-xl px-6 py-2 rounded-full hover:bg-gray-700 transition-colors">Cancelar</button>
        <button type="submit" class="bg-blue-500 text-white font-bold text-2xl px-6 py-2 rounded-full hover:bg-blue-700 transition-colors">
          {{ esEdicion ? 'Guardar' : '+ Tipo' }}
        </button>
      </div>
    </form>

    <!-- Modal de Confirmación -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg p-6 max-w-md mx-4">
        <h3 class="text-xl font-bold mb-4">
          {{ esEdicion ? 'Confirmar Edición' : 'Confirmar Registro' }}
        </h3>
        <p class="mb-6">
          {{ esEdicion 
            ? '¿Está seguro que desea guardar los cambios realizados en este diagnóstico?' 
            : '¿Está seguro que desea registrar este nuevo tipo de diagnóstico?' 
          }}
        </p>
        <div class="flex justify-end gap-4">
          <button 
            @click="showModal = false" 
            class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors"
          >
            Cancelar
          </button>
          <button 
            @click="ejecutarAccion" 
            class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition-colors"
          >
            {{ esEdicion ? 'Guardar' : 'Registrar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'

const router = useRouter()
const route = useRoute()

const { accessToken, isAuthenticated, checkAuth } = useAuth()

const showModal = ref(false)
const esEdicion = computed(() => {
  return route.params.id !== undefined || route.query.modo === 'edicion'
})

const diagnostico = reactive({
  nombre: '',
  descripcion: '',
  especie: '',
  clasificacion: '',
  clasificacion_otro: '', // CAMBIADO: de clasificacionOtro a clasificacion_otro
  criterios_diagnosticos: '', 
  evolucion: '',
  tratamiento_sugerido: '', 
  recomendaciones_clinicas: '', 
  riesgos_complicaciones: '', 
  observaciones: ''
})

// Verificar autenticación y cargar datos si es edición
onMounted(async () => {
  if (!isAuthenticated.value) {
    const isAuth = await checkAuth()
    if (!isAuth) {
      console.error('Debe iniciar sesión para acceder a esta página')
      setTimeout(() => {
        router.push('/')
      }, 2000)
      return
    }
  }

  // Si está en modo edición, cargar los datos existentes
  if (esEdicion.value) {
    await cargarDiagnostico()
  }
})

const cargarDiagnostico = async () => {
  try {
    const diagnosticoId = route.params.id
    if (diagnosticoId) {
      console.log('Cargando diagnóstico con ID:', diagnosticoId)
      
      const response = await fetch(`/api/tipos-diagnostico/${diagnosticoId}`, {
        headers: {
          'Authorization': `Bearer ${accessToken.value}`,
          'Accept': 'application/json'
        }
      })
      
      if (response.ok) {
        const result = await response.json()
        console.log('Datos recibidos del backend:', result)
        
        if (result.success && result.data) {
          const datos = result.data
          
          // Asignar los datos al reactive object, mapeando correctamente los campos
          Object.assign(diagnostico, {
            nombre: datos.nombre || '',
            descripcion: datos.descripcion || '',
            especie: datos.especie || '',
            clasificacion: datos.clasificacion || '',
            clasificacion_otro: datos.clasificacion_otro || '', // CAMBIADO
            criterios_diagnosticos: datos.criterios_diagnosticos || '',
            evolucion: datos.evolucion || '',
            tratamiento_sugerido: datos.tratamiento_sugerido || '',
            recomendaciones_clinicas: datos.recomendaciones_clinicas || '',
            riesgos_complicaciones: datos.riesgos_complicaciones || '',
            observaciones: datos.observaciones || ''
          })
          
          console.log('Datos asignados al formulario:', diagnostico)
        } else {
          console.error('Respuesta del servidor sin datos:', result)
          alert('No se pudieron cargar los datos del diagnóstico')
        }
      } else {
        console.error('Error en la respuesta HTTP:', response.status)
        alert('Error al cargar los datos del diagnóstico')
      }
    }
  } catch (error) {
    console.error('Error al cargar diagnóstico:', error)
    alert('Error al cargar los datos del diagnóstico: ' + error.message)
  }
}

const confirmarAccion = () => {
  showModal.value = true
}

const ejecutarAccion = async () => {
  showModal.value = false
  
  if (esEdicion.value) {
    await editarDiagnostico()
  } else {
    await registrarDiagnostico()
  }
}

const cancelar = () => {
  const mensaje = esEdicion.value 
    ? '¿Está seguro que desea cancelar? Los cambios no guardados se perderán.' 
    : '¿Está seguro que desea cancelar? Los datos no guardados se perderán.'
  
  if (confirm(mensaje)) {
    router.back()
  }
}

const registrarDiagnostico = async () => {
  try {
    const datosEnvio = {
      ...diagnostico,
      // Ya no necesitas mapear porque los nombres coinciden
      clasificacion_otro: diagnostico.clasificacion === 'otro' ? diagnostico.clasificacion_otro : null,
      tratamiento_sugerido: diagnostico.tratamiento_sugerido || null,
      riesgos_complicaciones: diagnostico.riesgos_complicaciones || null,
      recomendaciones_clinicas: diagnostico.recomendaciones_clinicas || null,
      observaciones: diagnostico.observaciones || null
    }

    console.log('Datos a enviar (registro):', datosEnvio);

    const response = await fetch('/api/tipos-diagnostico', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    console.log('Respuesta del servidor:', result);

    if (result.success) {
      alert('Tipo de diagnóstico registrado correctamente');
      router.push('/veterinarios/tipos/diagnosticos');
    } else {
      if (result.errors) {
        const errores = Object.values(result.errors).join('\n');
        alert('Errores de validación:\n' + errores);
      } else {
        alert('Error: ' + result.message);
      }
    }

  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al registrar el diagnóstico: ' + error.message);
  }
}

const editarDiagnostico = async () => {
  try {
    const datosEnvio = {
      ...diagnostico,
      // Ya no necesitas mapear porque los nombres coinciden
      clasificacion_otro: diagnostico.clasificacion === 'otro' ? diagnostico.clasificacion_otro : null,
      tratamiento_sugerido: diagnostico.tratamiento_sugerido || null,
      riesgos_complicaciones: diagnostico.riesgos_complicaciones || null,
      recomendaciones_clinicas: diagnostico.recomendaciones_clinicas || null,
      observaciones: diagnostico.observaciones || null
    }

    console.log('Datos a enviar (edición):', datosEnvio);

    const diagnosticoId = route.params.id
    const response = await fetch(`/api/tipos-diagnostico/${diagnosticoId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${accessToken.value}`
      },
      body: JSON.stringify(datosEnvio)
    });

    if (!response.ok) {
      const errorText = await response.text();
      console.error('Error response:', errorText);
      throw new Error(`HTTP error! status: ${response.status}`);
    }

    const result = await response.json();
    console.log('Respuesta del servidor:', result);

    if (result.success) {
      alert('Tipo de diagnóstico actualizado correctamente');
      router.push('/veterinarios/tipos/diagnosticos');
    } else {
      if (result.errors) {
        const errores = Object.values(result.errors).join('\n');
        alert('Errores de validación:\n' + errores);
      } else {
        alert('Error: ' + result.message);
      }
    }

  } catch (error) {
    console.error('Error completo:', error);
    alert('Error al actualizar el diagnóstico: ' + error.message);
  }
}
</script>