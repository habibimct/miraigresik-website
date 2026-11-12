/*************************************************************************
 * upload-core.js
 * LPK MIRAI GRESIK
 * Core Upload Engine
 *************************************************************************/

const UploadCore = {

    compressedFiles: {},

    config: {},

    IMAGE_TYPES: [
        "image/jpeg",
        "image/png",
        "image/jpg",
        "image/webp"
    ],

    PDF_TYPES: [
        "application/pdf"
    ],

    MAX_IMAGE_SIZE: 20 * 1024 * 1024,

    MAX_PDF_SIZE: 10 * 1024 * 1024

};

UploadCore.formatSize = function (bytes) {

    if (bytes < 1024) {
        return bytes + " B";
    }

    if (bytes < 1024 * 1024) {
        return (bytes / 1024).toFixed(1) + " KB";
    }

    return (bytes / 1024 / 1024).toFixed(2) + " MB";

};

UploadCore.showInfo = function (input, text, color = "green") {

    let info = input.parentNode.querySelector(".upload-info");

    if (!info) {

        info = document.createElement("small");
        info.className = "upload-info";
        input.parentNode.appendChild(info);

    }

    info.style.display = "block";
    info.style.color = color;
    info.innerHTML = text;

};

UploadCore.compressImage = async function (file) {

    if (!file.type.startsWith("image/")) {
        return file;
    }

    return new Promise((resolve) => {

        new Compressor(file, {

            quality: 0.75,
            maxWidth: 1600,
            maxHeight: 1600,

            success(result) {

                resolve(
                    new File(
                        [result],
                        file.name,
                        {
                            type: "image/jpeg",
                            lastModified: Date.now()
                        }
                    )
                );

            },

            error() {

                resolve(file);

            }
        });
    });
};

/*************************************************************************
 * Update Summary
 *************************************************************************/

UploadCore.updateSummary = function () {

    if (!this.config.summary) {
        return;
    }

    let html = "";
    let total = 0;
    let adaFile = false;

    Object.keys(this.config.files).forEach((field) => {

        if (this.compressedFiles[field]) {

            adaFile = true;

            total += this.compressedFiles[field].size;

            html += `
            <tr data-file="${field}">
                <td>
                    ${this.config.files[field]}
                </td>

                <td class="text-end text-success">
                    ${this.formatSize(this.compressedFiles[field].size)}
                </td>

                <td class="summary-status text-primary">
                    Siap
                </td>
            </tr>
        `;

        } else {

            html += `
            <tr data-file="${field}">
                <td>
                    ${this.config.files[field]}
                </td>

                <td class="text-end text-secondary">
                    -
                </td>

                <td class="summary-status text-secondary">
                    Belum
                </td>
            </tr>
        `;
        }
    });

    if (!adaFile) {
        $(this.config.ui.summaryCard).hide();

        return;
    }

    $(this.config.ui.summaryCard).show();
    $(this.config.ui.summaryList).html(html);
    $(this.config.ui.summaryTotal).text(
        this.formatSize(total)
    );
};

/*************************************************************************
 * Update Status Summary
 *************************************************************************/

UploadCore.setStatus = function (field, className, text) {

    const row = $(this.config.ui.summaryList)
        .find(`tr[data-file="${field}"]`);

    const status = row.find(".summary-status");

    status.removeClass(
        "text-secondary text-primary text-warning text-success text-danger"
    );

    status.addClass(className);
    status.text(text);
};

/*************************************************************************
 * Prepare File
 *************************************************************************/

UploadCore.prepareFile = async function (input) {

    if (input.files.length === 0) {
        return;
    }

    const file = input.files[0];

    if (
        !this.IMAGE_TYPES.includes(file.type) &&
        !this.PDF_TYPES.includes(file.type)
    ) {

        input.value = "";
        delete this.compressedFiles[input.name];
        this.showInfo(
            input,
            "❌ Format file tidak didukung",
            "red"
        );

        this.updateSummary();

        return;
    }

    if (
        this.IMAGE_TYPES.includes(file.type) &&
        file.size > this.MAX_IMAGE_SIZE
    ) {

        input.value = "";
        delete this.compressedFiles[input.name];
        this.showInfo(
            input,
            "❌ Maksimal 20 MB",
            "red"
        );

        this.updateSummary();

        return;
    }

    if (
        this.PDF_TYPES.includes(file.type)
    ) {

        this.compressedFiles[input.name] = file;
        this.showInfo(
            input,
            "✔ PDF : " + this.formatSize(file.size),
            "#00cc66"
        );

        this.updateSummary();
        this.setStatus(
            input.name,
            "text-primary",
            "Siap"
        );

        return;
    }

    this.showInfo(
        input,
        "Mengompres gambar...",
        "orange"
    );

    const hasil = await this.compressImage(file);

    this.compressedFiles[input.name] = hasil;

    const persen = (
        ((file.size - hasil.size) / file.size) * 100
    ).toFixed(0);

    this.showInfo(

        input,

        "✔ "
        + this.formatSize(file.size)
        + " → "
        + this.formatSize(hasil.size)
        + " (hemat "
        + persen
        + "%)",

        "#00cc66"

    );

    this.updateSummary();
    this.setStatus(
        input.name,
        "text-primary",
        "Siap"
    );
};

