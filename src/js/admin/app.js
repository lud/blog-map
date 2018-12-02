import domready from 'domready'

import WpMapAdmin from './WpMapAdmin.html'
import store from './store'
import loadFa from '../helpers/fa-loader'

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
    loadFa()
})

// window.initFetch = initFetch
