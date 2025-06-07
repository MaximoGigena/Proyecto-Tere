<template>
  <div class="min-h-screen bg-gradient-to-b from-teal-500 to-white p-6 flex flex-col items-center space-y-10">
     <!-- Logo en esquina superior izquierda -->
    <router-link @click="volverOInicio()" to="/" class="absolute top-4 left-4">
      <img 
        src="@/assets/Logo_Pagina_Oscura.png" 
        alt="Logo TERE"
        class="h-60 w-auto hover:opacity-80 transition -mt-16"
      />
    </router-link>
   
    <!-- Contenedor principal -->
      <h1 class="text-6xl font-bold text-teal-100 text-center">Ayudanos con tu donación</h1>
      <p class="text-xl text-teal-50 font-bold text-center max-w-2xl">
        Tu apoyo es fundamental para mantener y mejorar el sitio web. 
        Con tu donación, podremos seguir ofreciendo contenido de calidad y nuevas funcionalidades.
      </p>

      <!-- Sección QRs -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
       
        <!-- Mercado Pago -->
        <div class="flex flex-col items-center text-center p-2 bg-white rounded-2xl shadow-lg space-y-5">
          <!-- Logo -->
          <img 
            src="https://logosdown.com/wp-content/uploads/2023/08/mercado-pago-logo-0.png" 
            alt="Logo Mercado Pago"
            class="h-60 object-contain -mt-17"
          />
          
          <!-- Alias -->
          <p class="text-2xl text-gray-700 font-medium bg-yellow-100 px-4 py-1 rounded-full -mt-12">
            Alias: <span class="font-bold text-teal-700">maximo.571.arena.mp</span>
          </p>

          <!-- Línea divisoria -->
          <hr class="w-full border-t-2 border-teal-600 mt-11" />

          <!-- Instrucción -->
          <p class="text-sm text-gray-600 max-w-xs">
            Escaneá este código o hacé clic 
            <a 
              href="https://link.mercadopago.com.ar/proyectotereong" 
              target="_blank" 
              class="text-teal-600 underline hover:text-teal-800 font-medium"
            >
              acá
            </a> para donar.
          </p>

          <!-- Código QR -->
          <img 
            src="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=https://link.mercadopago.com.ar/proyectotereong" 
            alt="QR Mercado Pago Proyecto TERE" 
            class="w-40 h-40 object-contain border border-gray-300 rounded-md" 
          />
        </div>


       <!-- PayPal -->
        <div class="flex flex-col items-center text-center p-2 bg-white rounded-2xl shadow-md space-y-4">
          <!-- Logo -->
          <img 
            src="https://cdn.iconscout.com/icon/free/png-256/free-paypal-5-226456.png?f=webp" 
            alt="Logo PayPal"
            class="h-60 object-contain -mt-16"
          />

         
          <!-- Botón incrustado -->
          <form action="https://www.paypal.com/ncp/payment/Y5C5R2ZQNENXW" method="post" target="_blank" class="flex flex-col items-center space-y-1 -mt-12">
            <input 
              type="submit" 
              value="Donar con PayPal"
              class="bg-yellow-400 text-black font-bold px-8 py-2 rounded-md text-base cursor-pointer hover:bg-yellow-300 transition"
            />
            <img src="https://www.paypalobjects.com/images/Debit_Credit_APM.svg" alt="cards" class="h-5" />
          </form>


          <!-- Línea divisoria -->
          <hr class="w-full border-t-2 border-teal-600 my-4 mt-6" />

          <!-- Descripción -->
          <p class="text-sm text-gray-600 max-w-xs">
            Escaneá este código para hacer una donación internacional.
          </p>

          <!-- Código QR -->
          <img 
            src="@/assets/QR- paypal.png" 
            alt="QR PayPal TERE" 
            class="w-40 h-40 object-contain border border-gray-300 rounded-md" 
          />
        </div>
    </div>
      <p class="text-xl text-gray-600 text-center w-full">
        Todas las donaciones son utilizadas para mejorar la calidad y funcionamiento de la pagina.
      </p>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router'
const router = useRouter()

function volverOInicio() {
  if (window.history.length > 1) {
    router.back()
  } else {
    router.push('/')
  }
}
</script>