// import mapRenderer from './map-renderer'
import WpMap from './WpMap.html'
import domready from 'domready'

domready(function(){
    console.log('window._wpmap', window._wpmap)
    const { maps } = window._wpmap
    maps.forEach(function(map) {
        const app = new WpMap({
          target: map.el,
          data: {
            background: 'OpenTopoMap',
            mapID: map.mapID
          }
        })
    })
})
