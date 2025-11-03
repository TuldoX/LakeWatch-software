import axios from 'axios'

const API_BASE_URL = '/api'

export const api = {
  async getUserProbes(userId) {
    try {
      const response = await axios.get(`${API_BASE_URL}/users/${userId}/probes`)
      return response.data
    } catch (error) {
      console.error('Error fetching user probes:', error)
      throw error
    }
  },

  async getProbeData(probeId, hours = 200) {
    try {
      const response = await axios.get(`${API_BASE_URL}/probes/${probeId}/data/${hours}`)
      return response.data
    } catch (error) {
      console.error('Error fetching probe data:', error)
      throw error
    }
  }
}