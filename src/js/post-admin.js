import Geocoder from './components/Geocoder.html'
console.log(`window._wpmap`, window._wpmap)
const container = document.getElementById('wpmap-post-admin-app')

const geocoder = new Geocoder({target: container})

const pre = document.createElement('pre')
pre.innerText = JSON.stringify(window._wpmap, 0, 2)
container.appendChild(pre) 