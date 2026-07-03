// GYRE global play counter, backed by Netlify Blobs (plays.php can't run — Netlify only serves static files).
// GET  -> {"plays":N}
// POST -> increments by 1, returns {"plays":N}
import { getStore } from "@netlify/blobs";

// Real play count from before the move to Netlify/GitHub broke the old PHP+file counter.
// Used only the first time the blob store is empty, so history isn't lost.
const SEED_PLAYS = 345;

const CORS = {
  "Content-Type": "application/json; charset=utf-8",
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
  "Cache-Control": "no-store",
};

export default async (req) => {
  if (req.method === "OPTIONS") return new Response(null, { status: 204, headers: CORS });

  const store = getStore("gyre");

  if (req.method === "POST") {
    const raw = await store.get("plays");
    const cur = raw == null ? SEED_PLAYS : (parseInt(raw, 10) || 0);
    const next = cur + 1;
    await store.set("plays", String(next));
    return new Response(JSON.stringify({ plays: next }), { headers: CORS });
  }

  const raw = await store.get("plays");
  const cur = raw == null ? SEED_PLAYS : (parseInt(raw, 10) || 0);
  return new Response(JSON.stringify({ plays: cur }), { headers: CORS });
};

export const config = { path: "/plays.php" };
