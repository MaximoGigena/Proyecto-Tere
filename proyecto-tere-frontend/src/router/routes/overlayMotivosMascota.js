import motivosMascota from '@/components/módulo_mascotas/motivosBajaMascota.vue';

export const motivosBajaMascota = [
  {
    path: "/explorar/perfil/mascotas/:id/baja",
    name: "darBajaMascota",
    components: {
      overlay: motivosMascota   // 👈 ahora sí, lo montamos en el <router-view name="overlay" />
    },
    props: {
      overlay: true
    }
  }
];