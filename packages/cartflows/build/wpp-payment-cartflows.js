(()=>{var e={926:e=>{function t(e,t,r,n,o,i,c){try{var a=e[i](c),u=a.value}catch(e){return void r(e)}a.done?t(u):Promise.resolve(u).then(n,o)}e.exports=function(e){return function(){var r=this,n=arguments;return new Promise((function(o,i){var c=e.apply(r,n);function a(e){t(c,o,i,a,u,"next",e)}function u(e){t(c,o,i,a,u,"throw",e)}a(void 0)}))}}},713:e=>{e.exports=function(e,t,r){return t in e?Object.defineProperty(e,t,{value:r,enumerable:!0,configurable:!0,writable:!0}):e[t]=r,e}},318:e=>{e.exports=function(e){return e&&e.__esModule?e:{default:e}}},479:(e,t,r)=>{var n=r(316);e.exports=function(e,t){if(null==e)return{};var r,o,i=n(e,t);if(Object.getOwnPropertySymbols){var c=Object.getOwnPropertySymbols(e);for(o=0;o<c.length;o++)r=c[o],t.indexOf(r)>=0||Object.prototype.propertyIsEnumerable.call(e,r)&&(i[r]=e[r])}return i}},316:e=>{e.exports=function(e,t){if(null==e)return{};var r,n,o={},i=Object.keys(e);for(n=0;n<i.length;n++)r=i[n],t.indexOf(r)>=0||(o[r]=e[r]);return o}},465:(e,t,r)=>{"use strict";r.r(t),r.d(t,{loadStripe:()=>f});var n="https://js.stripe.com/v3",o=/^https:\/\/js\.stripe\.com\/v3\/?(\?.*)?$/,i="loadStripe.setLoadParameters was called but an existing Stripe.js script already exists in the document; existing script parameters will be used",c=null,a=function(e,t,r){if(null===e)return null;var n=e.apply(void 0,t);return function(e,t){e&&e._registerWrapper&&e._registerWrapper({name:"stripe-js",version:"1.12.1",startTime:t})}(n,r),n},u=Promise.resolve().then((function(){return e=null,null!==c?c:c=new Promise((function(t,r){if("undefined"!=typeof window)if(window.Stripe&&e&&console.warn(i),window.Stripe)t(window.Stripe);else try{var c=function(){for(var e=document.querySelectorAll('script[src^="'.concat(n,'"]')),t=0;t<e.length;t++){var r=e[t];if(o.test(r.src))return r}return null}();c&&e?console.warn(i):c||(c=function(e){var t=e&&!e.advancedFraudSignals?"?advancedFraudSignals=false":"",r=document.createElement("script");r.src="".concat(n).concat(t);var o=document.head||document.body;if(!o)throw new Error("Expected document.body not to be null. Stripe.js requires a <body> element.");return o.appendChild(r),r}(e)),c.addEventListener("load",(function(){window.Stripe?t(window.Stripe):r(new Error("Stripe.js not available"))})),c.addEventListener("error",(function(){r(new Error("Failed to load Stripe.js"))}))}catch(e){return void r(e)}else t(null)}));var e})),s=!1;u.catch((function(e){s||console.warn(e)}));var f=function(){for(var e=arguments.length,t=new Array(e),r=0;r<e;r++)t[r]=arguments[r];s=!0;var n=Date.now();return u.then((function(e){return a(e,t,n)}))}},609:e=>{"use strict";e.exports=window.jQuery},15:e=>{"use strict";e.exports=window.regeneratorRuntime},606:e=>{"use strict";e.exports=window.wp.apiFetch}},t={};function r(n){if(t[n])return t[n].exports;var o=t[n]={exports:{}};return e[n](o,o.exports,r),o.exports}r.d=(e,t)=>{for(var n in t)r.o(t,n)&&!r.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},r.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),r.r=e=>{"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},(()=>{var e=r(318),t=e(r(713)),n=e(r(479)),o=e(r(15)),i=e(r(926)),c=e(r(609)),a=r(465),u=e(r(606));function s(e,t){var r=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),r.push.apply(r,n)}return r}function f(e){for(var r=1;r<arguments.length;r++){var n=null!=arguments[r]?arguments[r]:{};r%2?s(Object(n),!0).forEach((function(r){(0,t.default)(e,r,n[r])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):s(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}var l,d=cartflows_offer.stripeData,p=new Promise((function(e){(0,a.loadStripe)(d.key,d.accountId?{stripeAccount:d.accountId}:{}).then((function(t){e(t)})).catch((function(t){e(!1)}))})),w=function(){var e=(0,i.default)(o.default.mark((function e(t){var r,n;return o.default.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:if(r=t.newURL.match(/response=(.*)/))try{(n=JSON.parse(window.atob(decodeURIComponent(r[1]))))&&n.hasOwnProperty("client_secret")&&(history.pushState({},"",window.location.pathname+window.location.search),v(n))}catch(e){}return e.abrupt("return",!0);case 3:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}(),v=function(){var e=(0,i.default)(o.default.mark((function e(t){var r,i;return o.default.wrap((function(e){for(;;)switch(e.prev=e.next){case 0:return r=t.client_secret,i=(0,n.default)(t,["client_secret"]),e.next=3,p;case 3:e.sent.handleCardAction(r).then((function(e){e.error?((0,c.default)("body").trigger("wcf-update-msg",[e.error.message,"wcf-payment-error"]),setTimeout((function(){(0,c.default)(document.body).trigger("wcf-hide-loader"),(0,c.default)(document.body).trigger("wcf-update-msg",[d.msg,"wcf-payment-success"])}),d.timeout),h(f({client_secret:r},i))):y()}));case 5:case"end":return e.stop()}}),e)})));return function(t){return e.apply(this,arguments)}}(),y=function(){l.click()},h=function(e){return new Promise((function(t,r){(0,u.default)({path:"/wpp-stripe/v1/cartflows/payment-intent",method:"POST",data:e}).then((function(e){})).catch((function(e){}))}))};window.addEventListener("hashchange",w),(0,c.default)(document.body).on("click",'a[href*="wcf-up-offer"], a[href*="wcf-down-offer"]',(function(e){l=(0,c.default)(e.currentTarget)}))})(),(this.wpp_payment=this.wpp_payment||{})["wpp-payment-cartflows"]={}})();
//# sourceMappingURL=wpp-payment-cartflows.js.map