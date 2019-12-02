// import mapRenderer from './map-renderer'
import WpMap from './WpMap.html'
import domready from 'domready'
import loadFa from '../helpers/fa-loader'

domready(function(){
    const { maps } = window._wpmap
    maps.forEach(function(map) {
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
