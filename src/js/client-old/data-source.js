import wpajax from '../helpers/wp-ajax.js'


export function fetchPosts(mapID) {
    return wpajax.get('getMapData', { mapID })
}

export function getPostInfos(postID) {
  return wpajax.get('getPostInfos', { postID })
}
