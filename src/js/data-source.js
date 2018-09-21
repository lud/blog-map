import axios from 'axios'
import FAKE_DATA from './fake-data'

export function fetchPosts(url) {
  return Promise.resolve(FAKE_DATA)
    // return axios.get(url)
}

export function getPageMetaDescription(url) {
    return axios.get(url)
        .then(function(response) {
            return response.data && response.data.description || ''
        })
}
