import L from 'leaflet'

// we will patch the meta functionality directly on leaflet

function createMetaInterface(map) {
    const store = {}
    return function meta(/*[key, [value]]*/) {
      if (arguments.length === 1) {
        const [key] = arguments
        return store[key]
      } else if (arguments.length === 2) {
        const [key, value] = arguments
        store[key] = value
        return map
      } else if (arguments.length === 0) {
        return Object.assign({}, store)
      }
    }
}


L.Map.include({
  meta() {
    // on the first call to meta we will initialize the meta storage
    // and then we will overwrite the meta function with the meta
    // access behaviour
    const metaFunction = createMetaInterface(this)
    this.meta = metaFunction
    // we still must return an actual information for our first call
    return metaFunction.apply(this, arguments)
  }
})

export function buildBackgroundLayer(bgType) {
  switch (bgType) {
    case 'mapnik':
      return L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: 'Ⓒ <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
      })
    case 'Stamen.Terrain':
    case 'Stamen.TerrainBackground':
    case 'Stamen.Toner':
    case 'Stamen.TonerBackground':
    case 'Stamen.TonerHybrid':
    case 'Stamen.TonerLabels':
    case 'Stamen.TonerLines':
    case 'Stamen.TonerLite':
    case 'Stamen.Watercolor':
      return L.tileLayer.provider(bgType)
  }
}