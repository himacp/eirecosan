document.addEventListener("DOMContentLoaded", function() {
    // Load Header
    fetch("header.html")
        .then(response => response.text())
        .then(data => {
            document.querySelector("header").innerHTML = data;
            // Hamburger Menu Functionality
            const hamburger = document.querySelector(".hamburger-menu");
            const navLinks = document.querySelector(".nav-links");
            hamburger.addEventListener("click", () => {
                navLinks.classList.toggle("active");
            });
        });

    // Load Footer
    fetch("footer.html").then(response => response.text()).then(data => document.querySelector("footer").innerHTML = data);
});