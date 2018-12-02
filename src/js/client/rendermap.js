// import 'leaflet/dist/leaflet.css'
// import L from 'leaflet'
// import 'leaflet-providers'
// import 'leaflet-active-area'

// function noop() {}


// function layerToStyleable(layer) {
//   if (layer.layer && typeof layer.layer.setStyle === 'function')
//     return layer.layer
//   if (typeof layer.setStyle === 'function')
//     return layer
//   console.error('layer.setStyle undefined, layer:', layer)
//   throw new Error("Layer does not accept styles")
// }

// export default function renderMap(el, posts, opts = {}) {

//   const onSelectFeature = (opts.onSelectFeature || noop)

//   const map = L.map(el).setView([0, 0], 1)

//   let selectionCache = {
//     marker: L.circleMarker()
//   }

//   window.aaa = selectionCache

//   function isSelectedMarker(marker) {
//     return selectionCache.marker === marker
//   }

//   function willSetMarkerStyleWithSelected(style) {
//     return function(marker) {
//       const selected = isSelectedMarker(marker)
//       const key = (selected ? 'selected-' : '') + style
//       return setMarkerStyle(marker, key)
//     }
//   }

//   function withFeatureLayer(callback) {
//     return function featureGroupEventHandler(layer) {
//       return callback(layer.layer)
//     }
//   }

//   map.addLayer(configureBackgroundLayer(opts.backgroundStyle))

//   map.addLayer(postsLayer)

//   map.fitBounds(postsLayer.getBounds(), {
//     padding: [40, 40],
//     maxZoom: 5,
//     animate: false
//   })

//   return {
//     map
//   }

// }
