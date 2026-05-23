/**
 * Composable para gerenciamento dinâmico de meta tags SEO e redes sociais.
 * Atualiza title, description, keywords, Open Graph e Twitter Cards no <head>.
 */
export function useMeta(meta: {
  title: string
  description: string
  keywords?: string
  ogType?: string
  ogImage?: string
  twitterCard?: string
}) {
  const siteName = 'Blink'
  const fullTitle = `${meta.title} | ${siteName}`
  const defaultImage = '/storage/og-image.png'

  // Atualiza o <title>
  document.title = fullTitle

  // Helper para criar/atualizar meta tags
  function setMetaTag(property: string, content: string, isName = false) {
    const attr = isName ? 'name' : 'property'
    let tag = document.querySelector(`meta[${attr}="${property}"]`)

    if (!tag) {
      tag = document.createElement('meta')
      tag.setAttribute(attr, property)
      document.head.appendChild(tag)
    }

    tag.setAttribute('content', content)
  }

  // Meta tags padrão
  setMetaTag('description', meta.description, true)
  if (meta.keywords) {
    setMetaTag('keywords', meta.keywords, true)
  }
  setMetaTag('robots', 'index, follow', true)

  // Open Graph
  setMetaTag('og:title', fullTitle)
  setMetaTag('og:description', meta.description)
  setMetaTag('og:type', meta.ogType || 'website')
  setMetaTag('og:url', window.location.href)
  setMetaTag('og:image', meta.ogImage || defaultImage)

  // Twitter Cards
  setMetaTag('twitter:card', meta.twitterCard || 'summary_large_image', true)
  setMetaTag('twitter:title', fullTitle, true)
  setMetaTag('twitter:description', meta.description, true)
  setMetaTag('twitter:image', meta.ogImage || defaultImage, true)
}
