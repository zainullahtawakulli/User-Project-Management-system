import api from '@/services/api'

export const searchWorkspace = (query) => api.get('/search', { params: { q: query } })
