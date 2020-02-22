import firebase from 'firebase/app';
import 'firebase/firebase-messaging';

export default function load(cb) {
  firebase.initializeApp({
    apiKey: 'AIzaSyBR7DCsR_W3C3OHY8QRpTkQXk8Pcd7Do_E',
    authDomain: 'iridium-blast.firebaseapp.com',
    databaseURL: 'https://iridium-blast.firebaseio.com',
    projectId: 'iridium-blast',
    storageBucket: 'iridium-blast.appspot.com',
    messagingSenderId: '273479070895',
    appId: '1:273479070895:web:5fcd60ddde3485c7f6d05a',
  });

  const messaging = firebase.messaging();

  messaging.requestPermission().then(() => {
    messaging.getToken().then(cb);
  });
}
