import HomeView from '@/components/home.vue'
import SeleccionarRegistro from '@/components/módulo_usuario/seleccionarRegistroUsuario.vue'
import DashboardView from '@/components/módulo_usuario/ExplorarEncuentros.vue'
import LoginView from '@/components/módulo_usuario/loginView.vue'

export const RutasLoginRegistro = [
    { path: '/', name: 'Home', component: HomeView},
    { path: '/seleccionarRegistro', name: 'SeleccionarRegistro', component: SeleccionarRegistro},
    { path: '/dashboard', name: 'Dashboard', component: DashboardView},
    { path: '/login', name: 'Login', component: LoginView},
    // fallback
    //{ path: '/:pathMatch(.*)*', name: 'NotFound', component: () => import('@/views/NotFound.vue') },
]