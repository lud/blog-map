// Ce module permet d'utiliser des arrays contenant des objets similaires, par
// exemple ayant tous une propriété 'id'.
// On peut alors demander l'objet dans le tableau qui a un certain id
"use strict"

function strf(format, ...args) {
  return format.replace(/%s/g, function() {
    return args.shift()
  })
}

let DEFAULT_NOT_FOUND_VALUE = {
  _isListoNotFoundValue: true
}

// returns the first occurence of an object with a matching key
export const keyFind = (list, propName, propVal, defaultItem) => {
  let i, len = list.length
  for (i = 0; i < len; i++) {
    let item = list[i]
    if (item[propName] === propVal) return item
  }
  return defaultItem
}

// returns the first occurence of an object with a matching key or throws
export const keyFindOrFail = (list, propName, propVal, errmsg) => {
  errmsg = errmsg || "Could not find item %s (key: '%s')"
  let val = keyFind(list, propName, propVal, DEFAULT_NOT_FOUND_VALUE)
  if (val === DEFAULT_NOT_FOUND_VALUE) {
    throw new Error(strf(errmsg, propName, propVal))
  } else {
    return val
  }
}

// replace the first occurence of item matching the key
export const keyReplace = (list, propName, propVal, newItem) => {
  return keyUpdate(list, propName, propVal, () => newItem)
}

// remove all matching occurences
export const propDelete = (list, propName, propVal) => {
  return list.filter(item => item[propName] !== propVal)
}

// return all matching occurences
export const propFind = (list, propName, propVal) => {
  return list.filter(item => item[propName] === propVal)
}

// return an object with all elements matching in .match property and all others
// elements in .other property.
export const propSplit = (list, propName, propVal) => {
  let i
  let len = list.length
  let match = [],
    other = []
  for (i = 0; i < len; i++) {
    let item = list[i]
    if (item[propName] === propVal) {
      match.push(item)
    } else {
      other.push(item)
    }
  }
  return {
    match,
    other
  }
}

export const propMax = (list, propName, defaultVal = -Infinity) => {
  let i
  let len = list.length
  let maxVal = defaultVal
  for (i = 0; i < len; i++) {
    let currentVal = list[i][propName]
    if (currentVal > maxVal) {
      maxVal = currentVal
    }
  }
  return maxVal
}

// return an object with all elements for whose callback returns truthy in
// .match property and all others elements in .other property.
export const splitWith = (list, callback) => {
  let i
  let len = list.length
  let match = [],
    other = []
  for (i = 0; i < len; i++) {
    let item = list[i]
    if (callback(item)) {
      match.push(item)
    } else {
      other.push(item)
    }
  }
  return {
    match,
    other
  }
}

// return the list splited at the index of the first element for wich the
// callback returns falsy, respectively in .before and .after properties
// The first to return falsy is in the .after
export const splitWhile = (list, callback) => {
  let i
  let len = list.length
  let before = [],
    after = []
  for (i = 0; i < len; i++) {
    let item = list[i]
    if (callback(item)) {
      before.push(item)
    } else {
      after = list.slice(i)
      break
    }
  }
  return {
    before,
    after
  }
}

// keyUpdate the first occurence of item matching the key
export const keyUpdate = (list, propName, propVal, callback) => {
  let i
  let len = list.length
  let newList = []
  let found = false
  for (i = 0; i < len; i++) {
    let item = list[i]
    if (item[propName] === propVal) {
      newList.push(callback(item))
      return newList.concat(list.slice(i + 1))
    } else {
      newList.push(item)
    }
  }
  throw new Error("List item not found with item." + propName + " = " + propVal)
}

// remove first matching occurence
export const keyDelete = (list, propName, propVal) => {
  let i
  let len = list.length
  let newList = []
  let found = false
  for (i = 0; i < len; i++) {
    let item = list[i]
    if (item[propName] === propVal) {
      return newList.concat(list.slice(i + 1))
    } else {
      newList.push(item)
    }
  }
  throw new Error("List item not found with item." + propName + " = " + propVal)
}

export const splice = (list, index, deleteCount, ...addItems) => {
  let before = list.slice(0, index)
  let rest = list.slice(index + deleteCount)
  return before.concat(addItems).concat(rest)
}

export const dedupe = (list) => {
  return list.filter(function(item, pos) {
    // If the index found for the current item is not pos, it means
    // that item is present before in the list
    return list.indexOf(item) === pos;
  })
}
