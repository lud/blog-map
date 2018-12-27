import {
  Store as BaseStore
} from 'svelte/store'
import {
  getMapsConfig,
  getPostsConfig,
  patchPostLayer as _patchPostLayer,
  patchPostMeta as _patchPostMeta,
  patchMap
} from './admin-api'
import * as list from '../helpers/list.js'

const PID = '_id'

function simpleSorter(a, b) {
  return a < b ? -1 : a > b ? 1 : 0
}

// Seamless Immutable sort
function sort(array, comparator = simpleSorter) {
    array = array.slice()
    return array.sort(comparator)
}

function sortBy(fn, desc) {
  return desc
    ? (a, b) => simpleSorter(fn(b), fn(a))
    : (a, b) => simpleSorter(fn(a), fn(b))
}

class Store extends BaseStore {

  constructor(state = {}, options) {
    state.rawPosts = state.rawPosts || []
    state.mapConfigs = state.mapConfigs || {}
    state.mapID = state.mapID || 'default-map'
    state.initialLoading = new Promise(function willNeverResolve(){})
    state.sortProp = null

    super(state, options)

    // computations
    this.compute('posts', ['rawPosts'], (rawPosts) => {
      return rawPosts
    })

    this.compute('mapConfig', ['mapConfigs', 'mapID'], (mapConfigs, mapID) => {
      const conf =  mapConfigs ? mapConfigs[mapID] : null
      return conf
    })
  }

  fetch() {
    const promise = Promise.all([
      this.fetchMaps(),
      this.fetchPosts(this.get().mapID)
    ])
    this.set({ initialLoading: promise })
  }

  fetchMaps() {
    return getMapsConfig()
      .then(data => {
        const mapConfigs = {}
        data.forEach(map => {
          mapConfigs[map.id] = map
        })
        this.set({ mapConfigs })
      })
  }

  fetchPosts(mapID) {
    return getPostsConfig(mapID)
      .then(data => {
        this.set({
          rawPosts: data
        })
      })
  }

  sortBy(sortProp) {
    // We will sort the rawPosts instead of sorting on computed
    // "posts" so we only sort once sortBy() is called
    const { rawPosts, sortProp: previous } = this.get()
    const desc = sortProp === previous
    console.debug('from rawPosts', rawPosts)
    const sorted = sort(rawPosts, sortBy(p => p.props[sortProp], desc))
    console.debug('sorted', sorted.map(p => p.props[sortProp]).join(','))
    this.set({ rawPosts: sorted, sortProp: desc ? null : sortProp })
  }

  getPost(postID) {
    const { posts } = this.get()
    return list.keyFindOrFail(posts, PID, postID)
  }

  setFetchedPost(post, opts = {}) {
    return this.updateRawPost(post._id, () => post, opts)
  }

  updateRawPost(postID, fn, opts = {}) {
    const rawPosts = list.keyUpdate(this.get().rawPosts, PID, postID, fn)
    this.set({ rawPosts })
    if (opts.refresh) {
      this.refreshMap()
    }
  }

  resetPostLayerConf(postID, layer, opts = {}) {
    return this.updateRawPost(postID, post => Object.assign({}, post, {layer}), opts)
  }

  resetPostMeta(postID, meta, opts = {}) {
    return this.updateRawPost(postID, post => Object.assign({}, post, {meta}), opts)
  }

  patchPostLayer(post, mapID, changeset, opts = {refresh: true}) {
    _patchPostLayer(post._id, mapID, changeset).then(
      newLayer => this.resetPostLayerConf(post._id, newLayer, opts),
      err => this.setFetchedPost(post)
    )
  }

  patchPostMeta(post, changeset, opts = {refresh: true}) {
    _patchPostMeta(post._id, changeset).then(
      newMeta => this.resetPostMeta(post._id, newMeta, opts),
      err => this.setFetchedPost(post)
    )
  }

  actTogglePostVisibilities(postID, visibility) {
    // No need to update the post in the state as this comes from
    // an input so it has already its current value. But if the
    // update fails, we must revert the current post to its server
    // side state
    const post = this.getPost(postID)
    const { mapID } = this.get()
    const newLayer = Object.assign({}, post.layer, {
        visible: visibility
    })
    this.patchPostLayer(post, mapID, newLayer)
  }

  actSetPostCountryCode(postID, { alpha2, lat, lon }) {
    const post = this.getPost(postID)
    const clone = JSON.parse(JSON.stringify(post))
    clone.meta.wpmap_country_alpha2 = alpha2
    this.setFetchedPost(clone)
    const metaChangeset = {
      wpmap_country_alpha2: alpha2
    }
    if (lat || lon) {
      metaChangeset.wpmap_latlng = [lat, lon]
    }
    this.patchPostMeta(post, metaChangeset)
  }

  actSetPostGeocoding(postID, { display_name: geocoded, lat, lon }) {
    const post = this.getPost(postID)
    this.patchPostMeta(post, {
      wpmap_geocoded: geocoded,
      wpmap_latlng: [lat, lon],
    })
  }

  actSetPostIcon(postID, icon) {
    const post = this.getPost(postID)
    const { mapID } = this.get()
    const newLayer = Object.assign({}, post.layer, { icon })
    this.patchPostLayer(post, mapID, newLayer)
  }

  actSetPinConfig({ height, radius, fillColor, strokeColor }) {
    const { mapID, mapConfig } = this.get()
    const currentConfig = mapConfig
    const pinConfig = { height, radius, fillColor, strokeColor }
    patchMap(mapID, { pin_config: pinConfig })
      .then(
        data => this.setFetchedMapConfig(data),
        err => {
          console.error("Could not save map")
          // this.setFetchedMapConfig(currentConfig)
        }
      )
  }

  actSetBGLayerConfig({ background }) {
    const { mapID, mapConfig } = this.get()
    const currentConfig = mapConfig
    patchMap(mapID, { background })
      .then(
        data => this.setFetchedMapConfig(data),
        err => {
          console.error("Could not save map")
          // this.setFetchedMapConfig(currentConfig)
        }
      )
  }

  setFetchedMapConfig(conf) {
    const { id } = conf
    let { mapConfigs } = this.get()
    mapConfigs = Object.assign({}, mapConfigs, {[id]: conf})
    this.set({ mapConfigs })
    this.refreshMap()
  }

  refreshMap() {
    this.fire('refreshMap')
  }
}

const store = new Store()

export default store
