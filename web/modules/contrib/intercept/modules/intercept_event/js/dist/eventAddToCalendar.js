!function(t){function e(e){for(var o,i,s=e[0],c=e[1],l=e[2],p=0,d=[];p<s.length;p++)i=s[p],Object.prototype.hasOwnProperty.call(a,i)&&a[i]&&d.push(a[i][0]),a[i]=0;for(o in c)Object.prototype.hasOwnProperty.call(c,o)&&(t[o]=c[o]);for(u&&u(e);d.length;)d.shift()();return r.push.apply(r,l||[]),n()}function n(){for(var t,e=0;e<r.length;e++){for(var n=r[e],o=!0,s=1;s<n.length;s++){var c=n[s];0!==a[c]&&(o=!1)}o&&(r.splice(e--,1),t=i(i.s=n[0]))}return t}var o={},a={2:0},r=[];function i(e){if(o[e])return o[e].exports;var n=o[e]={i:e,l:!1,exports:{}};return t[e].call(n.exports,n,n.exports,i),n.l=!0,n.exports}i.m=t,i.c=o,i.d=function(t,e,n){i.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:n})},i.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},i.t=function(t,e){if(1&e&&(t=i(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var n=Object.create(null);if(i.r(n),Object.defineProperty(n,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var o in t)i.d(n,o,function(e){return t[e]}.bind(null,o));return n},i.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return i.d(e,"a",e),e},i.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},i.p="";var s=window.wpJsonpIntercept=window.wpJsonpIntercept||[],c=s.push.bind(s);s.push=e,s=s.slice();for(var l=0;l<s.length;l++)e(s[l]);var u=c;r.push([876,0]),n()}({0:function(t,e){t.exports=React},16:function(t,e){t.exports=moment},209:function(t,e,n){var o=n(47),a=n(210),r=n(68),i=Math.ceil,s=function(t){return function(e,n,s){var c,l,u=String(r(e)),p=u.length,d=void 0===s?" ":String(s),m=o(n);return m<=p||""==d?u:(c=m-p,(l=a.call(d,i(c/d.length))).length>c&&(l=l.slice(0,c)),t?u+l:l+u)}};t.exports={start:s(!1),end:s(!0)}},210:function(t,e,n){"use strict";var o=n(104),a=n(68);t.exports="".repeat||function(t){var e=String(a(this)),n="",r=o(t);if(r<0||r==1/0)throw RangeError("Wrong number of repetitions");for(;r>0;(r>>>=1)&&(e+=e))1&r&&(n+=e);return n}},232:function(t,e,n){var o=n(15),a=n(272);o({target:"Date",proto:!0,forced:Date.prototype.toISOString!==a},{toISOString:a})},25:function(t,e){t.exports=ReactDOM},272:function(t,e,n){"use strict";var o=n(32),a=n(209).start,r=Math.abs,i=Date.prototype,s=i.getTime,c=i.toISOString;t.exports=o((function(){return"0385-07-25T07:06:39.999Z"!=c.call(new Date(-50000000000001))}))||!o((function(){c.call(new Date(NaN))}))?function(){if(!isFinite(s.call(this)))throw RangeError("Invalid time value");var t=this.getUTCFullYear(),e=this.getUTCMilliseconds(),n=t<0?"-":t>9999?"+":"";return n+a(r(t),n?6:4,0)+"-"+a(this.getUTCMonth()+1,2,0)+"-"+a(this.getUTCDate(),2,0)+"T"+a(this.getUTCHours(),2,0)+":"+a(this.getUTCMinutes(),2,0)+":"+a(this.getUTCSeconds(),2,0)+"."+a(e,3,0)+"Z"}:c},393:function(t,e,n){"use strict";var o=n(274),a=n(234),r=n(49),i=n(68),s=n(136),c=n(275),l=n(47),u=n(276),p=n(273),d=n(32),m=[].push,h=Math.min,f=!d((function(){return!RegExp(4294967295,"y")}));o("split",2,(function(t,e,n){var o;return o="c"=="abbc".split(/(b)*/)[1]||4!="test".split(/(?:)/,-1).length||2!="ab".split(/(?:ab)*/).length||4!=".".split(/(.?)(.?)/).length||".".split(/()()/).length>1||"".split(/.?/).length?function(t,n){var o=String(i(this)),r=void 0===n?4294967295:n>>>0;if(0===r)return[];if(void 0===t)return[o];if(!a(t))return e.call(o,t,r);for(var s,c,l,u=[],d=(t.ignoreCase?"i":"")+(t.multiline?"m":"")+(t.unicode?"u":"")+(t.sticky?"y":""),h=0,f=new RegExp(t.source,d+"g");(s=p.call(f,o))&&!((c=f.lastIndex)>h&&(u.push(o.slice(h,s.index)),s.length>1&&s.index<o.length&&m.apply(u,s.slice(1)),l=s[0].length,h=c,u.length>=r));)f.lastIndex===s.index&&f.lastIndex++;return h===o.length?!l&&f.test("")||u.push(""):u.push(o.slice(h)),u.length>r?u.slice(0,r):u}:"0".split(void 0,0).length?function(t,n){return void 0===t&&0===n?[]:e.call(this,t,n)}:e,[function(e,n){var a=i(this),r=null==e?void 0:e[t];return void 0!==r?r.call(e,a,n):o.call(String(a),e,n)},function(t,a){var i=n(o,t,this,a,o!==e);if(i.done)return i.value;var p=r(t),d=String(this),m=s(p,RegExp),g=p.unicode,v=(p.ignoreCase?"i":"")+(p.multiline?"m":"")+(p.unicode?"u":"")+(f?"y":"g"),b=new m(f?p:"^(?:"+p.source+")",v),w=void 0===a?4294967295:a>>>0;if(0===w)return[];if(0===d.length)return null===u(b,d)?[d]:[];for(var y=0,C=0,k=[];C<d.length;){b.lastIndex=f?C:0;var T,I=u(b,f?d:d.slice(C));if(null===I||(T=h(l(b.lastIndex+(f?0:C)),d.length))===y)C=c(d,C,g);else{if(k.push(d.slice(y,C)),k.length===w)return k;for(var O=1;O<=I.length-1;O++)if(k.push(I[O]),k.length===w)return k;C=y=T}}return k.push(d.slice(y)),k}]}),!f)},400:function(t,e,n){"use strict";var o,a=n(15),r=n(83).f,i=n(47),s=n(237),c=n(68),l=n(238),u=n(97),p="".startsWith,d=Math.min,m=l("startsWith");a({target:"String",proto:!0,forced:!!(u||m||(o=r(String.prototype,"startsWith"),!o||o.writable))&&!m},{startsWith:function(t){var e=String(c(this));s(t);var n=i(d(arguments.length>1?arguments[1]:void 0,e.length)),o=String(t);return p?p.call(e,o,n):e.slice(n,n+o.length)===o}})},51:function(t,e){t.exports=Drupal},6:function(t,e){t.exports=interceptClient},876:function(t,e,n){"use strict";n.r(e);n(31),n(232),n(70),n(393),n(330);var o=n(62),a=n.n(o),r=n(0),i=n.n(r),s=n(25),c=(n(29),n(98),n(67),n(37),n(19),n(21),n(20),n(178),n(400),n(74),n(239),n(10)),l=n.n(c),u=n(9),p=n.n(u),d=n(3),m=n.n(d),h=n(11),f=n.n(h),g=n(12),v=n.n(g),b=n(5),w=n.n(b),y=n(1),C=n.n(y),k=(n(41),n(403),n(211),n(119),n(212),n(114),n(16)),T=n.n(k);function I(t){var e=function(){if("undefined"==typeof Reflect||!Reflect.construct)return!1;if(Reflect.construct.sham)return!1;if("function"==typeof Proxy)return!0;try{return Date.prototype.toString.call(Reflect.construct(Date,[],(function(){}))),!0}catch(t){return!1}}();return function(){var n,o=w()(t);if(e){var a=w()(this).constructor;n=Reflect.construct(o,arguments,a)}else n=o.apply(this,arguments);return v()(this,n)}}var O=new(function(){function t(){l()(this,t)}return p()(t,[{key:"getRandomKey",value:function(){var t=Math.floor(999999999999*Math.random()).toString();return"".concat((new Date).getTime().toString(),"_").concat(t)}},{key:"formatTime",value:function(t){return T.a.utc(t).format("YYYYMMDDTHHmmssZ").replace("+00:00","Z")}},{key:"formatISO",value:function(t){return T.a.utc(t).toISOString()}},{key:"calculateDuration",value:function(t,e){var n=T.a.utc(e).format("DD/MM/YYYY HH:mm:ss"),o=T.a.utc(t).format("DD/MM/YYYY HH:mm:ss"),a=T()(n,"DD/MM/YYYY HH:mm:ss").diff(T()(o,"DD/MM/YYYY HH:mm:ss")),r=T.a.duration(a);return Math.floor(r.asHours())+T.a.utc(a).format(":mm")}},{key:"buildUrl",value:function(t,e,n){var o="";switch(e){case"google":o="https://calendar.google.com/calendar/render",o+="?action=TEMPLATE",o+="&dates=".concat(this.formatTime(t.startTime)),o+="/".concat(this.formatTime(t.endTime)),o+="&location=".concat(encodeURIComponent(t.location)),o+="&text=".concat(encodeURIComponent(t.title)),o+="&details=".concat(encodeURIComponent(t.description)),t.url&&t.url.includes("/event")&&(o+=encodeURIComponent(' <a href="'.concat(t.url,'">View Event</a>')));break;case"yahoo":var a=this.calculateDuration(t.startTime,t.endTime);o="https://calendar.yahoo.com/?v=60&view=d&type=20",o+="&title=".concat(encodeURIComponent(t.title)),o+="&st=".concat(this.formatTime(t.startTime)),o+="&dur=".concat(a),o+="&desc=".concat(encodeURIComponent(t.description)),o+="&in_loc=".concat(encodeURIComponent(t.location));break;case"outlookcom":o="https://outlook.live.com/calendar/0/deeplink/compose?rru=addevent",o+="&startdt=".concat(this.formatISO(t.startTime)),o+="&enddt=".concat(this.formatISO(t.endTime)),o+="&subject=".concat(encodeURIComponent(t.title)),o+="&location=".concat(encodeURIComponent(t.location)),t.url.includes("/event")?o+="&body=".concat(encodeURIComponent("".concat(t.description," ").concat(t.url?"View Event: ".concat(t.url):""))):o+="&body=".concat(encodeURIComponent("".concat(t.description))),o+="&allday=false",o+="&uid=".concat(this.getRandomKey()),o+="&path=/calendar/view/Month";break;default:o=["BEGIN:VCALENDAR","VERSION:2.0","BEGIN:VEVENT","URL:".concat(document.URL),"DTSTART:".concat(this.formatTime(t.startTime)),"DTEND:".concat(this.formatTime(t.endTime)),"SUMMARY:".concat(t.title),"DESCRIPTION:".concat(t.description),"LOCATION:".concat(t.location),"END:VEVENT","END:VCALENDAR"].join("\n"),!n&&this.isMobile()&&(o=encodeURI("data:text/calendar;charset=utf8,".concat(o)))}return o}},{key:"isMobile",value:function(){var t,e=!1;return t=window.navigator.userAgent||window.navigator.vendor||window.opera,(/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(t)||/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(t.substr(0,4)))&&(e=!0),e}}]),t}()),D=function(t){f()(n,t);var e=I(n);function n(t){var o;return l()(this,n),(o=e.call(this,t)).state={optionsOpen:t.optionsOpen||!1,isCrappyIE:!1},o.toggleCalendarDropdown=o.toggleCalendarDropdown.bind(m()(o)),o.handleDropdownLinkClick=o.handleDropdownLinkClick.bind(m()(o)),o}return p()(n,[{key:"componentWillMount",value:function(){String.prototype.startsWith||(String.prototype.startsWith=function(t,e){return e=e||0,this.indexOf(t,e)===e});var t=!1;"undefined"!=typeof window&&window.navigator.msSaveOrOpenBlob&&window.Blob&&(t=!0),this.setState({isCrappyIE:t})}},{key:"toggleCalendarDropdown",value:function(t){void 0!==t&&t.stopPropagation();var e=!this.state.optionsOpen;e?document.addEventListener("click",this.toggleCalendarDropdown,!1):document.removeEventListener("click",this.toggleCalendarDropdown),this.setState({optionsOpen:e})}},{key:"handleDropdownLinkClick",value:function(t){t.preventDefault();var e=t.currentTarget.getAttribute("href");if(O.isMobile()||!e.startsWith("data")&&!e.startsWith("BEGIN"))window.open(e,"_blank");else{var n=new Blob([e],{type:"text/calendar;charset=utf-8"});if(this.state.isCrappyIE)window.navigator.msSaveOrOpenBlob(n,"download.ics");else{var o=document.createElement("a");o.href=window.URL.createObjectURL(n),o.setAttribute("download","download.ics"),document.body.appendChild(o),o.click(),document.body.removeChild(o)}}this.toggleCalendarDropdown()}},{key:"renderDropdown",value:function(){var t=this,e=this.props.listItems.map((function(e){var n=Object.keys(e)[0],o=e[n],a=null;if(t.props.displayItemIcons){var r="outlook"===n||"outlookcom"===n?"windows":n;a=i.a.createElement("i",{className:"fa fa-".concat(r)})}return i.a.createElement("li",{key:O.getRandomKey()},i.a.createElement("a",{className:"".concat(n,"-link"),onClick:t.handleDropdownLinkClick,href:O.buildUrl(t.props.event,n,t.state.isCrappyIE),target:"_blank"},a,o))}));return i.a.createElement("div",{className:this.props.dropdownClass},i.a.createElement("ul",null,e))}},{key:"renderButton",value:function(){var t=this.props.buttonLabel,e=null,n=Object.keys(this.props.buttonTemplate);if("textOnly"!==n[0]){var o=this.props.buttonTemplate[n],a="react-add-to-calendar__icon--"===this.props.buttonIconClass?"".concat(this.props.buttonIconClass).concat(o):this.props.buttonIconClass,r=this.props.useFontAwesomeIcons?"fa fa-":"",s="caret"===n[0]?this.state.optionsOpen?"caret-up":"caret-down":n[0],c="".concat(a," ").concat(r).concat(s);e=i.a.createElement("i",{className:c}),t="right"===o?i.a.createElement("span",null,"".concat(t," "),e):i.a.createElement("span",null,e," ".concat(t))}var l=this.state.optionsOpen?"".concat(this.props.buttonClassClosed," ").concat(this.props.buttonClassOpen):this.props.buttonClassClosed;return i.a.createElement("div",{className:this.props.buttonWrapperClass},i.a.createElement("a",{className:l,onClick:this.toggleCalendarDropdown},t))}},{key:"render",value:function(){var t=null;this.state.optionsOpen&&(t=this.renderDropdown());var e=null;return this.props.event&&(e=this.renderButton()),i.a.createElement("div",{className:this.props.rootClass},e,t)}}]),n}(i.a.Component);D.displayName="Add To Calendar",D.propTypes={buttonClassClosed:C.a.string,buttonClassOpen:C.a.string,buttonLabel:C.a.string,buttonTemplate:C.a.object,buttonIconClass:C.a.string,useFontAwesomeIcons:C.a.bool,buttonWrapperClass:C.a.string,displayItemIcons:C.a.bool,optionsOpen:C.a.bool,dropdownClass:C.a.string,event:C.a.shape({title:C.a.string,description:C.a.string,location:C.a.string,startTime:C.a.string,endTime:C.a.string,url:C.a.string}).isRequired,listItems:C.a.arrayOf(C.a.object),rootClass:C.a.string},D.defaultProps={buttonClassClosed:"react-add-to-calendar__button",buttonClassOpen:"react-add-to-calendar__button--light",buttonLabel:"Add to My Calendar",buttonTemplate:{caret:"right"},buttonIconClass:"react-add-to-calendar__icon--",useFontAwesomeIcons:!0,buttonWrapperClass:"react-add-to-calendar__wrapper",displayItemIcons:!0,optionsOpen:!1,dropdownClass:"react-add-to-calendar__dropdown",event:{title:"Sample Event",description:"This is the sample event provided as an example only",location:"Portland, OR",startTime:"2016-09-16T20:15:00-04:00",endTime:"2016-09-16T21:45:00-04:00",url:null},listItems:[{apple:"Apple Calendar"},{google:"Google"},{outlook:"Outlook"},{outlookcom:"Outlook.com"},{yahoo:"Yahoo"}],rootClass:"react-add-to-calendar"};var E=n(51),x=n.n(E),S=n(6),R=n.n(S).a.utils,M=function(t,e){var n=t.getElementsByClassName(e);return n.length>0?n[0].innerHTML:null},_=function(t){return T.a.tz(t,"YYYY-MM-DD HH:mm:ss",R.getUserTimezone()).toISOString()};function Y(t){var e={title:M(t,"atc_title"),description:M(t,"atc_description").trim(),location:M(t,"atc_location"),startTime:_(M(t,"atc_date_start")),endTime:_(M(t,"atc_date_end")),url:window.location.href},n=(t.getAttribute("data-calendars")||"").split(", ").map((function(t){switch(t){case"iCalendar":return{apple:"Apple or Outlook (.ics)"};case"Google Calendar":return{google:"Google"};case"Outlook":return{outlook:"Outlook"};case"Outlook Online":return{outlookcom:"Outlook.com"};case"Yahoo! Calendar":return{yahoo:"Yahoo"};default:return null}}));Object(s.render)(i.a.createElement(D,{className:"add-to-cal",event:e,listItems:n}),t)}x.a.behaviors.interceptAddToCalendar={attach:function(t){a()(t.getElementsByClassName("addtocalendar")).map(Y)}}}});