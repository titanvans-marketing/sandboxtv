document.addEventListener("DOMContentLoaded", function () {
    const accordions = document.querySelectorAll(".accordion");

    if (!accordions.length) return;

    function openPanel(button, panel) {
        button.classList.add("active");
        button.setAttribute("aria-expanded", "true");

        panel.hidden = false;
        panel.classList.add("show");

        const inner = panel.querySelector(".panel__inner");
        const height = inner ? inner.scrollHeight : panel.scrollHeight;

        panel.style.height = "0px";

        requestAnimationFrame(() => {
            panel.style.height = height + "px";
        });
    }

    function closePanel(button, panel) {
        button.classList.remove("active");
        button.setAttribute("aria-expanded", "false");

        panel.style.height = panel.scrollHeight + "px";

        requestAnimationFrame(() => {
            panel.style.height = "0px";
        });

        panel.addEventListener(
            "transitionend",
            function handleClose(e) {
                if (e.propertyName !== "height") return;

                if (button.getAttribute("aria-expanded") === "false") {
                    panel.hidden = true;
                    panel.classList.remove("show");
                }

                panel.removeEventListener("transitionend", handleClose);
            }
        );
    }

    accordions.forEach((button) => {
        const panel = button.nextElementSibling;
        if (!panel || !panel.classList.contains("panel")) return;

        button.addEventListener("click", function () {
            const isOpen = button.classList.contains("active");

            accordions.forEach((otherButton) => {
                const otherPanel = otherButton.nextElementSibling;
                if (!otherPanel || !otherPanel.classList.contains("panel")) return;

                if (otherButton !== button && otherButton.classList.contains("active")) {
                    closePanel(otherButton, otherPanel);
                }
            });

            if (isOpen) {
                closePanel(button, panel);
            } else {
                openPanel(button, panel);
            }
        });
    });

    window.addEventListener("resize", function () {
        accordions.forEach((button) => {
            const panel = button.nextElementSibling;
            if (!panel || !panel.classList.contains("panel")) return;

            if (button.classList.contains("active")) {
                const inner = panel.querySelector(".panel__inner");
                const height = inner ? inner.scrollHeight : panel.scrollHeight;
                panel.style.height = height + "px";
            }
        });
    });
});