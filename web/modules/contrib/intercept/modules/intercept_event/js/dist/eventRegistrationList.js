wpJsonpIntercept([6],{0:function(a,b){a.exports=React},1054:function(a,b,c){"use strict";Object.defineProperty(b,"__esModule",{value:!0});var d=c(0),e=c.n(d),f=c(14),g=c.n(f),h=c(82),i=c(1055),j=Object(h.a)(i.a);Object(f.render)(e.a.createElement(j,null),document.getElementById("eventRegistrationRoot"))},1055:function(a,b,c){"use strict";var d=c(1056),e=c(1057);b.a=Object(d.a)(e.a)},1056:function(a,b,c){"use strict";var d=c(361),e=c.n(d),f=c(13),g=c.n(f),h=c(307),i={view:{type:d.UrlQueryParamTypes.string},showSaves:{type:d.UrlQueryParamTypes.boolean},showRegistrations:{type:d.UrlQueryParamTypes.boolean}},j=function a(b){return Object(h.a)(Object(d.addUrlProps)({urlPropsQueryConfig:i})(b))};b.a=j},1057:function(a,b,d){"use strict";function e(){var a=0<arguments.length&&arguments[0]!==void 0?arguments[0]:"upcoming",b=1<arguments.length?arguments[1]:void 0,c="past"===a?"<":">",d=B()(new Date).toISOString();return{date:{path:b,value:d,operator:c}}}var f=d(19),g=d.n(f),h=d(4),i=d.n(h),j=d(5),k=d.n(j),l=d(8),m=d.n(l),n=d(9),o=d.n(n),p=d(10),q=d.n(p),r=d(15),s=d.n(r),t=d(12),u=d.n(t),v=d(0),w=d.n(v),x=d(1),y=d.n(x),z=d(16),A=d(22),B=d.n(A),C=d(44),D=d.n(C),E=d(53),F=d.n(E),G=d(115),H=d.n(G),I=d(13),J=d.n(I),K=d(203),L=d(212),M=d(109),N=d(238),O=d(628),P=J.a.constants,Q=J.a.api,R=J.a.select,S=P,c=D.a.intercept.parameters.user.uuid,T=[{key:"past",value:"Past"},{key:"upcoming",value:"Upcoming"}],U=function(a){function b(a){var c;return i()(this,b),c=m()(this,o()(b).call(this,a)),u()(s()(s()(c)),"handleViewChange",function(a){c.props.onChangeView(a),c.doFetch(a)}),c.state={open:!1},c.handleViewChange=c.handleViewChange.bind(s()(s()(c))),c.doFetch=F()(c.doFetch,300).bind(s()(s()(c))),c.doFetchRegistrations=c.doFetchRegistrations.bind(s()(s()(c))),c.doFetchSavedEvents=c.doFetchSavedEvents.bind(s()(s()(c))),c}return q()(b,a),k()(b,[{key:"componentDidMount",value:function a(){this.props.fetchAudiences({fields:u()({},S.TYPE_AUDIENCE,["name"])}),this.doFetch(this.props.view)}},{key:"doFetchRegistrations",value:function a(b){this.props.fetchRegistrations({filters:g()({user:{path:"field_user.id",value:c}},e(b,"field_event.field_date_time.end_value")),include:["field_event","field_event.image_primary","field_event.image_primary.field_media_image","field_event.field_location"],headers:{"X-Consumer-ID":J.a.consumer}})}},{key:"doFetchSavedEvents",value:function a(b){this.props.fetchSavedEvents({filters:{user:{path:"uid.uid",value:c}},include:["flagged_entity","flagged_entity.image_primary","flagged_entity.image_primary.field_media_image","flagged_entity.field_location"],headers:{"X-Consumer-ID":J.a.consumer}})}},{key:"doFetch",value:function a(b){this.props.showSaves&&this.doFetchSavedEvents(b),this.props.showRegistrations&&this.doFetchRegistrations(b)}},{key:"doConfirmAction",value:function a(){this.setState({open:!0,text:"Confirm cancel"})}},{key:"render",value:function a(){var b=this.props,c=this.handleViewChange,d=b.events,e=b.view,f=b.isLoading,g=function a(b){return b.map(function(a){return{key:a.data.id,node:w.a.createElement(L.a,{id:a.data.id,className:"event-teaser"})}})},h=0<d.length?w.a.createElement(K.a,{heading:null,items:g(d)}):f?w.a.createElement(M.a,{loading:f}):w.a.createElement("p",null,"No events available.");return w.a.createElement("div",{className:"l--main"},w.a.createElement("div",{className:"l--subsection"},w.a.createElement(N.a,{options:T,value:e,handleChange:c})),w.a.createElement("div",{className:"l--subsection"},h))}}]),b}(v.Component);U.propTypes={events:y.a.array,onChangeView:y.a.func.isRequired,fetchAudiences:y.a.func.isRequired,fetchRegistrations:y.a.func.isRequired,fetchSavedEvents:y.a.func.isRequired,view:y.a.string,showSaves:y.a.bool,showRegistrations:y.a.bool},U.defaultProps={events:[],view:"upcoming",showSaves:!0,showRegistrations:!0};var V=function a(b,d){var e="past"===d.view?"usersPastEvents":"usersUpcomingEvents";return!1===d.showSaves&&(e="past"===d.view?"usersPastRegisteredEvents":"usersUpcomingRegisteredEvents"),!1===d.showRegistrations&&(e="past"===d.view?"usersPastSavedEvents":"usersUpcomingSavedEvents"),{events:R[e](c)(b),registrations:R.eventRegistrations(b),isLoading:R.recordsAreLoading(S.TYPE_EVENT_REGISTRATION)(b)||R.recordsAreLoading(S.TYPE_SAVED_EVENT)(b)}},W=function a(b,c){return{fetchAudiences:function a(c){b(Q[S.TYPE_AUDIENCE].fetchAll(c))},fetchRegistrations:function a(c){b(Q[S.TYPE_EVENT_REGISTRATION].fetchAll(c))},fetchSavedEvents:function a(c){b(Q[S.TYPE_SAVED_EVENT].fetchAll(c))}}};b.a=Object(z.b)(V,W)(U)},13:function(a,b){a.exports=interceptClient},14:function(a,b){a.exports=ReactDOM},212:function(a,b,d){"use strict";var e=d(226),f=d.n(e),g=d(4),h=d.n(g),i=d(5),j=d.n(i),k=d(8),l=d.n(k),m=d(9),n=d.n(m),o=d(10),p=d.n(o),q=d(0),r=d.n(q),s=d(1),t=d.n(s),u=d(16),v=d(22),w=d.n(v),x=d(20),y=d.n(x),z=d(13),A=d.n(z),B=d(166),C=d(213),D=d(214),E=d(215),F=A.a.select,G=A.a.constants,H=A.a.utils,I=G,c=H.getUserUuid(),J=function(a){function b(){return h()(this,b),l()(this,n()(b).apply(this,arguments))}return p()(b,a),j()(b,[{key:"render",value:function a(){var b=this.props,c=b.id,d=b.event,e=b.registrations,f=function a(b){return{id:b.id,name:y()(b,"attributes.name")}},g=w()(H.dateFromDrupal(d.attributes.field_date_time.value)),h=Array.isArray(d.relationships.field_event_audience)?d.relationships.field_event_audience.map(f).filter(function(a){return a.id}):[],i=0<h.length?r.a.createElement(B.a,{label:"Audience",key:"audience",values:h}):null,j=y()(d,"attributes.event_thumbnail");return r.a.createElement(C.a,{key:c,modifiers:[j?"with-image":"without-image"],image:j,supertitle:y()(d,"relationships.field_location.attributes.title"),title:d.attributes.title,titleUrl:d.attributes.path?d.attributes.path.alias:"/node/".concat(d.attributes.nid),date:{month:g.utcOffset(H.getUserUtcOffset()).format("MMM"),date:g.utcOffset(H.getUserUtcOffset()).format("D"),time:H.getTimeDisplay(g).replace(":00","")},description:y()(d,"attributes.field_text_teaser.value"),tags:[i],registrations:e,footer:function a(b){return r.a.createElement(r.a.Fragment,null,r.a.createElement(D.a,{eventId:b.event.id}),r.a.createElement(E.a,{eventId:b.event.id}))},event:d})}}]),b}(q.PureComponent);J.propTypes={id:t.a.string.isRequired,event:t.a.object.isRequired,registrations:t.a.array},J.defaultProps={registrations:[]};var K=function a(b,d){var e=F.getIdentifier(I.TYPE_EVENT,d.id),f=F.eventRegistrationsByEventByUser(d.id,c)(b);return{event:F.bundle(e)(b),registrations:f}};b.a=Object(u.b)(K)(J)},22:function(a,b){a.exports=moment},238:function(a,b,c){"use strict";var d=c(4),e=c.n(d),f=c(5),g=c.n(f),h=c(8),i=c.n(h),j=c(9),k=c.n(j),l=c(10),m=c.n(l),n=c(0),o=c.n(n),p=c(1),q=c.n(p),r=c(24),s=c.n(r),t=function a(b){return{checked:{fontWeight:"bold"},unChecked:{fontWeight:"bold",color:b.palette.secondary.main}}},u=function(a){function b(){return e()(this,b),i()(this,k()(b).apply(this,arguments))}return m()(b,a),g()(b,[{key:"render",value:function a(){var b=this.props,c=b.value,d=b.handleChange,e=b.options,f=function a(b){return"view-switcher__button ".concat(b&&"view-switcher__button--active")};return o.a.createElement("div",{className:"view-switcher"},e.map(function(a){return o.a.createElement("button",{key:a.key,className:f(c===a.key),disabled:c===a.key,onClick:function b(){return d(a.key)}},a.value)}))}}]),b}(o.a.PureComponent);u.propTypes={handleChange:q.a.func.isRequired,value:q.a.string,options:q.a.arrayOf(q.a.shape({key:q.a.string,value:q.a.string})).isRequired},u.defaultProps={value:"list"},b.a=Object(r.withStyles)(t)(u)},307:function(a,b,c){"use strict";function d(a){return a.displayName||a.name||"Component"}function e(a){var b=function(b){function c(){return g()(this,c),k()(this,m()(c).apply(this,arguments))}return o()(c,b),i()(c,[{key:"componentDidMount",value:function a(){var b=this;u.a.history.listen(function(){b.forceUpdate()})}},{key:"render",value:function b(){return s.a.createElement(a,this.props)}}]),c}(r.Component);return b.displayName="UpdateWithHistory(".concat(d(a),")"),b}var f=c(4),g=c.n(f),h=c(5),i=c.n(h),j=c(8),k=c.n(j),l=c(9),m=c.n(l),n=c(10),o=c.n(n),p=c(55),q=c.n(p),r=c(0),s=c.n(r),t=c(13),u=c.n(t);b.a=e},44:function(a,b){a.exports=drupalSettings},628:function(a,b,c){"use strict";var d=c(4),e=c.n(d),f=c(5),g=c.n(f),h=c(8),i=c.n(h),j=c(9),k=c.n(j),l=c(10),m=c.n(l),n=c(15),o=c.n(n),p=c(12),q=c.n(p),r=c(0),s=c.n(r),t=c(1),u=c.n(t),v=c(112),w=c.n(v),x=c(22),y=c.n(x),z=c(13),A=c.n(z),B=c(212),C=c(203),D=A.a.utils,E=function(a){function b(){var a,c;e()(this,b);for(var d=arguments.length,f=Array(d),g=0;g<d;g++)f[g]=arguments[g];return c=i()(this,(a=k()(b)).call.apply(a,[this].concat(f))),q()(o()(o()(c)),"state",{}),c}return m()(b,a),g()(b,[{key:"render",value:function a(){var b=this.props,c=b.events,d=b.loading,e=function a(b){return b.map(function(a){return{key:a,node:s.a.createElement(B.a,{id:a,className:"event-teaser"})}})},f=0<c.length?w()(c,function(a){return s.a.createElement(C.a,{heading:D.getDayDisplay(y.a.tz(a.date,D.getUserTimezone())),items:e(a.items),key:a.key})}):!d&&s.a.createElement("p",{key:0},"No events have been loaded.");return s.a.createElement("div",{className:"events-list"},f)}}]),b}(s.a.Component);E.propTypes={events:u.a.arrayOf(Object).isRequired,loading:u.a.bool},E.defaultProps={loading:!1},b.a=E},83:function(a,b){a.exports=interceptTheme}},[1054]);