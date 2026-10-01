import api from '@/services/api'

export const getTasks = (search = '', page = 1, projectId = null, perPage = 10) => {
  return api.get('/tasks', {
    params: {
      search,
      page,
      project_id: projectId,
      per_page: perPage,
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
