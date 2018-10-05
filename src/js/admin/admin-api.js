import wpajax from '../helpers/wp-ajax.js'

export function getPostsConfig() {
  return wpajax.get('getPostsConfig')
}

export function patchPost(postID, changeset) {
  return wpajax
    .patch('patchPost', {
      postID,
      changeset
    })
}