/*************************************************************************
 * Submit Form
 *************************************************************************/

UploadCore.submit = function (form) {

    const data = new FormData();

    $(form).find(":input").each((i, element) => {

        if (!element.name) return;

        if (element.type === "file") {
            if (this.compressedFiles[element.name]) {
                data.append(
                    element.name,
                    this.compressedFiles[element.name],
                    this.compressedFiles[element.name].name
                );
            }
        }
        else if (
            element.type !== "submit" &&
            element.type !== "button"
        ) {
            data.append(
                element.name,
                $(element).val()
            );
        }
    });

    const submitButton = $(this.config.submitButton);

    submitButton.prop("disabled", true);

    $(this.config.ui.progressBar).show().val(0);
    $(this.config.ui.progressText).text("Upload 0%");
    $(this.config.ui.loading).hide();

    Swal.fire({

        title: "Mengirim Data",
        html: "Mohon tunggu...",

        allowOutsideClick: false,

        didOpen() {
            Swal.showLoading();
        }

    });

    $.ajax({

        url: this.config.php,
        type: "POST",
        data: data,
        processData: false,
        contentType: false,
        cache: false,

        xhr: () => {

            const xhr = new window.XMLHttpRequest();

            xhr.upload.addEventListener("progress", (evt) => {

                if (evt.lengthComputable) {
                    const persen = Math.round(
                        evt.loaded / evt.total * 100
                    );

                    $(this.config.ui.progressBar).val(persen);
                    $(this.config.ui.progressText).text(
                        "Upload " + persen + "%"
                    );

                    if (persen === 100) {

                        $(this.config.ui.loading).show();
                        $(this.config.ui.progressText).text(
                            "Mohon tunggu... Mengirim email..."
                        );
                    }
                }
            });

            return xhr;
        },

        success: (response) => {

            Swal.close();
            
            // Khusus Trial Class
            if (response.trim() === "TRIAL_CLASS") {
            
                $(this.config.ui.loading).hide();
                $(this.config.ui.progressText).text("Selesai");
            
                // Reset form
                form.reset();
                UploadCore.clearStorage();
                UploadCore.reset(true);
            
                Swal.fire({
                    icon: "success",
                    title: "Pendaftaran Berhasil",
                    text: "Silakan bergabung ke Grup WhatsApp Trial Class.",
                    confirmButtonText: "Gabung Grup"
                }).then(() => {
                    window.location.href = "https://chat.whatsapp.com/JoqQzmvORCjIMgPugJTx8i";
                });
            
                return;
            }

            $(this.config.ui.loading).hide();
            $(this.config.ui.progressText).text("Selesai");

            Swal.fire({
                icon: "success",
                title: "Berhasil",
                html:
                    "Data telah berhasil dikirim ke <b>LPK Mirai Gresik</b>.<br><br>" +
                    "Apakah data ini ingin tetap disimpan di perangkat ini?",
                showCancelButton: true,
                confirmButtonText: "💾 Ya, Simpan",
                cancelButtonText: "🗑️ Hapus"
            }).then((result) => {
                if (result.isConfirmed) {
                    UploadCore.reset(false);
                } else {
                    UploadCore.clearStorage();
                    UploadCore.reset(true);
                }
            });

            form.reset();

            const storage = this.getStorage();

            if (storage) {
                storage.status = "submitted";
                storage.submittedAt = new Date().toISOString();
                localStorage.setItem(
                    this.config.storageKey,
                    JSON.stringify(storage)
                );
            }
        },

        error: (xhr) => {

            Swal.close();

            $(this.config.ui.loading).hide();
            $(this.config.ui.progressText).text("Terjadi Kesalahan");

            Swal.fire({

                icon: "error",
                title: "Upload Gagal",
                text: xhr.responseText

            });
        },

        complete: () => {
            submitButton.prop("disabled", false);
        }

    });

};

