import axios from 'axios'
const endpoint = window.ajaxurl

const api = {
  get(action, params = {}) {
    return axios.get(endpoint, {
      params: Object.assign({}, params, {
        action
      })
    })
  },
  patch(action, payload) {
  	return axios.post(endpoint, asForm({
  		action,
  		_method: 'patch',
  		lol: 'pat"ch',
  		payload: JSON.stringify(payload)
  	}))
  }
}

function asForm(data) {
	const form = new FormData()
	Object.entries(data).map(([k, v]) => form.set(k, v))
	return form
}

export function getPostsConfig() {
  return api
    .get('getPostsConfig')
    .then(resp => {
      const posts = resp.data.data
      return posts
    })
}

export function patchPost(postID, changeset) {
  return api
    .patch('patchPost', {
      postID,
      changeset
    })
    .then(resp => {
      const post = resp.data.data
      return post
    })

}
