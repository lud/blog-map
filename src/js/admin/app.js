import domready from 'domready'

import WpMapAdmin from './WpMapAdmin.html'
import store from './store'

function initFetch() {
    return store.fetch()
}

function setup() {
  const app = new WpMapAdmin({
    target: document.getElementById('wpmap-admin-app'),
    store
  })
}

domready(function(){
    setup()
    initFetch()
})

// window.initFetch = initFetch
