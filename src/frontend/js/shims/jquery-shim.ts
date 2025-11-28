/**
 * jQuery shim for Vite builds.
 *
 * This exports the global jQuery that is loaded synchronously via script tag.
 * This allows us to use `import $ from 'jquery'` syntax while using the global.
 */

// Use global jQuery that's loaded before Vite bundle
const $ = window.jQuery;
export default $;
export { $ as jQuery };
