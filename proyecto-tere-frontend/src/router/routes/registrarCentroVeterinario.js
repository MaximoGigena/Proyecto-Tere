import RegistrarCentro from '@/components/módulo_veterinario/registrarVeterinaria.vue'

export const registrarVeterinaria = [
  {
    path: '/registro/registroCentroVeterinario/editar/:id',  // ← RUTA ESPECÍFICA PARA EDITAR
    component: RegistrarCentro,
    name: 'editarCentroVeterinario',
  },
  {
    path: '/registro/registroCentroVeterinario',  // ← RUTA ESPECÍFICA PARA REGISTRO (SIN PARÁMETROS)
    component: RegistrarCentro,
    name: 'registrarCentroVeterinario',
  }
]