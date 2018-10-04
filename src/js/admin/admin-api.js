import superagent from 'superagent'
import superagentJsonapify from 'superagent-jsonapify'
superagentJsonapify(superagent)

function responseLogger(response) {
  if (response.body) {
    console.log('%s', JSON.stringify(response.body, 0, ' '))
  }
  return response
}

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
      .then(responseLogger, errorLogger)
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
      .then(responseLogger, errorLogger)
  },
  post(action, payload) {
    return superagent
      .post(endpoint)
      .accept('application/json')
      .send(asForm({
        action,
        payload: JSON.stringify(payload)
      }))
      .then(responseLogger, errorLogger)
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
      const posts = resp.body.data
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

// export function publishMap(mapID) {
//   return api
//     .post('publishMap', {
//       mapID: mapID
//     })
// }
