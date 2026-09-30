import api from '@/services/api'

export const getDashboard = () => {
  return api.get('/dashboard')
}
