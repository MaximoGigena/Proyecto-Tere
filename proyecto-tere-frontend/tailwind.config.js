/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{vue,js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {
      animation: {
        huellas: 'moverHuellas 120s linear infinite',
      },
      keyframes: {
        moverHuellas: {
          '0%': { backgroundPosition: '0 0' },
          '100%': { backgroundPosition: '0 200px' },
        },
      },
    },
  },
  plugins: [
    require('tailwind-scrollbar')({ nocompatible: true }),
    require('tailwind-scrollbar-hide'),
  ],
}
