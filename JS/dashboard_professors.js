document.addEventListener("DOMContentLoaded", function () {
    // Real-time Date Update
    function updateDateTime() {
        const dateElement = document.getElementById("real-time-date");
        const now = new Date();
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        dateElement.innerText = now.toLocaleDateString("en-US", options);
    }
    updateDateTime();
    setInterval(updateDateTime, 60000); // Update every minute
    
    // Sidebar Toggle
    const menuToggle = document.getElementById("menu-toggle");
    const sidebar = document.querySelector(".sidebar");
    const mainContent = document.querySelector(".main-content");

    menuToggle.addEventListener("click", function () {
        sidebar.classList.toggle("active");
    });

    // Close sidebar when clicking outside
    document.addEventListener("click", function (event) {
        if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
            sidebar.classList.remove("active");
        }
    });

    // Ensure content resizes when sidebar is toggled
    const adjustContent = () => {
        if (sidebar.classList.contains("active")) {
            mainContent.style.marginLeft = "250px";
        } else {
            mainContent.style.marginLeft = "0";
        }
    };
    
    menuToggle.addEventListener("click", adjustContent);
    adjustContent(); // Run on page load
});
