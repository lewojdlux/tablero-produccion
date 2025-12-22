import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: "pusher",
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,
    forceTLS: false,
    authEndpoint: "/tablero-produccion/broadcasting/auth",

    withCredentials: true,


    auth: {
        headers: {
            "X-CSRF-TOKEN": document
                .querySelector('meta[name=\"csrf-token\"]')
                ?.getAttribute("content"),
            "X-Requested-With": "XMLHttpRequest"
        }
    },
});
