wpJsonpIntercept([10],{0:function(a,b){a.exports=React},1188:function(a,b,c){"use strict";function d(a){var b=a.getAttribute("data-event-uuid");Object(i.render)(h.a.createElement(q,{eventId:b,user:r}),a)}Object.defineProperty(b,"__esModule",{value:!0});var e=c(135),f=c.n(e),g=c(0),h=c.n(g),i=c(21),j=c.n(i),k=c(89),l=c.n(k),m=c(36),n=c.n(m),o=c(79),p=c(1189),q=Object(o.a)(p.a),r=n.a.intercept.user;l.a.behaviors.eventRegisterButtonApp={attach:function a(b){var c=f()(b.getElementsByClassName("js--event-register-button"));c.map(d)}}},1189:function(a,b,d){"use strict";function e(a){var b=f();return function c(){var d,e=x()(a);if(b){var f=x()(this).constructor;d=Reflect.construct(e,arguments,f)}else d=e.apply(this,arguments);return v()(this,d)}}function f(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],function(){})),!0}catch(a){return!1}}var g=d(7),h=d.n(g),i=d(6),j=d.n(i),k=d(10),l=d.n(k),m=d(33),n=d.n(m),o=d(8),p=d.n(o),q=d(9),r=d.n(q),s=d(11),t=d.n(s),u=d(12),v=d.n(u),w=d(13),x=d.n(w),y=d(0),z=d.n(y),A=d(1),B=d.n(A),C=d(19),D=d(14),E=d.n(D),F=d(209),G=d(210),H=E.a.api,I=E.a.select,J=E.a.constants,c=function(a){function b(){return p()(this,b),c.apply(this,arguments)}t()(b,a);var c=e(b);return r()(b,[{key:"componentDidMount",value:function a(){this.props.fetchEvent(this.props.eventId),this.props.fetchRegistration(this.props.eventId,this.props.user)}},{key:"render",value:function a(){return z.a.createElement("div",{className:"event-register-button__inner"},this.props.event&&z.a.createElement(F.a,n()({},this.props,{event:this.props.event.data})),this.props.event&&z.a.createElement(G.a,n()({},this.props,{event:this.props.event.data})))}}]),b}(z.a.Component);c.propTypes={event:B.a.object,eventId:B.a.string.isRequired,registrations:B.a.array,fetchEvent:B.a.func.isRequired,fetchRegistration:B.a.func.isRequired,user:B.a.object},c.defaultProps={event:null,registrations:[]};var K=function a(b,c){return{event:I.record(I.getIdentifier(J.TYPE_EVENT,c.eventId))(b),registrations:I.eventRegistrationsByEventByUser(c.eventId,c.user.uuid)(b)}},L=function a(b){return{fetchEvent:function a(c){b(H[J.TYPE_EVENT].fetchResource(c))},fetchRegistration:function a(c,d){b(H[J.TYPE_EVENT_REGISTRATION].fetchAll({filters:{uuid:{value:c,path:"field_event.id"},user:{value:d.uuid,path:"field_user.id"}}}))}}};b.a=Object(C.b)(K,L)(c)},14:function(a,b){a.exports=interceptClient},21:function(a,b){a.exports=ReactDOM},36:function(a,b){a.exports=drupalSettings},80:function(a,b){a.exports=interceptTheme},89:function(a,b){a.exports=Drupal}},[1188]);