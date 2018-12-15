import wpajax from '../helpers/wp-ajax.js'

export function getPostsConfig(mapID) {
  return wpajax.get('getPostsConfig', { mapID })
}

export function getMapsConfig() {
  return wpajax.get('getMapsConfig')
}

export function patchPost(postID, changeset) {
  return wpajax
    .patch('patchPost', {
      postID,
      changeset
    })
}

export function patchMap(mapID, changeset) {
    return wpajax
    .patch('patchMap', {
        mapID,
        changeset
    })
}
