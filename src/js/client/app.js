// import mapRenderer from './map-renderer'
import WpMap from './WpMap.html'
import domready from 'domready'
import loadFa from '../helpers/fa-loader'

domready(function(){
    const { maps } = window._wpmap
    maps.forEach(function(map) {
      console.log('map', map)
      console.log('map.el', map.el)
      const app = new WpMap({
        target: map.el,
        data: {
          mapID: map.mapID,
          config: map.config
        }
      })
    })
    loadFa()
})
