import RegistrarCentro from '@/components/módulo_veterinario/registrarVeterinaria.vue'

export const registrarVeterinaria = [
   {
    path: '/registro/registroCentroVeterinario',
    component: RegistrarCentro,
    name: 'RegistrarCentroVeterinario',
  },
  {
  path: '/registro/registroCentroVeterinario/:id?',
  component: RegistrarCentro,
  name: 'registroCentroVeterinario',
  }
]