!function(e){function t(t){for(var r,s,a=t[0],u=t[1],c=t[2],p=0,f=[];p<a.length;p++)s=a[p],Object.prototype.hasOwnProperty.call(i,s)&&i[s]&&f.push(i[s][0]),i[s]=0;for(r in u)Object.prototype.hasOwnProperty.call(u,r)&&(e[r]=u[r]);for(l&&l(t);f.length;)f.shift()();return o.push.apply(o,c||[]),n()}function n(){for(var e,t=0;t<o.length;t++){for(var n=o[t],r=!0,a=1;a<n.length;a++){var u=n[a];0!==i[u]&&(r=!1)}r&&(o.splice(t--,1),e=s(s.s=n[0]))}return e}var r={},i={8:0},o=[];function s(t){if(r[t])return r[t].exports;var n=r[t]={i:t,l:!1,exports:{}};return e[t].call(n.exports,n,n.exports,s),n.l=!0,n.exports}s.m=e,s.c=r,s.d=function(e,t,n){s.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},s.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},s.t=function(e,t){if(1&t&&(e=s(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(s.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var r in e)s.d(n,r,function(t){return e[t]}.bind(null,r));return n},s.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return s.d(t,"a",t),t},s.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},s.p="";var a=window.wpJsonpIntercept=window.wpJsonpIntercept||[],u=a.push.bind(a);a.push=t,a=a.slice();for(var c=0;c<a.length;c++)t(a[c]);var l=u;o.push([879,0]),n()}({0:function(e,t){e.exports=React},123:function(e,t){e.exports=interceptTheme},140:function(e,t,n){"use strict";var r=n(0),i=n.n(r),o=n(1),s=n.n(o),a=n(18),u=n(6),c=n.n(u),l=n(226),p=c.a.select,f=c.a.utils.getUserUuid();function d(e){var t=e.onClick,n=e.mustRegister,r=e.registerUrl,o=e.text,s=e.registrationAllowed;return n?i.a.createElement(l.a,{href:t?null:r,variant:"register"===o?"contained":"outlined",size:"small",color:"primary",className:"action-button__button",disabled:!s,onClick:t},"Cancel"===o&&r?"View Registration":o):null}d.propTypes={eventId:s.a.string.isRequired,userId:s.a.string,onClick:s.a.func,mustRegister:s.a.bool,registrationAllowed:s.a.bool,registerUrl:s.a.string,text:s.a.string},d.defaultProps={onClick:null,userId:f,mustRegister:!1,registrationAllowed:!1,registerUrl:null,text:""};t.a=Object(a.b)((function(e,t){var n=t.eventId,r=p.mustRegisterForEvent(n)(e),i=p.registerUrl(n)(e),o=t.userId||f;return{mustRegister:r,registerUrl:i,text:p.registrationButtonText(n,o)(e),registrationAllowed:p.registrationAllowed(n,o)(e)}}))(d)},141:function(e,t,n){"use strict";var r=n(0),i=n.n(r),o=n(1),s=n.n(o),a=n(18),u=n(6),c=n.n(u),l=c.a.select,p=c.a.utils.getUserUuid();function f(e){var t=e.text;return t?i.a.createElement("p",{className:"action-button__message"},t):null}f.propTypes={eventId:s.a.string.isRequired,userId:s.a.string,text:s.a.string},f.defaultProps={text:null,userId:p};t.a=Object(a.b)((function(e,t){var n=t.eventId,r=t.userId;return{text:l.registrationStatusText(n,r||p)(e)}}))(f)},16:function(e,t){e.exports=moment},25:function(e,t){e.exports=ReactDOM},28:function(e,t){e.exports=drupalSettings},51:function(e,t){e.exports=Drupal},6:function(e,t){e.exports=interceptClient},879:function(e,t,n){"use strict";n.r(t);n(31);var r=n(62),i=n.n(r),o=n(0),s=n.n(o),a=n(25),u=n(51),c=n.n(u),l=n(28),p=n.n(l),f=n(71),d=(n(19),n(21),n(20),n(23)),v=n.n(d),g=n(10),h=n.n(g),b=n(9),m=n.n(b),y=n(11),R=n.n(y),x=n(12),E=n.n(x),I=n(5),_=n.n(I),O=n(1),j=n.n(O),T=n(18),w=n(6),P=n.n(w),U=n(16),A=n.n(U),S=n(140),C=n(141);function N(e){var t=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],(function(){}))),!0}catch(e){return!1}}();return function(){var n,r=_()(e);if(t){var i=_()(this).constructor;n=Reflect.construct(r,arguments,i)}else n=r.apply(this,arguments);return E()(this,n)}}var k=P.a.api,M=P.a.select,q=P.a.utils,B=P.a.constants,D=function(e){R()(n,e);var t=N(n);function n(){return h()(this,n),t.apply(this,arguments)}return m()(n,[{key:"componentDidMount",value:function(){this.props.fetchEvent(this.props.eventId),this.props.fetchRegistration(this.props.eventId,this.props.user)}},{key:"render",value:function(){return s.a.createElement("div",{className:"event-register-button__inner"},this.props.event&&s.a.createElement(S.a,v()({},this.props,{event:this.props.event.data})),this.props.event&&s.a.createElement(C.a,v()({},this.props,{event:this.props.event.data})))}}]),n}(s.a.Component);D.propTypes={event:j.a.object,eventId:j.a.string.isRequired,registrations:j.a.array,fetchEvent:j.a.func.isRequired,fetchRegistration:j.a.func.isRequired,user:j.a.object},D.defaultProps={event:null,registrations:[]};var V=Object(T.b)((function(e,t){return{event:M.record(M.getIdentifier(B.TYPE_EVENT,t.eventId))(e),registrations:M.eventRegistrationsByEventByUser(t.eventId,t.user.uuid)(e)}}),(function(e){return{fetchEvent:function(t){e(k[B.TYPE_EVENT].fetchResource(t))},fetchRegistration:function(t,n){e(k[B.TYPE_EVENT_REGISTRATION].fetchAll({filters:{date:{value:A()().tz(q.getUserTimezone()).startOf("day").format(),path:"field_event.field_date_time.value",operator:">="},user:{value:n.uuid,path:"field_user.id"}}}))}}}))(D),z=Object(f.a)(V),Y=p.a.intercept.user;function J(e){var t=e.getAttribute("data-event-uuid");Object(a.render)(s.a.createElement(z,{eventId:t,user:Y}),e)}c.a.behaviors.eventRegisterButtonApp={attach:function(e){i()(e.getElementsByClassName("js--event-register-button")).map(J)}}}});