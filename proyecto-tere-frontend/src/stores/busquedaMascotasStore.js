// stores/busquedaMascotasStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useBusquedaStore = defineStore('busquedaMascotas', () => {
  const busqueda = ref('')
  const tipoBusqueda = ref('nombre')
  const mascotas = ref([])
  const cargando = ref(false)

  function setResultados(resultados) {
    console.log('🔄 Store: setResultados llamado con:', resultados);
    mascotas.value = resultados
  }

  function setBusqueda(termino, tipo) {
    console.log('🔄 Store: setBusqueda llamado con:', { termino, tipo });
    busqueda.value = termino
    tipoBusqueda.value = tipo
  }

  function limpiarResultados() {
    mascotas.value = []
    busqueda.value = ''
    tipoBusqueda.value = 'nombre'
    cargando.value = false
  }

  // ✅ AGREGAR ESTA FUNCIÓN
  function setCargando(estado) {
    console.log('🔄 Store: setCargando llamado con:', estado);
    cargando.value = estado
  }

  return {
    busqueda,
    tipoBusqueda,
    mascotas,
    cargando,
    setResultados,
    setBusqueda,
    limpiarResultados,
    setCargando  // ✅ EXPORTARLA
  }
})