!function(e){function t(t){for(var n,s,u=t[0],a=t[1],c=t[2],p=0,f=[];p<u.length;p++)s=u[p],Object.prototype.hasOwnProperty.call(i,s)&&i[s]&&f.push(i[s][0]),i[s]=0;for(n in a)Object.prototype.hasOwnProperty.call(a,n)&&(e[n]=a[n]);for(l&&l(t);f.length;)f.shift()();return o.push.apply(o,c||[]),r()}function r(){for(var e,t=0;t<o.length;t++){for(var r=o[t],n=!0,u=1;u<r.length;u++){var a=r[u];0!==i[a]&&(n=!1)}n&&(o.splice(t--,1),e=s(s.s=r[0]))}return e}var n={},i={8:0},o=[];function s(t){if(n[t])return n[t].exports;var r=n[t]={i:t,l:!1,exports:{}};return e[t].call(r.exports,r,r.exports,s),r.l=!0,r.exports}s.m=e,s.c=n,s.d=function(e,t,r){s.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:r})},s.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},s.t=function(e,t){if(1&t&&(e=s(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var r=Object.create(null);if(s.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var n in e)s.d(r,n,function(t){return e[t]}.bind(null,n));return r},s.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return s.d(t,"a",t),t},s.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},s.p="";var u=window.wpJsonpIntercept=window.wpJsonpIntercept||[],a=u.push.bind(u);u.push=t,u=u.slice();for(var c=0;c<u.length;c++)t(u[c]);var l=a;o.push([873,0]),r()}({0:function(e,t){e.exports=React},116:function(e,t){e.exports=interceptTheme},144:function(e,t,r){"use strict";var n=r(0),i=r.n(n),o=r(1),s=r.n(o),u=r(18),a=r(6),c=r.n(a),l=r(387),p=c.a.select,f=c.a.utils.getUserUuid();function d(e){var t=e.onClick,r=e.mustRegister,n=e.registerUrl,o=e.text,s=e.registrationAllowed;return r?i.a.createElement(l.a,{href:t?null:n,variant:"register"===o?"contained":"outlined",size:"small",color:"primary",className:"action-button__button",disabled:!s,onClick:t},"Cancel"===o&&n?"View Registration":o):null}d.propTypes={eventId:s.a.string.isRequired,userId:s.a.string,onClick:s.a.func,mustRegister:s.a.bool,registrationAllowed:s.a.bool,registerUrl:s.a.string,text:s.a.string},d.defaultProps={onClick:null,userId:f,mustRegister:!1,registrationAllowed:!1,registerUrl:null,text:""};t.a=Object(u.b)((function(e,t){var r=t.eventId,n=p.mustRegisterForEvent(r)(e),i=p.registerUrl(r)(e),o=t.userId||f;return{mustRegister:n,registerUrl:i,text:p.registrationButtonText(r,o)(e),registrationAllowed:p.registrationAllowed(r,o)(e)}}))(d)},145:function(e,t,r){"use strict";var n=r(0),i=r.n(n),o=r(1),s=r.n(o),u=r(18),a=r(6),c=r.n(a),l=c.a.select,p=c.a.utils.getUserUuid();function f(e){var t=e.text;return t?i.a.createElement("p",{className:"action-button__message"},t):null}f.propTypes={eventId:s.a.string.isRequired,userId:s.a.string,text:s.a.string},f.defaultProps={text:null,userId:p};t.a=Object(u.b)((function(e,t){var r=t.eventId,n=t.userId;return{text:l.registrationStatusText(r,n||p)(e)}}))(f)},16:function(e,t){e.exports=moment},25:function(e,t){e.exports=ReactDOM},28:function(e,t){e.exports=drupalSettings},6:function(e,t){e.exports=interceptClient},77:function(e,t){e.exports=Drupal},873:function(e,t,r){"use strict";r.r(t);r(32);var n=r(62),i=r.n(n),o=r(0),s=r.n(o),u=r(25),a=r(77),c=r.n(a),l=r(28),p=r.n(l),f=r(67),d=(r(40),r(33),r(38),r(42),r(43),r(37),r(19),r(21),r(20),r(39),r(23)),v=r.n(d),g=r(2),h=r.n(g),b=r(10),y=r.n(b),m=r(9),O=r.n(m),j=r(11),R=r.n(j),E=r(12),x=r.n(E),P=r(5),w=r.n(P),I=r(1),_=r.n(I),T=r(18),S=r(6),U=r.n(S),A=r(16),D=r.n(A),C=r(144),N=r(145);function k(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function M(e){var t=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],(function(){}))),!0}catch(e){return!1}}();return function(){var r,n=w()(e);if(t){var i=w()(this).constructor;r=Reflect.construct(n,arguments,i)}else r=n.apply(this,arguments);return x()(this,r)}}var q=U.a.api,B=U.a.select,V=U.a.utils,z=U.a.constants,Y=function(e){R()(r,e);var t=M(r);function r(){return y()(this,r),t.apply(this,arguments)}return O()(r,[{key:"componentDidMount",value:function(){this.props.fetchEvent(this.props.eventId),this.props.fetchRegistration(this.props.eventId,this.props.user)}},{key:"render",value:function(){return console.log(function(e){for(var t=1;t<arguments.length;t++){var r=null!=arguments[t]?arguments[t]:{};t%2?k(Object(r),!0).forEach((function(t){h()(e,t,r[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(r)):k(Object(r)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(r,t))}))}return e}({},this.props)),s.a.createElement("div",{className:"event-register-button__inner"},this.props.event&&s.a.createElement(C.a,v()({},this.props,{event:this.props.event.data})),this.props.event&&s.a.createElement(N.a,v()({},this.props,{event:this.props.event.data})))}}]),r}(s.a.Component);Y.propTypes={event:_.a.object,eventId:_.a.string.isRequired,registrations:_.a.array,fetchEvent:_.a.func.isRequired,fetchRegistration:_.a.func.isRequired,user:_.a.object},Y.defaultProps={event:null,registrations:[]};var J=Object(T.b)((function(e,t){return{event:B.record(B.getIdentifier(z.TYPE_EVENT,t.eventId))(e),registrations:B.eventRegistrationsByEventByUser(t.eventId,t.user.uuid)(e)}}),(function(e){return{fetchEvent:function(t){e(q[z.TYPE_EVENT].fetchResource(t))},fetchRegistration:function(t,r){e(q[z.TYPE_EVENT_REGISTRATION].fetchAll({filters:{date:{value:D()().tz(V.getUserTimezone()).startOf("day").format(),path:"field_event.field_date_time.value",operator:">="},user:{value:r.uuid,path:"field_user.id"}}}))}}}))(Y),F=Object(f.a)(J),G=p.a.intercept.user;function H(e){var t=e.getAttribute("data-event-uuid");Object(u.render)(s.a.createElement(F,{eventId:t,user:G}),e)}c.a.behaviors.eventRegisterButtonApp={attach:function(e){i()(e.getElementsByClassName("js--event-register-button")).map(H)}}}});