import api from '@/services/api'

export const getTasks = (search = '', page = 1, projectId = null) => {
  return api.get('/tasks', {
    params: {
      search,
      page,
      project_id: projectId,
    },
  })
}

export const getTask = (id) => {
  return api.get(`/tasks/${id}`)
}

export const createTask = (taskData) => {
  return api.post('/tasks', taskData)
}

export const updateTask = (id, taskData) => {
  return api.put(`/tasks/${id}`, taskData)
}

export const deleteTask = (id) => {
  return api.delete(`/tasks/${id}`)
}
