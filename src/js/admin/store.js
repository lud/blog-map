import {
  Store as BaseStore
} from 'svelte/store'
import {
  getPostsConfig,
  patchPost
} from './admin-api'

class Store extends BaseStore {

  constructor(state = {}, options) {
    state.rawPosts = state.rawPosts || []
    super(state, options)

    // initial data
    this.set({
      initLoaded: false
    })

    // computations
    this.compute('posts', ['rawPosts'], (rawPosts) => {
      console.log('rawPosts', rawPosts)
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
    for(let i = 0; i < posts.length; i++) {
        if (posts[i].ID === postID) {
            return posts[i]
        }
    }
    throw new Error("Post not found : " + postID.toString())
  }

  actTogglePostVisibility(postID, visibility) {
    // No need to update the post in the state as this comes from
    // an input so it has already its current value. But if the
    // update fails, we must revert the current post to its server
    // side state
    const currentPost = this.getPost(postID)
    patchPost(postID, {
        _meta: {
            wpmap_visibility: visibility
        }
      })
      .then(
        data => this.updatePost(postID, data),
        err => this.updatePost(postID, currentPost)
      )
  }
}

const store = new Store()

store

export default store
