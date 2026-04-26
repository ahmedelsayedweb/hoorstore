const API_URL = import.meta.env.VITE_API_URL

export const purchasesApi = {
  async getAll(params = {}) {
    const query = new URLSearchParams(params).toString()
    const res = await fetch(`${API_URL}/purchases${query ? '?' + query : ''}`)
    return res.json()
  },
  async create(data) {
    const res = await fetch(`${API_URL}/purchases`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
    return res.json()
  },
  async update(id, data) {
    const res = await fetch(`${API_URL}/purchases/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
    return res.json()
  },
  async remove(id) {
    const res = await fetch(`${API_URL}/purchases/${id}`, { method: 'DELETE' })
    return res.json()
  },
}

export const salesApi = {
  async getAll(params = {}) {
    const query = new URLSearchParams(params).toString()
    const res = await fetch(`${API_URL}/sales${query ? '?' + query : ''}`)
    return res.json()
  },
  async create(data) {
    const res = await fetch(`${API_URL}/sales`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
    return res.json()
  },
  async update(id, data) {
    const res = await fetch(`${API_URL}/sales/${id}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    })
    return res.json()
  },
  async remove(id) {
    const res = await fetch(`${API_URL}/sales/${id}`, { method: 'DELETE' })
    return res.json()
  },
}
