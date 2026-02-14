import "./bootstrap";
import React from "react";
import { createRoot } from "react-dom/client";
import UserCreatePage from "./components/user-create-page";

const container = document.getElementById("app");

if (container) {
  createRoot(container).render(
    <React.StrictMode>
      <UserCreatePage />
    </React.StrictMode>,
  );
}
