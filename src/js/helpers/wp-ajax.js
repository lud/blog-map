import superagent from 'superagent'
import superagentJsonapify from 'superagent-jsonapify'
superagentJsonapify(superagent)

function unpackJsonApi(response) {
  if (response.body) {
    // console.debug('%s', JSON.stringify(response.body, 0, ' '))
  }
  return response.body.data
}

export function errorLogger(httpError) {
  const { response } = httpError
  if (!response) {
    console.error("Response missing in", httpError)
    console.error(httpError)
  }
  if (response.body && response.body.errors) {
    response.body.errors.forEach(err => {
      console.error('[API Error] ' + err.title)
      if (err.detail) {
        console.debug(err.detail)
      }
      if (err.meta) {
        console.debug(JSON.stringify(err.meta, 0, '  '))
      }
    })
  } else {
    console.warn('undef response.body or response.body.errors : ', response.body)
  }
  throw httpError
}

// On the frontend, we use _wpmap_loc, on the backoffice window.ajaxurl
// exists
const endpoint = window.ajaxurl || window._wpmap_loc.ajaxurl

const api = {
  get(action, params = {}) {
    const query = Object.assign({}, params, {
      action
    })
    return superagent
      .get(endpoint)
      .accept('application/json')
      .query(query)
      .then(unpackJsonApi, errorLogger)
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
      .then(unpackJsonApi, errorLogger)
  },
  post(action, payload) {
    return superagent
      .post(endpoint)
      .accept('application/json')
      .send(asForm({
        action,
        payload: JSON.stringify(payload)
      }))
      .then(unpackJsonApi, errorLogger)
  }
}

function asForm(data) {
  const form = new FormData()
  Object.entries(data).map(([k, v]) => form.set(k, v))
  return form
}

export default api
