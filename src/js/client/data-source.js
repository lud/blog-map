import wpajax from '../helpers/wp-ajax.js'

export function fetchPosts(mapID) {
    return wpajax.get('getMapData', { mapID })
}

export function getPageMetaDescription(url) {
    return wpajax.get(url)
}
