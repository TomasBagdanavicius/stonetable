"use strict";openInPlayground.addEventListener("click",e=>{e.preventDefault(),fetch(e.target).then(e=>e.json()).then(e=>{open(e.data.IdeUri,"_self")}).catch(e=>console.log(e))});