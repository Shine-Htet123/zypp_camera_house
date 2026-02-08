const profileSection = document.querySelector("#profile");
const editBtn = document.querySelector("#profile-edit-btn");
const cancelBtn = document.querySelector("#profile-cancel-btn");

if (profileSection && editBtn) {
    editBtn.addEventListener("click", () => {
        profileSection.classList.toggle("editing");
    });
}

if (profileSection && cancelBtn) {
    cancelBtn.addEventListener("click", () => {
        profileSection.classList.remove("editing");
    });
}

const trackForm = document.querySelector("#track-form");
const trackResult = document.querySelector("#track-result");
const closeTrackResult = document.querySelector("#close-track-result");


if (trackForm && trackResult) {
    trackForm.addEventListener("submit", (event) => {
        event.preventDefault();
        trackResult.classList.remove("hidden");
        trackResult.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    if (closeTrackResult) {
        closeTrackResult.addEventListener("click", () => {
            trackResult.classList.add("hidden");
            trackForm.scrollIntoView({ behavior: "smooth", block: "start" });
        });
    }
}
