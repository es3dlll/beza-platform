const CACHE = "beza-wap-v1";
const STATIC_ASSETS = [
  "/",
  "/wap/login",
  "/wap/dashboard",
  "/wap/user",
  "/wap/merchant",
  "/wap/agent",
  "/manifest.json",
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE).then((cache) => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener("fetch", (event) => {
  const { method, url } = event.request;
  const u = new URL(url);

  // API POST → Offline Queue (يُعالج في المتصفح)
  if (method === "POST" && u.pathname.includes("/api/v1/wap/wallet/transfer")) {
    event.respondWith(networkFirst(event.request));
    return;
  }

  // API GET → Network First
  if (u.pathname.includes("/api/")) {
    event.respondWith(networkFirst(event.request));
    return;
  }

  // Static assets → Cache First
  if (method === "GET") {
    event.respondWith(cacheFirst(event.request));
  }
});

async function networkFirst(request: Request): Promise<Response> {
  try {
    const res = await fetch(request);
    const cache = await caches.open(CACHE);
    cache.put(request, res.clone());
    return res;
  } catch {
    const cached = await caches.match(request);
    if (cached) return cached;
    return new Response(JSON.stringify({ success: false, error: { code: "OFFLINE", message: "غير متصل" } }), {
      status: 503,
      headers: { "Content-Type": "application/json" },
    });
  }
}

async function cacheFirst(request: Request): Promise<Response> {
  const cached = await caches.match(request);
  if (cached) return cached;

  try {
    const res = await fetch(request);
    const cache = await caches.open(CACHE);
    cache.put(request, res.clone());
    return res;
  } catch {
    return new Response("غير متصل", { status: 503 });
  }
}
