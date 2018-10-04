import superagent from 'superagent'
import superagentJsonapify from 'superagent-jsonapify'
superagentJsonapify(superagent)
console.log('superagentJsonapify', superagentJsonapify)

function errorLogger(httpError) {
  const { response } = httpError
  if (response.body.errors) {
    response.body.errors.forEach(err => {
      console.error('[API Error] ' + err.title)
      if (err.detail) {
        console.log(err.detail)
      }
      if (err.meta) {
        console.log(JSON.stringify(err.meta, 0, '  '))
      }
    })
  } else {
    console.warn('undef response.body.errors : ', response.body.errors)
  }
  throw httpError
}

const endpoint = window.ajaxurl

const api = {
  get(action, params = {}) {
    return superagent
      .get(endpoint)
      .accept('application/json')
      .query(Object.assign({}, params, {
        action
      }))
      .then(null, errorLogger)
  },
  patch(action, payload) {
    return superagent
      .post(endpoint)
      .accept('application/json')
      .send(asForm({
        action,
        _method: 'patch',
        payload: JSON.stringify(payload)
      }))
      .then(null, errorLogger)
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
