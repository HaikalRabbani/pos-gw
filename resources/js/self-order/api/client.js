import axios from 'axios'

const client = axios.create({
  baseURL: '/api/v1/public',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

export default client
