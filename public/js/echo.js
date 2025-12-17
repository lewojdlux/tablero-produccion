import Echo from "https://cdn.skypack.dev/laravel-echo";
import Pusher from "https://cdn.skypack.dev/pusher-js";

window.Pusher = Pusher;

// CONFIGURA TU PUSHER
window.Echo = new Echo({
    broadcaster: "pusher",
    key: "{{ env('PUSHER_APP_KEY') }}",
    cluster: "{{ env('PUSHER_APP_CLUSTER') }}",
    forceTLS: true,
});

console.log("Echo cargado correctamente");
