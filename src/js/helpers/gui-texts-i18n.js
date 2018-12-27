const translations = {}
const DEFAULT_LANG = 'en-GB'

function createTranslation(base, strings) {
  const extended = Object.create(base)
  Object.assign(extended, strings)
  return extended
}

function registerTranslation(code, translation) {
  translations[code] = translation
}

registerTranslation(DEFAULT_LANG,
  createTranslation({}, {
    countryCode: DEFAULT_LANG,
    'PinColors': 'Colors (fill / outline)',
    'BackgroundLayer': 'Map Background',
    'BlogMapConfig': 'Blog Map',
    'PinDesign': 'Pin Design',
    'PostsList': 'Posts & Pages',
    'PublishedOn': 'Published',
    'ReadMore': 'Read more',
    'PickCountry': 'Pick a country',
    'NoGeocodingResults': 'No results for "$0"',
    'TypeFullLocationName': "Try to type the full location name",
  }))
registerTranslation('fr-FR',
  createTranslation(translations[DEFAULT_LANG], {
    countryCode: 'fr-FR',
    'Providers': 'Fournisseurs',
    'Variants': 'Variantes',
    'Size': 'Taille',
    'Shape': 'Forme',
    'PinColors': 'Couleurs (remplissage / contour)',
    'BackgroundLayer': 'Fond de carte',
    'BlogMapConfig': 'Carte du blog',
    'PinDesign': 'Style des pins',
    'PublishedOn': 'Publié le',
    'ReadMore': 'Lire',
    'Close': 'Fermer',
    'PickCountry': 'Choisir pays',
    'NoGeocodingResults': 'Aucun résultat pour « $0 »',
    'TypeFullLocationName': "Essayez d'entre le nom complet du lieu recherché",
    'Help': 'Aide',
  }))
registerTranslation('fr', createTranslation(translations['fr-FR'], {}))

export function getI18nFunction(code) {
  const source = translations[code] || translations[DEFAULT_LANG]
  if (typeof translations[code] === 'undefined') {
    console.warn("Language %s unavailable", code)
  }
  return function getText(key, ...args) {
    if (typeof source[key] === 'undefined') {
      console.warn("missing translation for %s:'%s'", source.countryCode, key)
      return key
    }
    let text = source[key], i = args.length
    while (i--) {
      text = text.replace(`$${i}`, args[i])
    }
    return text
  }
}

export const lang = navigator.language || navigator.userLanguage

export function getI18nFunctionDefault() {
  return getI18nFunction(lang)
}

const __ = getI18nFunctionDefault()
// const __ = getI18nFunction('en-GB')
export default __

