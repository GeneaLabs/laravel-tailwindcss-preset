global._ = require("lodash");

global.fontawesome = require("@fortawesome/fontawesome");
var fa-brands = require("@fortawesome/fontawesome-free-brands");
var fa-regular = require("@fortawesome/fontawesome-free-regular");
var fa-solid = require("@fortawesome/fontawesome-free-regular");

fontawesome.library.add(fa-brands, fa-regular, fa-solid);

global.Vue = require("vue");

global.axios = require("axios");
global.axios.defaults.headers.common = {
    "X-CSRF-TOKEN": window.Laravel.csrfToken,
    "X-Requested-With": "XMLHttpRequest"
};

// import Echo from "laravel-echo"
//
// window.Echo = new Echo({
//     broadcaster: "socket.io",
//     host: window.location.hostname + ":6001"
// });
