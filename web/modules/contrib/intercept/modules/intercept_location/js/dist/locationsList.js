!function(t){function e(e){for(var r,u,a=e[0],i=e[1],l=e[2],p=0,s=[];p<a.length;p++)u=a[p],Object.prototype.hasOwnProperty.call(o,u)&&o[u]&&s.push(o[u][0]),o[u]=0;for(r in i)Object.prototype.hasOwnProperty.call(i,r)&&(t[r]=i[r]);for(f&&f(e);s.length;)s.shift()();return c.push.apply(c,l||[]),n()}function n(){for(var t,e=0;e<c.length;e++){for(var n=c[e],r=!0,a=1;a<n.length;a++){var i=n[a];0!==o[i]&&(r=!1)}r&&(c.splice(e--,1),t=u(u.s=n[0]))}return t}var r={},o={10:0},c=[];function u(e){if(r[e])return r[e].exports;var n=r[e]={i:e,l:!1,exports:{}};return t[e].call(n.exports,n,n.exports,u),n.l=!0,n.exports}u.m=t,u.c=r,u.d=function(t,e,n){u.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},u.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},u.t=function(t,e){if(1&e&&(t=u(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(u.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var r in t)u.d(n,r,function(e){return t[e]}.bind(null,r));return n},u.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return u.d(e,"a",e),e},u.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},u.p="";var a=window.wpJsonpIntercept=window.wpJsonpIntercept||[],i=a.push.bind(a);a.push=e,a=a.slice();for(var l=0;l<a.length;l++)e(a[l]);var f=i;c.push([877,0]),n()}({0:function(t,e){t.exports=React},115:function(t,e){t.exports=interceptTheme},25:function(t,e){t.exports=ReactDOM},6:function(t,e){t.exports=interceptClient},877:function(t,e,n){"use strict";n.r(e);var r=n(0),o=n.n(r),c=n(25),u=n(68),a=(n(29),n(37),n(19),n(21),n(20),n(10)),i=n.n(a),l=n(9),f=n.n(l),p=n(3),s=n.n(p),d=n(11),y=n.n(d),h=n(12),v=n.n(h),b=n(5),m=n.n(b),O=n(2),g=n.n(O),j=n(1),w=n.n(j),x=n(15),R=n(55),P=n.n(R);function S(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],(function(){}))),!0}catch(t){return!1}}();return function(){var n,r=m()(t);if(e){var o=m()(this).constructor;n=Reflect.construct(r,arguments,o)}else n=r.apply(this,arguments);return v()(this,n)}}var _=function(t){y()(n,t);var e=S(n);function n(){var t;i()(this,n);for(var r=arguments.length,o=new Array(r),c=0;c<r;c++)o[c]=arguments[c];return t=e.call.apply(e,[this].concat(o)),g()(s()(t),"state",{}),t}return f()(n,[{key:"render",value:function(){var t=this.props.locations,e=Object.keys(t).length>0?P()(t,(function(t,e){return o.a.createElement("p",{key:e},t.data.title)})):o.a.createElement("p",null,"No locations have been loaded.");return o.a.createElement("div",{className:"locationList"},e)}}]),n}(o.a.Component);_.propTypes={locations:w.a.object.isRequired};var E=Object(x.a)((function(t){return{}}),{withTheme:!0})(_),M=Object(u.a)(E);Object(c.render)(o.a.createElement(M,null),document.getElementById("locationsListRoot"))}});