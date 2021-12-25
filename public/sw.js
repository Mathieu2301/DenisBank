/* eslint-disable no-restricted-globals */

self.addEventListener('fetch', (event) => {
  event.respondWith(fetch(event.request));
});
