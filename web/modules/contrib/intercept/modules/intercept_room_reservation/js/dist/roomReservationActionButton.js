wpJsonpIntercept([7],{0:function(a,b){a.exports=React},11:function(a,b){a.exports=interceptClient},1245:function(a,b,c){"use strict";function d(a){var b=a.getAttribute("data-reservation-uuid"),c=a.getAttribute("data-status");Object(i.render)(h.a.createElement(q,{entityId:b,isStaff:o.a.utils.userIsStaff(),status:c,type:o.a.constants.TYPE_ROOM_RESERVATION}),a)}Object.defineProperty(b,"__esModule",{value:!0});var e=c(132),f=c.n(e),g=c(0),h=c.n(g),i=c(19),j=c.n(i),k=c(77),l=c(36),m=c.n(l),n=c(11),o=c.n(n),p=c(1246),q=Object(k.a)(p.a),r=f()(document.getElementsByClassName("js--room-reservation-action"));r.map(d)},1246:function(a,b,d){"use strict";function e(a,b){var c=Object.keys(a);if(Object.getOwnPropertySymbols){var d=Object.getOwnPropertySymbols(a);b&&(d=d.filter(function(b){return Object.getOwnPropertyDescriptor(a,b).enumerable})),c.push.apply(c,d)}return c}function f(a){for(var b,c=1;c<arguments.length;c++)b=null==arguments[c]?{}:arguments[c],c%2?e(Object(b),!0).forEach(function(c){K()(a,c,b[c])}):Object.getOwnPropertyDescriptors?Object.defineProperties(a,Object.getOwnPropertyDescriptors(b)):e(Object(b)).forEach(function(c){Object.defineProperty(a,c,Object.getOwnPropertyDescriptor(b,c))});return a}function g(a,b,c){return f({},c,{},a,{},b,{setStatusTo:function c(d){return b.setStatusTo(d,a.record)}})}var h=d(24),i=d.n(h),j=d(25),k=d.n(j),l=d(21),m=d.n(l),n=d(20),o=d.n(n),p=d(17),q=d.n(p),r=d(22),s=d.n(r),t=d(106),u=d.n(t),v=d(31),w=d.n(v),x=d(6),y=d.n(x),z=d(7),A=d.n(z),B=d(8),C=d.n(B),D=d(9),E=d.n(D),F=d(13),G=d.n(F),H=d(10),I=d.n(H),J=d(14),K=d.n(J),L=d(0),M=d.n(L),N=d(1),O=d.n(N),P=d(15),Q=d(23),R=d.n(Q),S=d(11),T=d.n(S),U=d(185),V=d(1247),W=d(117),X=d(112),Y=d(1248),Z=T.a.actions,$=T.a.api,_=T.a.select,aa=T.a.session,ba=T.a.utils,ca=T.a.constants,c=function(a){function b(a){var c;return y()(this,b),c=C()(this,E()(b).call(this,a)),K()(G()(c),"getRoomAvailabilityQuery",function(){var a=c.props.record,b=R()(a,"data.relationships.field_room.data.id"),d=R()(a,"data.attributes.field_dates");return{rooms:[b],start:d.value,end:d.end_value}}),K()(G()(c),"getActions",function(a){var b=G()(c),d=b.cancel,e=b.deny,f=b.approve,g=b.request,h=ba.userIsManager();return"requested"===a?h?[f(),e()]:[d()]:"denied"===a?h?[f(),d()]:null:"approved"===a?h?[e(),d()]:[d()]:"canceled"===a?c.isConflicted()?c.getConflictedMessage():[g()]:null}),K()(G()(c),"getDialogProps",function(a){return"requested"===a?{status:"requested",heading:"Are you sure you want to rerequest this reservation?"}:"denied"===a?{status:"denied",heading:"Are you sure you want to deny this reservation?"}:"approved"===a?{status:"approved",heading:"Are you sure you want to approve this reservation?"}:"canceled"===a?{status:"canceled",heading:"Are you sure you want to cancel this reservation?"}:null}),K()(G()(c),"getConflictedMessage",function(){var a=c.props,b=a.record,d=a.availability,e=R()(b,"data.relationships.field_room.data.id");return!d.loading&&d.rooms[e]?M.a.createElement("p",{className:"action-button__message"},"This reservation time is no longer available."):""}),K()(G()(c),"isConflicted",function(){var a=c.props,b=a.record,d=a.availability,e=R()(b,"data.relationships.field_room.data.id");if(!d.loading&&d.rooms[e]){var f=d.rooms[e];return f.has_max_duration_conflict||f.has_open_hours_conflict||f.has_reservation_conflict}return!0}),K()(G()(c),"openDialog",function(a){c.setState({open:!0,dialogProps:c.getDialogProps(a)})}),K()(G()(c),"closeDialog",function(){c.setState({open:!1})}),K()(G()(c),"closeErrorDialog",function(){c.props.fetchReservation(c.props.entityId),c.setState({errorDialogOpen:!1})}),K()(G()(c),"confirmDialog",function(a){c.props.setStatusTo(a),c.closeDialog()}),K()(G()(c),"cancel",function(){return c.actionButton({status:"canceled",label:"Cancel"})}),K()(G()(c),"deny",function(){return c.actionButton({status:"denied",label:"Deny"})}),K()(G()(c),"request",function(){return c.actionButton({status:"requested",label:"Rerequest"})}),K()(G()(c),"approve",function(){return c.actionButton({status:"approved",label:"Approve",variant:"contained"})}),K()(G()(c),"dialog",function(){var a=c.state.dialogProps;return M.a.createElement(W.a,w()({},a,{open:c.state.open,onClose:c.onDialogClose,onCancel:c.closeDialog,onBackdropClick:null,disableEscapeKeyDown:c.state.disableEscapeKeyDown,disableBackdropClick:c.state.disableBackdropClick,onConfirm:function b(){return c.confirmDialog(a.status)},confirmText:"Yes"}))}),K()(G()(c),"errorDialog",function(){var a=c.state.dialogProps,b=c.props.record,d=R()(b,"state.error")||[],e=d.map(function(a){return a.detail.replace("Entity is not valid: ","")})||"Unknown Error";return M.a.createElement(W.a,w()({},a,{open:c.state.errorDialogOpen,onClose:c.closeErrorDialog,onConfirm:c.closeErrorDialog,onBackdropClick:null,disableEscapeKeyDown:c.state.disableEscapeKeyDown,disableBackdropClick:c.state.disableBackdropClick,heading:"Unable to update reservation",text:e,confirmText:"Close"}))}),c.state={open:!1,errorDialogOpen:!1,disableBackdropClick:!0,disableEscapeKeyDown:!0,dialogProps:{}},c.actionButton=c.actionButton.bind(G()(c)),c}return I()(b,a),A()(b,[{key:"componentDidMount",value:function a(){this.props.fetchReservation(this.props.entityId)}},{key:"componentDidUpdate",value:function a(b){var c=this.props,d=c.record,e=c.isLoading,f=c.availability,g=c.fetchAvailability,h=b.record,i=R()(d,"state.error"),j=R()(h,"state.error");e||null===d||f.loading||0!==f.rooms.length&&h===d||g(this.getRoomAvailabilityQuery()),i&&!j&&this.setState({errorDialogOpen:!0})}},{key:"actionButton",value:function a(b){var c=this,d=b.status,e=b.label,f=b.variant,g=this.props,h=g.record,i=g.entityId;return h?M.a.createElement(V.a,{entityId:i,type:ca.TYPE_ROOM_RESERVATION,record:h,text:e,onClick:function a(){return c.openDialog(d)},key:d,variant:f}):null}},{key:"render",value:function a(){var b=this.props,c=b.record,d=b.isLoading,e=R()(c,"data.attributes.field_status")||this.props.status,f=d?M.a.createElement(X.a,{loading:d,size:20}):this.getActions(e);return M.a.createElement("div",{className:"reservation-register-button__inner"},M.a.createElement(Y.a,{status:e,syncing:R()(c,"state.syncing")}),f,this.dialog(),this.errorDialog())}}]),b}(M.a.Component);c.propTypes={entityId:O.a.string.isRequired,type:O.a.string.isRequired,status:O.a.string.isRequired,fetchReservation:O.a.func.isRequired,fetchAvailability:O.a.func.isRequired,availability:O.a.object.isRequired,isLoading:O.a.bool,setStatusTo:O.a.func.isRequired,record:O.a.object},c.defaultProps={record:null,isLoading:!1};var da=function a(b,c){return{record:_.record(_.getIdentifier(ca.TYPE_ROOM_RESERVATION,c.entityId))(b),isLoading:_.recordsAreLoading(ca.TYPE_ROOM_RESERVATION)(b)||_.recordIsLoading(ca.TYPE_ROOM_RESERVATION,c.entityId)(b)}},ea=function a(b,c){var d=c.entityId,e=c.type,f=function a(){aa.getToken().then(function(a){b($[e].sync(d,{headers:{"X-CSRF-Token":a}}))}).catch(function(a){console.log("Unable to save Reservation",a)})},g=function a(c,f){var g=f.data;g.attributes.field_status=c,b(Z.edit(g,e,d))};return{setStatusTo:function a(b,c){g(b,c),f(e)},fetchReservation:function a(c){b($[ca.TYPE_ROOM_RESERVATION].fetchResource(c))}}};b.a=Object(P.b)(da,ea,g)(Object(U.a)(c))},1247:function(a,b,c){"use strict";var d=c(6),e=c.n(d),f=c(7),g=c.n(f),h=c(8),i=c.n(h),j=c(9),k=c.n(j),l=c(10),m=c.n(l),n=c(0),o=c.n(n),p=c(1),q=c.n(p),r=c(11),s=c.n(r),t=c(16),u=s.a.utils,v=u.getUserUuid(),w=function(a){function b(){return e()(this,b),i()(this,k()(b).apply(this,arguments))}return m()(b,a),g()(b,[{key:"render",value:function a(){var b=this.props,c=b.onClick,d=b.text,e=b.variant;return o.a.createElement(t.b,{variant:e,size:"small",color:"primary",className:"action-button__button",onClick:c},d)}}]),b}(o.a.PureComponent);w.propTypes={entityId:q.a.string.isRequired,onClick:q.a.func,text:q.a.string,variant:q.a.string},w.defaultProps={onClick:null,userId:v,mustRegister:!1,registrationAllowed:!1,registerUrl:null,text:"",variant:"outlined"},b.a=w},1248:function(a,b,c){"use strict";var d=c(6),e=c.n(d),f=c(7),g=c.n(f),h=c(8),i=c.n(h),j=c(9),k=c.n(j),l=c(13),m=c.n(l),n=c(10),o=c.n(n),p=c(14),q=c.n(p),r=c(0),s=c.n(r),t=c(1),u=c.n(t),v=c(15),w=c(11),x=c.n(w),y=c(36),z=c.n(y),A=function(a){function b(){var a,c;e()(this,b);for(var d=arguments.length,f=Array(d),g=0;g<d;g++)f[g]=arguments[g];return c=i()(this,(a=k()(b)).call.apply(a,[this].concat(f))),q()(m()(c),"getText",function(a,b){return"denied"===a?b?"Denying":"Denied":"approved"===a?b?"Approving":"Approved":"canceled"===a?b?"Cancelling":"Canceled":"requested"===a?b?"Rerequesting":"Awaiting Approval":null}),c}return o()(b,a),g()(b,[{key:"render",value:function a(){var b=this.getText(this.props.status,this.props.syncing);return b?s.a.createElement("p",{className:"action-button__message"},b):null}}]),b}(s.a.PureComponent);A.propTypes={status:u.a.string.isRequired,syncing:u.a.bool},A.defaultProps={syncing:!1},b.a=A},185:function(a,b,c){"use strict";function d(a,b){var c=Object.keys(a);if(Object.getOwnPropertySymbols){var d=Object.getOwnPropertySymbols(a);b&&(d=d.filter(function(b){return Object.getOwnPropertyDescriptor(a,b).enumerable})),c.push.apply(c,d)}return c}function f(a){for(var b,c=1;c<arguments.length;c++)b=null==arguments[c]?{}:arguments[c],c%2?d(Object(b),!0).forEach(function(c){H()(a,c,b[c])}):Object.getOwnPropertyDescriptors?Object.defineProperties(a,Object.getOwnPropertyDescriptors(b)):d(Object(b)).forEach(function(c){Object.defineProperty(a,c,Object.getOwnPropertyDescriptor(b,c))});return a}function e(a){var b,c=1<arguments.length&&arguments[1]!==void 0?arguments[1]:200;return b=function(b){function d(a){var b;return v()(this,d),b=z()(this,B()(d).call(this,a)),H()(D()(b),"handleResponse",function(a){return b.mounted&&b.setState({availability:f({},b.state.availability,{loading:!1,rooms:JSON.parse(a),shouldUpdate:!1})}),a}),b.handleResponse=b.handleResponse.bind(D()(b)),b.fetchAvailability=L()(b.fetchAvailability,c).bind(D()(b)),b.state={availability:{loading:!1,shouldUpdate:!1,rooms:[]}},b}return F()(d,b),x()(d,[{key:"componentDidMount",value:function a(){this.mounted=!0}},{key:"componentWillUnmount",value:function a(){this.mounted=!1}},{key:"fetchAvailability",value:function a(b){var c=this,d=1<arguments.length&&void 0!==arguments[1]?arguments[1]:function(a){return a};return this.setState({availability:f({},this.state.availability,{loading:!0,shouldUpdate:!1})}),fetch("/api/rooms/availability",{method:"POST",credentials:"same-origin",headers:{"Content-Type":"application/json",Accept:"application/json"},body:JSON.stringify(f({},b,{start:O.dateToDrupal(O.ensureDate(b.start)),end:O.dateToDrupal(O.ensureDate(b.end))}))}).then(function(a){return a.text()}).then(function(a){return d(c.handleResponse(a))}).catch(function(a){c.mounted&&(console.log(a),c.setState({availability:f({},c.state.availability,{loading:!1,shouldUpdate:!1})}))})}},{key:"render",value:function b(){return J.a.createElement(a,t()({availability:this.state.availability,fetchAvailability:this.fetchAvailability},this.props))}}]),d}(J.a.Component),b}var g=c(24),h=c.n(g),i=c(25),j=c.n(i),k=c(21),l=c.n(k),m=c(20),n=c.n(m),o=c(17),p=c.n(o),q=c(22),r=c.n(q),s=c(31),t=c.n(s),u=c(6),v=c.n(u),w=c(7),x=c.n(w),y=c(8),z=c.n(y),A=c(9),B=c.n(A),C=c(13),D=c.n(C),E=c(10),F=c.n(E),G=c(14),H=c.n(G),I=c(0),J=c.n(I),K=c(55),L=c.n(K),M=c(11),N=c.n(M),O=N.a.utils;b.a=e},19:function(a,b){a.exports=ReactDOM},36:function(a,b){a.exports=drupalSettings},78:function(a,b){a.exports=interceptTheme}},[1245]);