import {
  Store as BaseStore
} from 'svelte/store'
import {
  getPostsConfig,
  patchPost
} from './admin-api'
import * as list from '../helpers/list.js'

const PID = '_id'

class Store extends BaseStore {

  constructor(state = {}, options) {
    state.rawPosts = state.rawPosts || []
    super(state, options)

    // initial data
    this.set({
      initLoaded: false,
      mapId: 'default-map'
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

  getPost(postID) {
    const { posts } = this.get()
    return list.keyFindOrFail(posts, PID, postID)
  }

  setFetchedPost(post) {
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
}

const store = new Store()

store

export default store
