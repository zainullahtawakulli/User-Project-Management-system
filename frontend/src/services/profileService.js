import api from '@/services/api'

export const getProfile = () => {
  return api.get('/profile')
}

export const updateProfile = (profileData) => {
  return api.put('/profile', profileData)
}

export const updatePassword = (passwordData) => {
  return api.put('/profile/password', passwordData)
}
