import React from "react";
import ReactDOM from "react-dom/client";
import "@/index.css";
import App from "@/App";

// Function to initialize the app
function initializeApp() {
  // Support both standalone and WordPress embedding
  const rootElement = document.getElementById("rarebeats-player-root") || document.getElementById("root");

  if (rootElement) {
    console.log("RareBeats: Mounting React app to", rootElement.id);
    const root = ReactDOM.createRoot(rootElement);
    root.render(
      <React.StrictMode>
        <App />
      </React.StrictMode>,
    );
  } else {
    console.error("RareBeats: Root element not found. Expected #rarebeats-player-root or #root");
    // Retry after a short delay (for WordPress async loading)
    setTimeout(() => {
      const retryElement = document.getElementById("rarebeats-player-root") || document.getElementById("root");
      if (retryElement) {
        console.log("RareBeats: Found element on retry, mounting...");
        const root = ReactDOM.createRoot(retryElement);
        root.render(
          <React.StrictMode>
            <App />
          </React.StrictMode>,
        );
      } else {
        console.error("RareBeats: Still cannot find root element after retry");
      }
    }, 500);
  }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeApp);
} else {
  initializeApp();
}
