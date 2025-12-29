import CuentaSuspendida from '@/components/módulo_usuario/CuentaSuspendida.vue'

export const UsuarioSuspendido = [
  {
    path: '/cuenta-suspendida',
    name: 'cuenta-suspendida',
    component: CuentaSuspendida,
    meta: { 
      title: 'Cuenta Suspendida',
      requiresAuth: true
    }
  }
]
