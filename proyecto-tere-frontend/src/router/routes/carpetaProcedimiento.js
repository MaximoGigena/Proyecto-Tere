
export const overlayProcedimiento = [
  {
    path: '/procedimientos/:id',
    component: () => import('@/components/módulo_seguimiento/partesProcedimiento/procedimientoLayout.vue'),
    name: 'procedimientos',
    meta: { requiresAuth: true, preserveQuery: ['from'] },
    children: [
      {
        path: '',
        name: 'procedimiento-detalles',
        component: () => import('@/components/módulo_seguimiento/partesProcedimiento/detallesProcedimiento.vue'),
        meta: { preserveQuery: ['from'], exact: true  }
         
      },
      {
        path: 'observaciones',
        name: 'procedimiento-observaciones',
        component: () => import('@/components/módulo_seguimiento/partesProcedimiento/observacionesProcedimiento.vue'),
        meta: { preserveQuery: ['from'] }
      },
      {
        path: 'derivaciones',
        name: 'procedimiento-derivaciones',
        component: () => import('@/components/módulo_seguimiento/partesProcedimiento/derivaciones.vue'),
         meta: { preserveQuery: ['from'] }
      }
    ]
  }
]