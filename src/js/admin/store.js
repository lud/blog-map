import {
  Store as BaseStore
} from 'svelte/store'
import {
  getMapsConfig,
  getPostsConfig,
  patchPost,
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
      this.fetchPosts()
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

  fetchPosts() {
    return getPostsConfig()
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
    console.log('from rawPosts', rawPosts)
    const sorted = sort(rawPosts, sortBy(p => p.props[sortProp], desc))
    console.log('sorted', sorted.map(p => p.props[sortProp]).join(','))
    this.set({ rawPosts: sorted, sortProp: desc ? null : sortProp })
  }

  getPost(postID) {
    const { posts } = this.get()
    return list.keyFindOrFail(posts, PID, postID)
  }

  setFetchedPost(post, opts = {}) {
    let { rawPosts, mapID } = this.get()
    rawPosts = list.keyReplace(rawPosts, PID, post._id, post)
    this.set({ rawPosts })
    if (opts.refresh) {
      console.log('@todo refresh map')
    }
  }

  actTogglePostVisibilities(postID, visibility) {
    // No need to update the post in the state as this comes from
    // an input so it has already its current value. But if the
    // update fails, we must revert the current post to its server
    // side state
    const post = this.getPost(postID)
    const newVisibilities = Object.assign({}, post.meta.wpmap_visibilities, {
        [this.get().mapID]: visibility
    })
    patchPost(postID, {
        meta: {
            wpmap_visibilities: newVisibilities
        }
      })
      .then(
        data => this.setFetchedPost(data, {refresh: true}),
        err => this.setFetchedPost(post)
      )
  }

  actSetPostCountryCode(postID, alpha2) {
    const post = this.getPost(postID)
    const clone = JSON.parse(JSON.stringify(post))
    clone.meta.wpmap_country_alpha2 = alpha2
    this.setFetchedPost(clone)
    patchPost(postID, {
        meta: {
            wpmap_country_alpha2: alpha2
        }
      })
      .then(
        data => this.setFetchedPost(data, {refresh: true}),
        err => this.setFetchedPost(post)
      )
  }

  actSetPostGeocoding(postID, { display_name: geocoded, lat, lon }) {
    const latlng = [lat, lon]
    const post = this.getPost(postID)

    patchPost(postID, {
        meta: {
          wpmap_geocoded: geocoded,
          wpmap_latlng: latlng,
        }
      })
      .then(
        data => this.setFetchedPost(data, {refresh: true}),
        err => this.setFetchedPost(post)
      )
  }

  actSetPinConfig({ height, radius, fillColor, strokeColor }) {
    console.log('actSetPinConfig', {height, radius, fillColor, strokeColor })
  }

}

const store = new Store()

console.warn('@todo do not publish in window in prod')
window.store = store

export default store
