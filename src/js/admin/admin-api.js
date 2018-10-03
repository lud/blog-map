import superagent from 'superagent'
import superagentJsonapify from 'superagent-jsonapify'
superagentJsonapify(superagent)
console.log('superagentJsonapify', superagentJsonapify)

const endpoint = window.ajaxurl

const api = {
  get(action, params = {}) {
    return superagent
      .get(endpoint)
      .query(Object.assign({}, params, {
        action
      }))
  },
  patch(action, payload) {
    return superagent
      .post(endpoint)
      .send(asForm({
        action,
        _method: 'patch',
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
      console.log('resp', resp)
      const posts = resp.body.data
      console.log('posts', posts)
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
      const post = resp.body.data
      return post
    })

}
