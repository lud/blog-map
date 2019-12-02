export const defaultStyleSpec = {
  default: {
    radius: 6,
    fillColor: "#f88",
    color: "#c00",
    weight: 2,
    opacity: 1,
    fillOpacity: 0.8
  },
  hover: {
    color: "#c00",
    fillColor: "#fcc"
  },
  'highlight-hover': {
    color: "#0ac",
    fillColor: "#caf"
  },
  'highlight-default': {
    color: "#00c",
    fillColor: "#ccf"
  }
}

function FeatureStyle(styleSpec = defaultStyleSpec) {
  this.spec = styleSpec
}

FeatureStyle.prototype.get = function(key) {
  if (typeof this.spec[key] === 'undefined') {
    throw new Error("Style for " + key + " is not defined")
  }
  return this.spec[key]
}

FeatureStyle.prototype.setLayerStyle =  function(layer, key) {
  if (layer.setStyle) {
    layer.setStyle(this.get(key))
  } else {
    // @todo reiplement layer hovering
    // console.warn("Layer is not styleable")
  }
  return layer
}

export default FeatureStyle
