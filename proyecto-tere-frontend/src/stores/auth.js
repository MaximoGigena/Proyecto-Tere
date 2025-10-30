// stores/auth.js
import { defineStore } from 'pinia'
import axios from 'axios'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null,
    token: localStorage.getItem('token') || null,
  }),

  actions: {
    async setToken(token) {
      this.token = token
      localStorage.setItem('token', token)
      axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
    },

    async checkAuth() {
      if (!this.token) return false

      try {
        const res = await axios.get('/api/user', { // ✅ Cambiar endpoint
          headers: {
            Authorization: `Bearer ${this.token}`,
          },
        })
        this.user = res.data
        return true
      } catch (error) {
        console.error('Error checking auth:', error)
        this.logout()
        return false
      }
    },

    logout() {
      // ✅ Llamar al backend para revocar token
      axios.post('/api/logout', {}, {
        headers: {
          Authorization: `Bearer ${this.token}`
        }
      }).catch(error => {
        console.error('Logout error:', error)
      })
      
      this.user = null
      this.token = null
      localStorage.removeItem('token')
      delete axios.defaults.headers.common['Authorization']
    },
  },
})
