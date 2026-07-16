import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

/**
 * Auth private channel (routes/channels.php) lewat /broadcasting/auth.
 * withCredentials: true — pakai cookie session Sanctum SPA yang sama
 * kayak axios client di api/client.js, bukan token terpisah.
 */
let echoInstance = null

export function getEcho() {
  if (echoInstance) return echoInstance

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: '/broadcasting/auth',
    auth: {
      withCredentials: true,
    },
  })

  return echoInstance
}

/**
 * Subscribe ke channel private outlet tertentu, dengarin OrderStatusUpdated.
 * Return unsubscribe function — panggil ini di onUnmounted biar gak leak listener.
 */
export function listenOrderUpdates(outletId, callback) {
  const echo = getEcho()
  const channel = echo.private(`outlet.${outletId}`)
  channel.listen('.OrderStatusUpdated', callback)

  return () => {
    channel.stopListening('.OrderStatusUpdated')
    echo.leave(`outlet.${outletId}`)
  }
}
