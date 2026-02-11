const postcssPresetEnv = require('postcss-preset-env');
const postcssInlineSvg = require('postcss-inline-svg');
const pxtorem = require('postcss-pxtorem');
const postcssSvgo = require('postcss-svgo');

// Encode SVG files.
function encode(code) {
  return code
    .replace(/\%/g, '%25')
    .replace(/\</g, '%3C')
    .replace(/\>/g, '%3E')
    .replace(/\s/g, '%20')
    .replace(/\!/g, '%21')
    .replace(/\*/g, '%2A')
    .replace(/\'/g, '%27')
    .replace(/\"/g, '%22')
    .replace(/\(/g, '%28')
    .replace(/\)/g, '%29')
    .replace(/\;/g, '%3B')
    .replace(/\:/g, '%3A')
    .replace(/\@/g, '%40')
    .replace(/\&/g, '%26')
    .replace(/\=/g, '%3D')
    .replace(/\+/g, '%2B')
    .replace(/\$/g, '%24')
    .replace(/\,/g, '%2C')
    .replace(/\//g, '%2F')
    .replace(/\?/g, '%3F')
    .replace(/\#/g, '%23')
    .replace(/\[/g, '%5B')
    .replace(/\]/g, '%5D');
}

module.exports = (ctx) => ({
  plugins: [
    postcssInlineSvg({
      paths: ['./images'],
      encode,
    }),
    postcssSvgo(),
    pxtorem({
      propList: ['--font*', 'font', 'font*'],
    }),
    postcssPresetEnv({
      stage: 1,
      features: {
        // Custom properties get poyfilled for IE so no need to process them.
        'custom-properties': false,
      },
    }),
  ],
});
