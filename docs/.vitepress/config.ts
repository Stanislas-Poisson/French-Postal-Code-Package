import { existsSync, readFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig } from 'vitepress'

const here = dirname(fileURLToPath(import.meta.url))
const generated = join(here, '..', 'reference', 'sidebar.json')
const reference = existsSync(generated) ? JSON.parse(readFileSync(generated, 'utf8')) : []

const ci = process.env

// What built this site: shown at the bottom of every page.
const build = {
  version: ci.DOCS_VERSION ?? 'next',
  ref: ci.CI_COMMIT_REF_NAME ?? 'local',
  commit: ci.CI_COMMIT_SHORT_SHA ?? '',
  commitUrl: ci.CI_PROJECT_URL && ci.CI_COMMIT_SHA ? `${ci.CI_PROJECT_URL}/commit/${ci.CI_COMMIT_SHA}` : '',
  pipeline: ci.CI_PIPELINE_ID ?? '',
  pipelineUrl: ci.CI_PIPELINE_URL ?? '',
  date: ci.CI_COMMIT_TIMESTAMP ?? new Date().toISOString(),
}

const project = 'https://github.com/Stanislas-Poisson/French-Postal-Code-Package'

// GitHub Pages serves a repository under /Repository/. The versions live in folders of it:
// DOCS_BASE is the folder of this build (/French-Postal-Code-Package/1.0.0/) and DOCS_ROOT the root of the site (/French-Postal-Code-Package/).
export default defineConfig({
  title: 'French Postal Code',
  description: 'The regions, departments, communes and postal codes of France, with one GPS point per postal code.',
  base: process.env.DOCS_BASE ?? '/',
  cleanUrls: true,
  lastUpdated: true,
  themeConfig: {
    root: process.env.DOCS_ROOT ?? '/',
    build,
    badges: [
      { label: 'ci', image: `${project}/actions/workflows/ci.yml/badge.svg?branch=main`, href: `${project}/actions/workflows/ci.yml` },
      { label: 'Packagist', image: 'https://img.shields.io/packagist/v/stanislas-poisson/french-postal-code', href: 'https://packagist.org/packages/stanislas-poisson/french-postal-code' },
      { label: 'Release', image: 'https://img.shields.io/github/v/release/Stanislas-Poisson/French-Postal-Code-Package', href: `${project}/releases` },
      { label: 'License', image: 'https://img.shields.io/badge/license-MIT-blue.svg', href: `${project}/blob/main/LICENSE` },
      { label: 'PHP', image: 'https://img.shields.io/badge/php-%3E%3D8.3-777bb4.svg?logo=php&logoColor=white', href: 'https://www.php.net' },
    ],
    nav: [
      { text: 'Guide', link: '/guide/readme' },
      { text: 'Reference', link: '/reference/' },
      { text: 'GitHub', link: project },
    ],
    sidebar: [
      { text: 'Guide', items: [{ text: 'Install and use', link: '/guide/readme' }] },
      { text: 'Reference', link: '/reference/', items: reference },
    ],
    search: { provider: 'local' },
    outline: [2, 3],
  },
})
