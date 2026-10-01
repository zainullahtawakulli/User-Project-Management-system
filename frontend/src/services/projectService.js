import api from '@/services/api'

export const getProjects = (search = '', page = 1, perPage = 10) => {
  return api.get('/projects', {
    params: {
      search,
      page,
      per_page: perPage,
    },
  })
}

export const getProject = (id) => {
  return api.get(`/projects/${id}`)
}

export const createProject = (projectData) => {
  return api.post('/projects', projectData)
}

export const updateProject = (id, projectData) => {
  return api.put(`/projects/${id}`, projectData)
}

export const deleteProject = (id) => {
  return api.delete(`/projects/${id}`)
}
