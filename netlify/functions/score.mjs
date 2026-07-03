// GYRE global high score, backed by Netlify Blobs (score.php can't run — Netlify only serves static files).
// GET  -> {"score":N,"name":"..."}
// POST score,name -> updates only if higher; returns the current record + "updated":bool
import { getStore } from "@netlify/blobs";

// Real high score from before the move to Netlify/GitHub broke the old PHP+file endpoint.
// Used only the first time the blob store is empty, so history isn't lost.
const SEED_SCORE = { score: 484, name: "LEW" };

const CORS = {
  "Content-Type": "application/json; charset=utf-8",
  "Access-Control-Allow-Origin": "*",
  "Access-Control-Allow-Methods": "GET, POST, OPTIONS",
  "Cache-Control": "no-store",
};

function sanitizeName(raw) {
  let name = String(raw || "").replace(/[^A-Za-z0-9 _.-]/g, "");
  name = name.trim().replace(/\s+/g, " ");
  if (name.length > 12) name = name.slice(0, 12);
  return name || "ANON";
}

async function currentScore(store) {
  const raw = await store.get("score", { type: "json" });
  return raw || SEED_SCORE;
}

export default async (req) => {
  if (req.method === "OPTIONS") return new Response(null, { status: 204, headers: CORS });

  const store = getStore("gyre");

  if (req.method === "POST") {
    const form = await req.formData();
    let score = parseInt(form.get("score"), 10) || 0;
    if (score < 0) score = 0;
    if (score > 100000) score = 100000; // sane cap against garbage submissions
    const name = sanitizeName(form.get("name"));

    const cur = await currentScore(store);
    if (score > cur.score) {
      const next = { score, name, ts: Date.now() };
      await store.setJSON("score", next);
      return new Response(JSON.stringify({ ...next, updated: true }), { headers: CORS });
    }
    return new Response(JSON.stringify({ ...cur, updated: false }), { headers: CORS });
  }

  const cur = await currentScore(store);
  return new Response(JSON.stringify(cur), { headers: CORS });
};

export const config = { path: "/score.php" };
