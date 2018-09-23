import domready from 'domready'

import WpMapAdmin from './WpMapAdmin.html'
import store from './store'

domready(function() {
  const app = new WpMapAdmin({
    target: document.getElementById('wpmap-admin-app'),
    store
  })
  store.fetch()
})
