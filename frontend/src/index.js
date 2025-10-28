import React from "react";
import ReactDOM from "react-dom/client";
import "@/index.css";
import App from "@/App";

// Support both standalone and WordPress embedding
const rootElement = document.getElementById("rarebeats-player-root") || document.getElementById("root");

if (rootElement) {
  const root = ReactDOM.createRoot(rootElement);
  root.render(
    <React.StrictMode>
      <App />
    </React.StrictMode>,
  );
} else {
  console.error("RareBeats: Root element not found. Expected #rarebeats-player-root or #root");
}
