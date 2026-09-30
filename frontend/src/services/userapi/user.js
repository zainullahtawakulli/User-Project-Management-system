import api from '@/services/api'

export const getUsers = () => {
  return api.get('/users')
}

export const searchUsers = (search) => {
  return api.get('/users', {
    params: { search },
  })
}

export const createUser = (data) => {
  return api.post('/users', data)
}

export const getUser = (id) => {
  return api.get(`/users/${id}`)
}

export const updateUser = (id, data) => {
  return api.put(`/users/${id}`, data)
}

export const deleteUser = (id) => {
  return api.delete(`/users/${id}`)
}

export const getUserOptions = () => {
  return api.get('/users/options')
}
