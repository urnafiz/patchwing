document.addEventListener("DOMContentLoaded", () => {
    const container = document.querySelector(".db-table-container");
    if (!container) return;

    const ajaxurl = container.dataset.ajaxurl;
    const nonce = container.dataset.nonce;

    const spinner = document.getElementById("spinner");
    const successBox = document.getElementById("successBox");

    function showSpinner() {
        spinner.style.display = "block";
    }

    function hideSpinner() {
        spinner.style.display = "none";
    }

    function formatFilename() {
        const d = new Date();
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        let hours = d.getHours();
        const minutes = String(d.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        return `db-backup-${yyyy}-${mm}-${dd}_${hours}-${minutes}-${ampm}.sql`;
    }


    function ajaxRequest(actionType, table = "") {
        showSpinner();
        const data = new URLSearchParams();
        data.append("action", "patchwing_db_table_actions");
        data.append("action_type", actionType);
        data.append("_wpnonce", nonce);
        if (table) data.append("table", table);

        fetch(ajaxurl, {
                method: "POST",
                body: data
            })
            .then(response => {
                hideSpinner();
                if (actionType === "export_backup") {
                    return response.blob();
                }
                return response.json();
            })
            .then(result => {
                if (actionType === "export_backup") {
                    const url = window.URL.createObjectURL(result);
                    const a = document.createElement("a");
                    a.href = url;
                    a.download = formatFilename();
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    return;
                }
                if (result.success) {
                    successBox.innerText = result.data;
                    successBox.style.display = "block";
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    alert(result.data || "Error occurred.");
                }
            })
            .catch(err => {
                hideSpinner();
                alert("Error: " + err);
            });
    }

    // Button handlers
    document.getElementById("btn-backup")?.addEventListener("click", () => {
        ajaxRequest("export_backup");
    });

    document.getElementById("btn-convert-all")?.addEventListener("click", () => {
        ajaxRequest("convert_all");
    });

    document.querySelectorAll(".convert-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const tableName = btn.dataset.table;
            ajaxRequest("convert_innodb", tableName);
        });
    });
});