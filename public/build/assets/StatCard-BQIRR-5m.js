import{T as h,P as r}from"./truck-D4pbb1EX.js";import{c as n}from"./createLucideIcon-DVTccj_G.js";import{j as g,d as o,o as a,a as t,e as p,t as c,n as i,c as s,u as l,a3 as y}from"./app-zdFtph8t.js";/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const k=n("dollar-sign",[["line",{x1:"12",x2:"12",y1:"2",y2:"22",key:"7eqyqh"}],["path",{d:"M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6",key:"1b0p4s"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const x=n("shopping-cart",[["circle",{cx:"8",cy:"21",r:"1",key:"jimo8o"}],["circle",{cx:"19",cy:"21",r:"1",key:"13723u"}],["path",{d:"M2.05 2.05h2l2.66 12.42a2 2 0 0 0 2 1.58h9.78a2 2 0 0 0 1.95-1.57l1.65-7.43H5.12",key:"9zh506"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const f=n("trending-down",[["path",{d:"M16 17h6v-6",key:"t6n2it"}],["path",{d:"m22 17-8.5-8.5-5 5L2 7",key:"x473p"}]]);/**
 * @license lucide-vue-next v0.540.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const v=n("trending-up",[["path",{d:"M16 7h6v6",key:"box55l"}],["path",{d:"m22 7-8.5 8.5-5-5L2 17",key:"1t1m79"}]]),b={class:"bg-white rounded-lg shadow-sm border p-6"},S={class:"flex items-center justify-between"},w={class:"text-sm font-medium text-gray-600"},C={class:"text-3xl font-semibold text-gray-900 mt-2"},B={__name:"StatCard",props:{title:{type:String,required:!0},value:{type:[String,Number],required:!0},change:{type:Number,default:void 0},icon:{type:String,required:!0},color:{type:String,default:"bg-blue-500"}},setup(e){const d=e,u=g(()=>({DollarSign:k,Package:r,ShoppingCart:x,Truck:h})[d.icon]||r);return(m,M)=>(a(),o("div",b,[t("div",S,[t("div",null,[t("p",w,c(e.title),1),t("p",C,c(e.value),1),e.change!==void 0?(a(),o("div",{key:0,class:i(["flex items-center mt-2 text-sm",e.change>=0?"text-green-600":"text-red-600"])},[e.change>=0?(a(),s(l(v),{key:0,class:"h-4 w-4 mr-1"})):(a(),s(l(f),{key:1,class:"h-4 w-4 mr-1"})),t("span",null,c(Math.abs(e.change))+"% vs last month",1)],2)):p("",!0)]),t("div",{class:i(["p-3 rounded-lg",e.color])},[(a(),s(y(u.value),{class:"h-8 w-8 text-white"}))],2)])]))}};export{B as default};
