window.addEventListener("DOMContentLoaded", () => {
  const logoutButton = document.querySelector("#logout-button");
  if (logoutButton) {
    logoutButton.addEventListener("click", () => {
      window.location.href = "/admin/logout";
    });
  }
});
