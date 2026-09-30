import api from '@/services/api'

export const getRoles = () => {
  return api.get('/roles')
}

export const getRole = (id) => {
  return api.get(`/roles/${id}`)
}

export const getPermissions = () => {
  return api.get('/roles/permissions')
}

export const createRole = (data) => {
  return api.post('/roles', data)
}

export const updateRole = (id, data) => {
  return api.put(`/roles/${id}`, data)
}

export const deleteRole = (id) => {
  return api.delete(`/roles/${id}`)
}
