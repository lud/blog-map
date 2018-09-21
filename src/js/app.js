import {
  fetchPosts
} from './data-source'
// import mapRenderer from './map-renderer'
import WpMap from './WpMap.html';
import domready from 'domready'


domready(function(){
    console.log('window._wpmap', window._wpmap)
    const { maps } = window._wpmap
    maps.forEach(function(map){
        const app = new WpMap({
          target: map.el,
          data: { background: 'mapnik' }
        })
        fetchPosts()
          .then(featureCollection => {
            console.log('fetched posts', featureCollection)
            app.setFeatureCollection(featureCollection)
          })
    })
})