/*************************************************************************
 * Reset Form
 *************************************************************************/

UploadCore.reset = function (clearStorage = true) {

    if (clearStorage) {
        this.clearStorage();
    }

    Swal.fire({

        icon: "success",
        title: "Berhasil",
        text: "Form berhasil dikirim.",
        timer: 1800,
        showConfirmButton: false

    });

    Object.keys(this.compressedFiles).forEach((key) => {

        delete this.compressedFiles[key];

    });

    $(".upload-info").remove();
    $(this.config.ui.summaryCard).hide();
    $(this.config.ui.summaryList).html("");
    $(this.config.ui.summaryTotal).text("0 KB");

    this.updateSummary();

};

/*************************************************************************
 * Auto Save
 *************************************************************************/

UploadCore.saveForm = function () {

    if (!this.config.autoSave) return;

    const data = {};

    $(this.config.form)
        .find(":input")
        .each(function () {

            if (!this.name) return;
            if (this.type === "file") return;
            if (this.type === "submit") return;
            if (this.type === "button") return;
            if (this.type === "checkbox") {

                data[this.name] = this.checked;

                return;

            }

            if (this.type === "radio") {

                if (this.checked) {
                    data[this.name] = this.value;
                }

                return;
            }

            data[this.name] = $(this).val();
        });

    const storage = {
        status: "draft",
        savedAt: new Date().toISOString(),
        data: data
    };

    localStorage.setItem(
        this.config.storageKey,
        JSON.stringify(storage)
    );

};

/*************************************************************************
 * Load Form
 *************************************************************************/

UploadCore.loadForm = function () {

    if (!this.config.autoSave) return;

    const json = localStorage.getItem(
        this.config.storageKey
    );

    if (!json) return;

    const storage = JSON.parse(json);
    const data = storage.data || {};

    Object.keys(data).forEach((name) => {

        const input = $(this.config.form).find("[name='" + name + "']");

        if (!input.length) return;

        const el = input[0];

        if (el.type === "checkbox") {
            el.checked = data[name];
            return;
        }

        if (el.type === "radio") {
            input.each(function () {
                this.checked = this.value === data[name];
            });

            return;
        }

        input.val(data[name]);
    });
};

/*************************************************************************
 * Get Storage
 *************************************************************************/

UploadCore.getStorage = function () {

    const json = localStorage.getItem(
        this.config.storageKey
    );

    if (!json) {
        return null;
    }

    return JSON.parse(json);
};

/*************************************************************************
 * Cek Draft
 *************************************************************************/

UploadCore.hasDraft = function () {

    if (!this.config.autoSave) {
        return false;
    }

    return localStorage.getItem(
        this.config.storageKey
    ) !== null;

};

/*************************************************************************
 * Clear Storage
 *************************************************************************/

UploadCore.clearStorage = function () {

    if (!this.config.autoSave) return;

    localStorage.removeItem(
        this.config.storageKey
    );

};

/*************************************************************************
 * Init
 *************************************************************************/

UploadCore.init = function (config) {

    this.config = {

        autoSave: false,
        storageKey: "uploadForm",
        summary: true,

        ...config

    };

    $(document).ready(() => {

        /*************************************************************************
         * Konfirmasi Draft
         *************************************************************************/

        if (this.hasDraft()) {

            Swal.fire({

                icon: "question",
                title: "Lanjutkan Pengisian?",
                html:
                    "Ditemukan data formulir yang belum selesai.<br><br>" +
                    "Apakah ingin melanjutkan pengisian?",
                confirmButtonText: "Lanjutkan",
                cancelButtonText: "Mulai Baru",
                showCancelButton: true,
                allowOutsideClick: false

            }).then((result) => {

                if (result.isConfirmed) {
                    this.loadForm();
                } else {
                    this.clearStorage();
                    $(this.config.form)[0].reset();
                }
            });
        }

        // ===============================
        // Upload file
        // ===============================
        $("input[type=file]").change((e) => {
            this.prepareFile(e.target);
        });

        // ===============================
        // Auto Save setiap perubahan
        // ===============================
        $(this.config.form).on("input change", ":input", () => {
            this.saveForm();
        });

        // ===============================
        // Summary upload
        // ===============================
        this.updateSummary();

        // ===============================
        // Submit
        // ===============================
        $(this.config.form).submit((e) => {
            e.preventDefault();
            this.submit(e.target);
        });

    });
};