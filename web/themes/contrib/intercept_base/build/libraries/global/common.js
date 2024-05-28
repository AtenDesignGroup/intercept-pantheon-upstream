!function (error) {
  console.error(error);
  if (typeof document === 'undefined') {
    return;
  } else if (!document.body) {
    document.addEventListener('DOMContentLoaded', print);
  } else {
    print();
  }
  function print() {
    var pre = document.createElement('pre');
    pre.className = 'errorify';
    pre.textContent = error.message || error;
    if (document.body.firstChild) {
      document.body.insertBefore(pre, document.body.firstChild);
    } else {
      document.body.appendChild(pre);
    }
  }
}({"message":"/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/libraries/views_autosubmit/views_autosubmit.js: Unexpected token (26:15) while parsing file: /Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/libraries/views_autosubmit/views_autosubmit.js\n\n  24 |       return;\n  25 |     }\n> 26 |     if (Drupal?.behaviors?.ViewsAutoSubmitRefocus) {\n     |                ^\n  27 |       overrideStoreFocusedElement();\n  28 |     } else {\n  29 |       attempts++;","name":"SyntaxError","stack":"SyntaxError: /Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/libraries/views_autosubmit/views_autosubmit.js: Unexpected token (26:15)\n  24 |       return;\n  25 |     }\n> 26 |     if (Drupal?.behaviors?.ViewsAutoSubmitRefocus) {\n     |                ^\n  27 |       overrideStoreFocusedElement();\n  28 |     } else {\n  29 |       attempts++;\n    at Parser.pp$5.raise (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:4454:13)\n    at Parser.pp.unexpected (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:1761:8)\n    at Parser.pp$3.parseExprAtom (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:3750:12)\n    at Parser.parseExprAtom (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:7238:22)\n    at Parser.pp$3.parseExprSubscripts (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:3494:19)\n    at Parser.pp$3.parseMaybeUnary (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:3474:19)\n    at Parser.pp$3.parseExprOps (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:3404:19)\n    at Parser.pp$3.parseMaybeConditional (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:3381:19)\n    at Parser.pp$3.parseMaybeAssign (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:3344:19)\n    at Parser.parseMaybeAssign (/Users/mjarrell/Sites/richland-site/web/themes/contrib/intercept_base/node_modules/babylon/lib/index.js:6474:20)"})