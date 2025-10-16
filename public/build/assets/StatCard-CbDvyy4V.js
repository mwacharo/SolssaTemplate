import{_ as h,j as M,d as m,o as s,a as r,e as S,t as d,n as k,c as u,u as w,a3 as j}from"./app-BG1kvza6.js";/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const x=e=>e.replace(/([a-z0-9])([A-Z])/g,"$1-$2").toLowerCase(),q=e=>e.replace(/^([A-Z])|[\s-_]+(\w)/g,(t,n,a)=>a?a.toUpperCase():n.toLowerCase()),A=e=>{const t=q(e);return t.charAt(0).toUpperCase()+t.slice(1)},_=(...e)=>e.filter((t,n,a)=>!!t&&t.trim()!==""&&a.indexOf(t)===n).join(" ").trim(),f=e=>e==="";/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */var i={xmlns:"http://www.w3.org/2000/svg",width:24,height:24,viewBox:"0 0 24 24",fill:"none",stroke:"currentColor","stroke-width":2,"stroke-linecap":"round","stroke-linejoin":"round"};/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const B=({name:e,iconNode:t,absoluteStrokeWidth:n,"absolute-stroke-width":a,strokeWidth:o,"stroke-width":p,size:l=i.width,color:C=i.stroke,...y},{slots:g})=>h("svg",{...i,...y,width:l,height:l,stroke:C,"stroke-width":f(n)||f(a)||n===!0||a===!0?Number(o||p||i["stroke-width"])*24/Number(l):o||p||i["stroke-width"],class:_("lucide",y.class,...e?[`lucide-${x(A(e))}-icon`,`lucide-${x(e)}`]:["lucide-icon"])},[...t.map(b=>h(...b)),...g.default?[g.default()]:[]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const c=(e,t)=>(n,{slots:a,attrs:o})=>h(B,{...o,...n,iconNode:t,name:e},a);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const H=c("dollar-sign",[["line",{x1:"12",x2:"12",y1:"2",y2:"22",key:"7eqyqh"}],["path",{d:"M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6",key:"1b0p4s"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const v=c("package",[["path",{d:"M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z",key:"1a0edw"}],["path",{d:"M12 22V12",key:"d0xqtd"}],["polyline",{points:"3.29 7 12 12 20.71 7",key:"ousv84"}],["path",{d:"m7.5 4.27 9 5.15",key:"1c824w"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const D=c("shopping-cart",[["circle",{cx:"8",cy:"21",r:"1",key:"jimo8o"}],["circle",{cx:"19",cy:"21",r:"1",key:"13723u"}],["path",{d:"M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12",key:"9zh506"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const L=c("trending-down",[["path",{d:"M16 17h6v-6",key:"t6n2it"}],["path",{d:"m22 17-8.5-8.5-5 5L2 7",key:"x473p"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const T=c("trending-up",[["path",{d:"M16 7h6v6",key:"box55l"}],["path",{d:"m22 7-8.5 8.5-5-5L2 17",key:"1t1m79"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const V=c("truck",[["path",{d:"M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2",key:"wrbu53"}],["path",{d:"M15 18H9",key:"1lyqi6"}],["path",{d:"M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14",key:"lysw3i"}],["circle",{cx:"17",cy:"18",r:"2",key:"332jqn"}],["circle",{cx:"7",cy:"18",r:"2",key:"19iecd"}]]),N={class:"bg-white rounded-lg shadow-sm border p-6"},P={class:"flex items-center justify-between"},$={class:"text-sm font-medium text-gray-600"},z={class:"text-3xl font-semibold text-gray-900 mt-2"},O={__name:"StatCard",props:{title:{type:String,required:!0},value:{type:[String,Number],required:!0},change:{type:Number,default:void 0},icon:{type:String,required:!0},color:{type:String,default:"bg-blue-500"}},setup(e){const t=e,n=M(()=>({DollarSign:H,Package:v,ShoppingCart:D,Truck:V})[t.icon]||v);return(a,o)=>(s(),m("div",N,[r("div",P,[r("div",null,[r("p",$,d(e.title),1),r("p",z,d(e.value),1),e.change!==void 0?(s(),m("div",{key:0,class:k(["flex items-center mt-2 text-sm",e.change>=0?"text-green-600":"text-red-600"])},[e.change>=0?(s(),u(w(T),{key:0,class:"h-4 w-4 mr-1"})):(s(),u(w(L),{key:1,class:"h-4 w-4 mr-1"})),r("span",null,d(Math.abs(e.change))+"% vs last month",1)],2)):S("",!0)]),r("div",{class:k(["p-3 rounded-lg",e.color])},[(s(),u(j(n.value),{class:"h-8 w-8 text-white"}))],2)])]))}},E=Object.freeze(Object.defineProperty({__proto__:null,default:O},Symbol.toStringTag,{value:"Module"}));export{E as S,O as _,c};
