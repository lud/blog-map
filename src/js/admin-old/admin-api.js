import wpajax from '../helpers/wp-ajax.js'

export function getHelpPage(lang) {
  return wpajax.get('getHelpPage', { lang })
}

export function getPostsConfig(mapID) {
  return wpajax.get('getPostsConfig', { mapID })
}

export function getMapsConfig() {
  return wpajax.get('getMapsConfig')
}

export function patchPostMeta(postID, changeset) {
  return wpajax
    .patch('patchPostMeta', {
      postID,
      changeset
    })
}

export function patchPostLayer(postID, mapID, changeset) {
  return wpajax
    .patch('patchPostLayer', {
      postID,
      mapID,
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
