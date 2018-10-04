import {
  Store as BaseStore
} from 'svelte/store'
import {
  getPostsConfig,
  patchPost
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
    super(state, options)

    // initial data
    this.set({
      initLoaded: false,
      mapId: 'default-map',
      sortProp: null
    })

    // computations
    this.compute('posts', ['rawPosts'], (rawPosts) => {
      return rawPosts
    })
  }

  fetch() {
    getPostsConfig()
      .then(data => {
        this.set({
          rawPosts: data,
          initLoaded: true
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

  setFetchedPost(post) {
    // console.log('setFetchedPost post', post)
    let { rawPosts } = this.get()
    rawPosts = list.keyReplace(rawPosts, PID, post._id, post)
    this.set({ rawPosts })
  }

  actTogglePostVisibilities(postID, visibility) {
    // No need to update the post in the state as this comes from
    // an input so it has already its current value. But if the
    // update fails, we must revert the current post to its server
    // side state
    const post = this.getPost(postID)
    const newVisibilities = Object.assign({}, post.meta.wpmap_visibilities, {
        [this.get().mapId]: visibility
    })
    patchPost(postID, {
        meta: {
            wpmap_visibilities: newVisibilities
        }
      })
      .then(
        data => this.setFetchedPost(data),
        err => this.setFetchedPost(post)
      )
  }

  actSetPostCountryCode(postID, alpha2) {
    console.log('actSetPostCountryCode postID', postID)
    console.log('actSetPostCountryCode what', alpha2)

    const post = this.getPost(postID)
    patchPost(postID, {
        meta: {
            wpmap_country_alpha2: alpha2
        }
      })
      .then(
        data => this.setFetchedPost(data),
        err => this.setFetchedPost(post)
      )

  }
}

const store = new Store()

console.warn('@todo do not publish in window in prod')
window.store = store

export default store
