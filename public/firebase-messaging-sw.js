importScripts('https://www.gstatic.com/firebasejs/5.8.2/firebase-app.js');
importScripts('https://www.gstatic.com/firebasejs/5.8.2/firebase-messaging.js');

firebase.initializeApp({ messagingSenderId: '273479070895' });

const messaging = firebase.messaging();

messaging.setBackgroundMessageHandler((payload) => {
  self.registration.showNotification(payload.data.title, {
    body: payload.data.body,
    renotify: true,
    icon: 'logos/logo192.png',
    vibrate: [100, 50, 100],
    sound: 'default',
    tag: Math.round(Math.random() * 1000),
  });
});
