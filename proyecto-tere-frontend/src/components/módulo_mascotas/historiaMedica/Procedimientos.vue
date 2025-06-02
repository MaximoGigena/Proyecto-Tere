<template>
  <div class="p-4 min-w-[300px]">
    <h2 class="text-xl font-semibold text-gray-800 flex items-center mb-4">
      <font-awesome-icon :icon="['fas', 'hand-holding-medical']" class="mr-2" />
      Procedimientos Quirúrgicos
    </h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div 
        v-for="item in procedimientos" 
        :key="item.id" 
        class="bg-white rounded-lg shadow-sm p-4 border border-gray-100 hover:shadow-md transition-shadow"
      >
        <h3 class="text-lg font-semibold text-blue-600 mb-3 flex items-center">
          <font-awesome-icon :icon="['fas', item.icon]" class="mr-2" />
          {{ item.nombre }}
        </h3>
        <ul class="space-y-2 pl-2">
          <li 
            v-for="(proc, idx) in item.procedimientos" 
            :key="idx" 
            class="text-gray-700 text-sm pl-4 relative"
          >
            <span class="absolute left-0 top-2 w-2 h-2 bg-blue-300 rounded-full"></span>
            {{ proc }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {
      procedimientos: [
        {
          id: 'cirugias-mayores',
          nombre: 'Cirugías Mayores',
          icon: 'hospital',
          procedimientos: [
            'Esterilización completa',
            'Reparación de fracturas complejas',
            'Cirugía de tejidos blandos avanzada'
          ]
        },
        {
          id: 'cirugias-menores',
          nombre: 'Cirugías Menores',
          icon: 'clinic-medical',
          procedimientos: [
            'Sutura de heridas',
            'Extracciones dentales',
            'Drenaje de abscesos'
          ]
        }
      ]
    }
  }
}
</script>

<style scoped>
/* Solo mantenemos las transiciones y pseudoelementos complejos */
.procedimiento-item::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0.5rem;
  width: 0.5rem;
  height: 0.5rem;
  background-color: #93c5fd;
  border-radius: 9999px;
}
</style>