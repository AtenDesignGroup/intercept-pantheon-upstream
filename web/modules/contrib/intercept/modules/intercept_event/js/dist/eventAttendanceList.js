!function(e){function t(t){for(var a,o,c=t[0],s=t[1],d=t[2],u=0,p=[];u<c.length;u++)o=c[u],Object.prototype.hasOwnProperty.call(r,o)&&r[o]&&p.push(r[o][0]),r[o]=0;for(a in s)Object.prototype.hasOwnProperty.call(s,a)&&(e[a]=s[a]);for(l&&l(t);p.length;)p.shift()();return i.push.apply(i,d||[]),n()}function n(){for(var e,t=0;t<i.length;t++){for(var n=i[t],a=!0,c=1;c<n.length;c++){var s=n[c];0!==r[s]&&(a=!1)}a&&(i.splice(t--,1),e=o(o.s=n[0]))}return e}var a={},r={3:0},i=[];function o(t){if(a[t])return a[t].exports;var n=a[t]={i:t,l:!1,exports:{}};return e[t].call(n.exports,n,n.exports,o),n.l=!0,n.exports}o.m=e,o.c=a,o.d=function(e,t,n){o.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:n})},o.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},o.t=function(e,t){if(1&t&&(e=o(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var n=Object.create(null);if(o.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var a in e)o.d(n,a,function(t){return e[t]}.bind(null,a));return n},o.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return o.d(t,"a",t),t},o.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},o.p="";var c=window.wpJsonpIntercept=window.wpJsonpIntercept||[],s=c.push.bind(c);c.push=t,c=c.slice();for(var d=0;d<c.length;d++)t(c[d]);var l=s;i.push([869,0]),n()}({0:function(e,t){e.exports=React},115:function(e,t){e.exports=interceptTheme},16:function(e,t){e.exports=moment},172:function(e,t,n){"use strict";var a=n(0),r=a.createContext();t.a=r},244:function(e,t,n){var a=n(14),r=n(245).values;a({target:"Object",stat:!0},{values:function(e){return r(e)}})},245:function(e,t,n){var a=n(48),r=n(167),i=n(103),o=n(180).f,c=function(e){return function(t){for(var n,c=i(t),s=r(c),d=s.length,l=0,u=[];d>l;)n=s[l++],a&&!o.call(c,n)||u.push(e?[n,c[n]]:c[n]);return u}};e.exports={entries:c(!0),values:c(!1)}},25:function(e,t){e.exports=ReactDOM},261:function(e,t,n){"use strict";n(29),n(33),n(121),n(32);var a=n(0),r=n.n(a),i=n(1),o=n.n(i),c=n(18),s=n(15),d=n(7),l=n.n(d),u=n(6),p=n.n(u),f=p.a.constants,v=p.a.select,h=f;function g(e){var t=e.tally;return r.a.createElement("p",null,t.map((function(e){return"".concat(e.count||0," ").concat(e.label)})).join(", "))}g.propTypes={tally:o.a.array},g.defaultProps={tally:[]};t.a=Object(c.b)((function(e,t){var n=v.getIdentifier(t.type,t.id),a=v.record(n)(e),r=v.records(h.TYPE_POPULATION_SEGMENT)(e);return{tally:l()(a,t.valuePath).filter((function(e){return l()(e,"meta.count")>0})).map((function(e){return{label:l()(r[e.id],"data.attributes.name"),count:l()(e,"meta.count")}}))}}))(Object(s.a)({card:{maxWidth:345},media:{height:0,paddingTop:"56.25%"}})(g))},28:function(e,t){e.exports=drupalSettings},382:function(e,t,n){var a=n(480),r=n(600),i=Object.prototype.hasOwnProperty,o=r((function(e,t,n){i.call(e,n)?e[n].push(t):a(e,n,[t])}));e.exports=o},6:function(e,t){e.exports=interceptClient},600:function(e,t,n){var a=n(601),r=n(602),i=n(153),o=n(113);e.exports=function(e,t){return function(n,c){var s=o(n)?a:r,d=t?t():{};return s(n,e,i(c,2),d)}}},601:function(e,t){e.exports=function(e,t,n,a){for(var r=-1,i=null==e?0:e.length;++r<i;){var o=e[r];t(a,o,n(o),e)}return a}},602:function(e,t,n){var a=n(344);e.exports=function(e,t,n,r){return a(e,(function(e,a,i){t(r,e,n(e),i)})),r}},639:function(e,t,n){"use strict";var a=n(13),r=n(4),i=n(0),o=(n(1),n(8)),c=n(15),s=n(172),d=i.forwardRef((function(e,t){var n=e.classes,c=e.className,d=e.component,l=void 0===d?"table":d,u=e.padding,p=void 0===u?"default":u,f=e.size,v=void 0===f?"medium":f,h=e.stickyHeader,g=void 0!==h&&h,m=Object(a.a)(e,["classes","className","component","padding","size","stickyHeader"]),b=i.useMemo((function(){return{padding:p,size:v,stickyHeader:g}}),[p,v,g]);return i.createElement(s.a.Provider,{value:b},i.createElement(l,Object(r.a)({role:"table"===l?null:"table",ref:t,className:Object(o.default)(n.root,c,g&&n.stickyHeader)},m)))}));t.a=Object(c.a)((function(e){return{root:{display:"table",width:"100%",borderCollapse:"collapse",borderSpacing:0,"& caption":Object(r.a)({},e.typography.body2,{padding:e.spacing(2),color:e.palette.text.secondary,textAlign:"left",captionSide:"bottom"})},stickyHeader:{borderCollapse:"separate"}}}),{name:"MuiTable"})(d)},640:function(e,t,n){"use strict";var a=n(4),r=n(13),i=n(0),o=(n(1),n(8)),c=n(15),s=n(92),d={variant:"head"},l=i.forwardRef((function(e,t){var n=e.classes,c=e.className,l=e.component,u=void 0===l?"thead":l,p=Object(r.a)(e,["classes","className","component"]);return i.createElement(s.a.Provider,{value:d},i.createElement(u,Object(a.a)({className:Object(o.default)(n.root,c),ref:t,role:"thead"===u?null:"rowgroup"},p)))}));t.a=Object(c.a)({root:{display:"table-header-group"}},{name:"MuiTableHead"})(l)},641:function(e,t,n){"use strict";var a=n(4),r=n(13),i=n(0),o=(n(1),n(8)),c=n(15),s=n(92),d=n(26),l=i.forwardRef((function(e,t){var n=e.classes,c=e.className,d=e.component,l=void 0===d?"tr":d,u=e.hover,p=void 0!==u&&u,f=e.selected,v=void 0!==f&&f,h=Object(r.a)(e,["classes","className","component","hover","selected"]),g=i.useContext(s.a);return i.createElement(l,Object(a.a)({ref:t,className:Object(o.default)(n.root,c,g&&{head:n.head,footer:n.footer}[g.variant],p&&n.hover,v&&n.selected),role:"tr"===l?null:"row"},h))}));t.a=Object(c.a)((function(e){return{root:{color:"inherit",display:"table-row",verticalAlign:"middle",outline:0,"&$hover:hover":{backgroundColor:e.palette.action.hover},"&$selected, &$selected:hover":{backgroundColor:Object(d.c)(e.palette.secondary.main,e.palette.action.selectedOpacity)}},selected:{},hover:{},head:{},footer:{}}}),{name:"MuiTableRow"})(l)},642:function(e,t,n){"use strict";var a=n(13),r=n(4),i=n(0),o=(n(1),n(8)),c=n(15),s=n(24),d=n(26),l=n(172),u=n(92),p=i.forwardRef((function(e,t){var n,c,d=e.align,p=void 0===d?"inherit":d,f=e.classes,v=e.className,h=e.component,g=e.padding,m=e.scope,b=e.size,E=e.sortDirection,y=e.variant,T=Object(a.a)(e,["align","classes","className","component","padding","scope","size","sortDirection","variant"]),O=i.useContext(l.a),j=i.useContext(u.a),x=j&&"head"===j.variant;h?(c=h,n=x?"columnheader":"cell"):c=x?"th":"td";var N=m;!N&&x&&(N="col");var R=g||(O&&O.padding?O.padding:"default"),_=b||(O&&O.size?O.size:"medium"),A=y||j&&j.variant,P=null;return E&&(P="asc"===E?"ascending":"descending"),i.createElement(c,Object(r.a)({ref:t,className:Object(o.default)(f.root,f[A],v,"inherit"!==p&&f["align".concat(Object(s.a)(p))],"default"!==R&&f["padding".concat(Object(s.a)(R))],"medium"!==_&&f["size".concat(Object(s.a)(_))],"head"===A&&O&&O.stickyHeader&&f.stickyHeader),"aria-sort":P,role:n,scope:N},T))}));t.a=Object(c.a)((function(e){return{root:Object(r.a)({},e.typography.body2,{display:"table-cell",verticalAlign:"inherit",borderBottom:"1px solid\n    ".concat("light"===e.palette.type?Object(d.e)(Object(d.c)(e.palette.divider,1),.88):Object(d.a)(Object(d.c)(e.palette.divider,1),.68)),textAlign:"left",padding:16}),head:{color:e.palette.text.primary,lineHeight:e.typography.pxToRem(24),fontWeight:e.typography.fontWeightMedium},body:{color:e.palette.text.primary},footer:{color:e.palette.text.secondary,lineHeight:e.typography.pxToRem(21),fontSize:e.typography.pxToRem(12)},sizeSmall:{padding:"6px 24px 6px 16px","&:last-child":{paddingRight:16},"&$paddingCheckbox":{width:24,padding:"0 12px 0 16px","&:last-child":{paddingLeft:12,paddingRight:16},"& > *":{padding:0}}},paddingCheckbox:{width:48,padding:"0 0 0 4px","&:last-child":{paddingLeft:0,paddingRight:4}},paddingNone:{padding:0,"&:last-child":{padding:0}},alignLeft:{textAlign:"left"},alignCenter:{textAlign:"center"},alignRight:{textAlign:"right",flexDirection:"row-reverse"},alignJustify:{textAlign:"justify"},stickyHeader:{position:"sticky",top:0,left:0,zIndex:2,backgroundColor:e.palette.background.default}}}),{name:"MuiTableCell"})(p)},643:function(e,t,n){"use strict";var a=n(4),r=n(13),i=n(0),o=(n(1),n(8)),c=n(15),s=n(92),d={variant:"body"},l=i.forwardRef((function(e,t){var n=e.classes,c=e.className,l=e.component,u=void 0===l?"tbody":l,p=Object(r.a)(e,["classes","className","component"]);return i.createElement(s.a.Provider,{value:d},i.createElement(u,Object(a.a)({className:Object(o.default)(n.root,c),ref:t,role:"tbody"===u?null:"rowgroup"},p)))}));t.a=Object(c.a)({root:{display:"table-row-group"}},{name:"MuiTableBody"})(l)},869:function(e,t,n){"use strict";n.r(t);var a=n(0),r=n.n(a),i=n(25),o=n(66),c=n(28),s=n.n(c),d=(n(19),n(21),n(20),n(10)),l=n.n(d),u=n(9),p=n.n(u),f=n(3),v=n.n(f),h=n(11),g=n.n(h),m=n(12),b=n.n(m),E=n(5),y=n.n(E),T=n(2),O=n.n(T),j=n(1),x=n.n(j),N=n(18),R=(n(16),n(46)),_=n.n(R),A=n(6),P=n.n(A),S=(n(32),n(83),n(244),n(15)),w=n(261),C=n(7),k=n.n(C),I=n(382),D=n.n(I),V=n(639),M=n(640),Y=n(641),F=n(642),L=n(643),z=P.a.constants,H=function(e){return r.a.createElement(w.a,{key:k()(e,"data.id"),id:k()(e,"data.id"),valuePath:"data.relationships.field_attendees.data",type:z.TYPE_EVENT_ATTENDANCE})},q=function(e){return r.a.createElement(w.a,{key:k()(e,"data.id"),id:k()(e,"data.id"),valuePath:"data.relationships.field_registrants.data",type:z.TYPE_EVENT_REGISTRATION})},G=function(e){return r.a.createElement("p",{key:k()(e,"data.id")},"Yes")};function U(e){var t=e.classes,n=function(e,t,n,a){var r=D()(t,(function(e){return k()(e,"data.relationships.field_user.data.id")})),i=D()(n,(function(e){return k()(e,"data.relationships.field_user.data.id")})),o=D()(a,(function(e){return k()(e,"data.relationships.uid.data.id")}));return Object.values(e).map((function(e){var t=k()(e,"data.id");return{id:t,name:k()(e,"data.attributes.name"),registered:r[t]||[],attendance:i[t]||[],saved:o[t]||[]}}))}(e.users,e.registrations,e.attendance,e.savedEvents);return r.a.createElement(V.a,{className:t.table},r.a.createElement(M.a,null,r.a.createElement(Y.a,null,r.a.createElement(F.a,null,"Name"),r.a.createElement(F.a,null,"Saved"),r.a.createElement(F.a,null,"Registered"),r.a.createElement(F.a,null,"Scanned"))),r.a.createElement(L.a,null,n.map((function(e){return r.a.createElement(Y.a,{key:e.id},r.a.createElement(F.a,{component:"th",scope:"row"},e.name),r.a.createElement(F.a,null,e.saved.map(G)||null),r.a.createElement(F.a,null,e.registered.map(q)||null),r.a.createElement(F.a,null,e.attendance.map(H)||null))}))))}U.propTypes={classes:x.a.object.isRequired,eventId:x.a.string.isRequired,users:x.a.object,attendance:x.a.object,registrations:x.a.object,savedEvents:x.a.object},U.defaultProps={users:{},attendance:{},registrations:{},savedEvents:{}};var W=Object(S.a)((function(e){return{root:{width:"100%",marginTop:e.spacing(3),overflowX:"auto"},table:{minWidth:700}}}))(U);function X(e){var t=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],(function(){}))),!0}catch(e){return!1}}();return function(){var n,a=y()(e);if(t){var r=y()(this).constructor;n=Reflect.construct(a,arguments,r)}else n=a.apply(this,arguments);return b()(this,n)}}var $=P.a.constants,B=P.a.api,J=P.a.select,K=$,Q=function(e){g()(n,e);var t=X(n);function n(e){var a;return l()(this,n),a=t.call(this,e),O()(v()(a),"handleViewChange",(function(e){a.doFetch(e)})),a.state={open:!1},a.handleViewChange=a.handleViewChange.bind(v()(a)),a.doFetch=_()(a.doFetch,300).bind(v()(a)),a.doFetchRegistrations=a.doFetchRegistrations.bind(v()(a)),a.doFetchSavedEvents=a.doFetchSavedEvents.bind(v()(a)),a}return p()(n,[{key:"componentDidMount",value:function(){this.props.fetchSegments(),this.doFetch()}},{key:"doFetchAttendance",value:function(){this.props.fetchAttendance({filters:{event:{path:"field_event.id",value:this.props.event.uuid}},include:["field_user"],headers:{"X-Consumer-ID":P.a.consumer}})}},{key:"doFetchRegistrations",value:function(){this.props.fetchRegistrations({filters:{event:{path:"field_event.id",value:this.props.event.uuid},status:{path:"status",value:["active","waitlist"],operator:"IN"}},include:["field_user"],headers:{"X-Consumer-ID":P.a.consumer}})}},{key:"doFetchSavedEvents",value:function(){this.props.fetchSavedEvents({filters:{event:{path:"entity_id",value:this.props.event.nid}},include:["uid"],headers:{"X-Consumer-ID":P.a.consumer}})}},{key:"doFetch",value:function(){this.doFetchSavedEvents(),this.doFetchRegistrations(),this.doFetchAttendance()}},{key:"doConfirmAction",value:function(){this.setState({open:!0,text:"Confirm cancel"})}},{key:"render",value:function(){var e=this.props,t=(e.isLoading,e.event),n=e.users,a=e.registrations,i=e.attendance,o=e.savedEvents,c={eventId:t.uuid,users:n,registrations:a,attendance:i,savedEvents:o};return r.a.createElement(W,c)}}]),n}(a.Component);Q.propTypes={event:x.a.shape({nid:x.a.string.isRequired,uuid:x.a.string.isRequired}).isRequired,fetchAttendance:x.a.func.isRequired,fetchRegistrations:x.a.func.isRequired,fetchSavedEvents:x.a.func.isRequired,fetchSegments:x.a.func.isRequired,users:x.a.object,attendance:x.a.object,registrations:x.a.object,savedEvents:x.a.object},Q.defaultProps={users:{},attendance:{},registrations:{},savedEvents:{}};var Z=Object(N.b)((function(e,t){return{attendance:J.records([K.TYPE_EVENT_ATTENDANCE])(e),registrations:J.records([K.TYPE_EVENT_REGISTRATION])(e),savedEvents:J.records([K.TYPE_SAVED_EVENT])(e),users:J.records([K.TYPE_USER])(e),isLoading:J.recordsAreLoading(K.TYPE_EVENT_ATTENDANCE)(e)||J.recordsAreLoading(K.TYPE_EVENT_REGISTRATION)(e)||J.recordsAreLoading(K.TYPE_SAVED_EVENT)(e)}}),(function(e,t){return{fetchAttendance:function(t){e(B[K.TYPE_EVENT_ATTENDANCE].fetchAll(t))},fetchRegistrations:function(t){e(B[K.TYPE_EVENT_REGISTRATION].fetchAll(t))},fetchSavedEvents:function(t){e(B[K.TYPE_SAVED_EVENT].fetchAll(t))},fetchSegments:function(){var t=arguments.length>0&&void 0!==arguments[0]?arguments[0]:{fields:O()({},K.TYPE_POPULATION_SEGMENT,["name","weight"])};e(B[K.TYPE_POPULATION_SEGMENT].fetchAll(t))}}}))(Q),ee=Object(o.a)(Z),te=document.getElementById("eventAttendanceListRoot"),ne=s.a.intercept.user,ae=te.getAttribute("data-event-uuid"),re=te.getAttribute("data-event-nid");Object(i.render)(r.a.createElement(ee,{event:{uuid:ae,nid:re},user:ne}),te)},92:function(e,t,n){"use strict";var a=n(0),r=a.createContext();t.a=r}});