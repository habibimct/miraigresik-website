/*************************************************************************
 * upload-core.js
 * LPK MIRAI GRESIK
 * Core Upload Engine - revised checkbox, autosave and upload handling
 *************************************************************************/

const UploadCore = {
    compressedFiles: {},
    config: {},
    pendingFileTasks: new Map(),
    fileTaskIds: {},

    IMAGE_TYPES: [
        "image/jpeg",
        "image/png",
        "image/jpg",
        "image/webp"
    ],

    PDF_TYPES: ["application/pdf"],

    MAX_IMAGE_SIZE: 20 * 1024 * 1024,
    MAX_PDF_SIZE: 10 * 1024 * 1024,

    DRAFT_VERSION: 2
};

/*************************************************************************
 * Helpers
 *************************************************************************/

UploadCore.formatSize = function (bytes) {
    if (bytes < 1024) return bytes + " B";
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / 1024 / 1024).toFixed(2) + " MB";
};

UploadCore.getForm = function () {
    return document.querySelector(this.config.form);
};

UploadCore.showInfo = function (input, message, color = "green") {
    if (!input || !input.parentNode) return;

    let info = input.__uploadCoreInfo;
    if (!info || !info.isConnected) {
        info = document.createElement("small");
        info.className = "upload-info d-block mt-1";
        info.setAttribute("data-upload-core", "true");
        input.parentNode.appendChild(info);
        input.__uploadCoreInfo = info;
    }

    // Use textContent: file names and messages must not be interpreted as HTML.
    info.textContent = message;
    info.style.display = "block";
    info.style.color = color;
};

UploadCore.removeInfo = function (input) {
    if (input && input.__uploadCoreInfo) {
        input.__uploadCoreInfo.remove();
        input.__uploadCoreInfo = null;
    }
};

UploadCore.clearUploadState = function () {
    Object.keys(this.compressedFiles).forEach((key) => {
        delete this.compressedFiles[key];
    });

    this.pendingFileTasks.clear();
    this.fileTaskIds = {};

    document.querySelectorAll(".upload-info[data-upload-core]").forEach((node) => node.remove());

    if (this.config.ui) {
        $(this.config.ui.summaryCard).hide();
        $(this.config.ui.summaryList).empty();
        $(this.config.ui.summaryTotal).text("0 KB");
        $(this.config.ui.progressBar).hide().val(0);
        $(this.config.ui.progressText).text("");
        $(this.config.ui.loading).hide();
    }
};

/*************************************************************************
 * Image Compression
 *************************************************************************/

UploadCore.compressImage = async function (file) {
    if (!file.type.startsWith("image/") || typeof Compressor === "undefined") {
        return file;
    }

    return new Promise((resolve) => {
        try {
            new Compressor(file, {
                quality: 0.75,
                maxWidth: 1600,
                maxHeight: 1600,
                success(result) {
                    resolve(new File([result], file.name, {
                        type: "image/jpeg",
                        lastModified: Date.now()
                    }));
                },
                error() {
                    // Do not lose the original file if compression fails.
                    resolve(file);
                }
            });
        } catch (error) {
            console.error("Image compression error:", error);
            resolve(file);
        }
    });
};

/*************************************************************************
 * Upload Summary
 *************************************************************************/

UploadCore.updateSummary = function () {
    if (!this.config.summary || !this.config.ui) return;

    const list = document.querySelector(this.config.ui.summaryList);
    const card = document.querySelector(this.config.ui.summaryCard);
    const totalNode = document.querySelector(this.config.ui.summaryTotal);
    if (!list || !card || !totalNode) return;

    list.replaceChildren();

    let total = 0;
    let hasFile = false;

    Object.keys(this.config.files || {}).forEach((field) => {
        const file = this.compressedFiles[field];
        const row = document.createElement("tr");
        row.setAttribute("data-file", field);

        const nameCell = document.createElement("td");
        nameCell.textContent = this.config.files[field];

        const sizeCell = document.createElement("td");
        sizeCell.className = "text-end";

        const statusCell = document.createElement("td");
        statusCell.className = "summary-status";

        if (file) {
            hasFile = true;
            total += file.size;
            sizeCell.classList.add("text-success");
            sizeCell.textContent = this.formatSize(file.size);
            statusCell.classList.add("text-primary");
            statusCell.textContent = "Siap";
        } else {
            sizeCell.classList.add("text-secondary");
            sizeCell.textContent = "-";
            statusCell.classList.add("text-secondary");
            statusCell.textContent = "Belum";
        }

        row.append(nameCell, sizeCell, statusCell);
        list.appendChild(row);
    });

    card.style.display = hasFile ? "block" : "none";
    totalNode.textContent = this.formatSize(total);
};

UploadCore.setStatus = function (field, className, text) {
    const list = document.querySelector(this.config.ui.summaryList);
    if (!list) return;

    const row = Array.from(list.querySelectorAll("tr[data-file]"))
        .find((item) => item.getAttribute("data-file") === field);
    if (!row) return;

    const status = row.querySelector(".summary-status");
    if (!status) return;

    status.classList.remove(
        "text-secondary", "text-primary", "text-warning", "text-success", "text-danger"
    );
    status.classList.add(className);
    status.textContent = text;
};

/*************************************************************************
 * Prepare File
 *************************************************************************/

UploadCore.handleFileChange = function (input) {
    const field = input.name || input.id;
    if (!field) return Promise.resolve();

    const taskId = (this.fileTaskIds[field] || 0) + 1;
    this.fileTaskIds[field] = taskId;

    const task = this.prepareFile(input, taskId)
        .catch((error) => {
            console.error("File preparation error:", error);
            if (this.fileTaskIds[field] === taskId) {
                delete this.compressedFiles[field];
                this.showInfo(input, "Gagal memproses file. Silakan pilih ulang.", "red");
                this.updateSummary();
            }
        })
        .finally(() => {
            if (this.pendingFileTasks.get(field) === task) {
                this.pendingFileTasks.delete(field);
            }
        });

    this.pendingFileTasks.set(field, task);
    return task;
};

UploadCore.prepareFile = async function (input, taskId) {
    const field = input.name || input.id;
    if (!field) return;

    if (!input.files || input.files.length === 0) {
        delete this.compressedFiles[field];
        this.removeInfo(input);
        this.updateSummary();
        this.setStatus(field, "text-secondary", "Belum");
        return;
    }

    const file = input.files[0];
    const isImage = this.IMAGE_TYPES.includes(file.type);
    const isPdf = this.PDF_TYPES.includes(file.type);

    if (!isImage && !isPdf) {
        input.value = "";
        delete this.compressedFiles[field];
        this.showInfo(input, "Format file tidak didukung. Gunakan JPG, PNG, WEBP, atau PDF.", "red");
        this.updateSummary();
        this.setStatus(field, "text-danger", "Format salah");
        return;
    }

    if (isImage && file.size > this.MAX_IMAGE_SIZE) {
        input.value = "";
        delete this.compressedFiles[field];
        this.showInfo(input, "Ukuran gambar maksimal 20 MB.", "red");
        this.updateSummary();
        this.setStatus(field, "text-danger", "Terlalu besar");
        return;
    }

    if (isPdf && file.size > this.MAX_PDF_SIZE) {
        input.value = "";
        delete this.compressedFiles[field];
        this.showInfo(input, "Ukuran PDF maksimal 10 MB.", "red");
        this.updateSummary();
        this.setStatus(field, "text-danger", "Terlalu besar");
        return;
    }

    if (isPdf) {
        if (this.fileTaskIds[field] !== taskId) return;
        this.compressedFiles[field] = file;
        this.showInfo(input, "PDF siap: " + this.formatSize(file.size), "#00a65a");
        this.updateSummary();
        this.setStatus(field, "text-primary", "Siap");
        return;
    }

    this.showInfo(input, "Mengompres gambar...", "#b7791f");
    const result = await this.compressImage(file);

    // Ignore a stale compression result if the user changed the file meanwhile.
    if (this.fileTaskIds[field] !== taskId) return;

    this.compressedFiles[field] = result;
    const savedPercent = file.size > 0
        ? Math.round(((file.size - result.size) / file.size) * 100)
        : 0;

    const message = result === file
        ? "Gambar siap (tanpa kompresi): " + this.formatSize(result.size)
        : "Gambar siap: " + this.formatSize(file.size) + " → " +
          this.formatSize(result.size) + " (hemat " + savedPercent + "%)";

    this.showInfo(input, message, "#00a65a");
    this.updateSummary();
    this.setStatus(field, "text-primary", "Siap");
};

/*************************************************************************
 * Form Data Builder
 *************************************************************************/

UploadCore.buildFormData = function (form) {
    // Native FormData automatically excludes unchecked checkboxes/radios,
    // and correctly preserves repeated names such as bahasa[] and keluarga[].
    const data = new FormData(form);

    // Replace original selected files with compressed versions when available.
    form.querySelectorAll('input[type="file"][name]').forEach((input) => {
        data.delete(input.name);

        const preparedFile = this.compressedFiles[input.name];
        const originalFile = input.files && input.files.length ? input.files[0] : null;
        const file = preparedFile || originalFile;

        if (file) {
            data.append(input.name, file, file.name);
        }
    });

    return data;
};

/*************************************************************************
 * Submit Form
 *************************************************************************/

UploadCore.submit = async function (form) {
    if (this.isSubmitting) return;

    // Respect HTML required fields before custom validation.
    if (typeof form.reportValidity === "function" && !form.reportValidity()) {
        return;
    }

    // Wait for image compression/file preparation to finish.
    if (this.pendingFileTasks.size > 0) {
        await Promise.all(Array.from(this.pendingFileTasks.values()));
    }

    if (typeof this.config.beforeSubmit === "function") {
        const isValid = this.config.beforeSubmit(form);
        if (isValid === false) return;
    }

    // Save the latest form values before sending. Files themselves are not
    // stored in localStorage; they must be selected again after a page reload.
    this.saveForm();

    const data = this.buildFormData(form);
    const submitButton = $(this.config.submitButton);

    this.isSubmitting = true;
    submitButton.prop("disabled", true);

    $(this.config.ui.progressBar).show().val(0);
    $(this.config.ui.progressText).text("Upload 0%");
    $(this.config.ui.loading).hide();

    Swal.fire({
        title: "Mengirim Data",
        html: "Mohon tunggu...",
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen() {
            Swal.showLoading();
        }
    });

    $.ajax({
        url: this.config.php,
        type: "POST",
        data,
        processData: false,
        contentType: false,
        cache: false,
        dataType: "text",

        xhr: () => {
            const xhr = new window.XMLHttpRequest();
            if (xhr.upload) {
                xhr.upload.addEventListener("progress", (event) => {
                    if (!event.lengthComputable) return;

                    const percent = Math.round((event.loaded / event.total) * 100);
                    $(this.config.ui.progressBar).val(percent);
                    $(this.config.ui.progressText).text("Upload " + percent + "%");

                    if (percent >= 100) {
                        $(this.config.ui.loading).show();
                        $(this.config.ui.progressText).text("Mohon tunggu... Mengirim email...");
                    }
                });
            }
            return xhr;
        },

        success: (response) => {
            const responseText = String(response).trim();

            if (this.config.successResponse &&
                responseText !== this.config.successResponse &&
                responseText !== "TRIAL_CLASS") {
                Swal.close();
                Swal.fire({
                    icon: "error",
                    title: "Respons server tidak dikenali",
                    text: "Server mengembalikan respons yang tidak sesuai. Periksa log server sebelum mengirim ulang."
                });
                return;
            }

            Swal.close();
            $(this.config.ui.loading).hide();
            $(this.config.ui.progressText).text("Selesai");

            if (responseText === "TRIAL_CLASS") {
                form.reset();
                if (typeof this.config.onFormReset === "function") {
                    this.config.onFormReset(form);
                }
                this.clearStorage();
                this.clearUploadState();

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

            const storage = this.getStorage();
            if (storage) {
                storage.status = "submitted";
                storage.submittedAt = new Date().toISOString();
                this.writeStorage(storage);
            }

            form.reset();
            if (typeof this.config.onFormReset === "function") {
                this.config.onFormReset(form);
            }
            this.clearUploadState();

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
                if (!result.isConfirmed) {
                    this.clearStorage();
                }
            });
        },

        error: (xhr) => {
            Swal.close();
            $(this.config.ui.loading).hide();
            $(this.config.ui.progressText).text("Terjadi Kesalahan");

            const responseMessage = String(xhr.responseText || "").trim();
            if (responseMessage) {
                // Keep detailed server responses in the browser console only.
                console.error("Server response:", responseMessage);
            }

            const safeMessage = this.config.showServerError && responseMessage
                ? responseMessage.replace(/<[^>]*>/g, " ").slice(0, 500)
                : (this.config.errorMessage ||
                   "Pengiriman gagal. Data formulir tetap tersedia. Silakan coba lagi atau hubungi admin LPK Mirai.");

            Swal.fire({
                icon: "error",
                title: "Pengiriman Gagal",
                text: safeMessage
            });
        },

        complete: () => {
            this.isSubmitting = false;
            submitButton.prop("disabled", false);
        }
    });
};

/*************************************************************************
 * Reset Upload State
 *************************************************************************/

UploadCore.reset = function (clearStorage = true, showMessage = false) {
    if (clearStorage) this.clearStorage();
    this.clearUploadState();

    if (showMessage) {
        Swal.fire({
            icon: "success",
            title: "Berhasil",
            text: "Form berhasil direset.",
            timer: 1500,
            showConfirmButton: false
        });
    }
};

/*************************************************************************
 * Auto Save
 *************************************************************************/

UploadCore.saveForm = function () {
    if (!this.config.autoSave) return;

    const form = this.getForm();
    if (!form) return;

    const controls = Array.from(form.elements).filter((element) => {
        if (!element.name || element.disabled) return false;
        if (["file", "submit", "button", "reset", "image"].includes(element.type)) return false;
        return true;
    });

    const data = {};
    const grouped = new Map();

    controls.forEach((element) => {
        const name = element.name;
        if (!grouped.has(name)) grouped.set(name, []);
        grouped.get(name).push(element);
    });

    grouped.forEach((elements, name) => {
        const isArrayField = name.endsWith("[]") || elements.length > 1;
        const first = elements[0];

        if (first.type === "checkbox") {
            if (isArrayField) {
                data[name] = elements
                    .filter((element) => element.checked)
                    .map((element) => element.value);
            } else {
                data[name] = Boolean(first.checked);
            }
            return;
        }

        if (first.type === "radio") {
            const selected = elements.find((element) => element.checked);
            if (selected) data[name] = selected.value;
            return;
        }

        if (first.tagName === "SELECT" && first.multiple) {
            data[name] = Array.from(first.selectedOptions).map((option) => option.value);
            return;
        }

        const values = elements.map((element) => $(element).val() ?? "");
        data[name] = isArrayField ? values : values[0];
    });

    const storage = {
        version: this.DRAFT_VERSION,
        status: "draft",
        savedAt: new Date().toISOString(),
        data
    };

    this.writeStorage(storage);
};

UploadCore.writeStorage = function (storage) {
    try {
        localStorage.setItem(this.config.storageKey, JSON.stringify(storage));
        return true;
    } catch (error) {
        console.error("Tidak dapat menyimpan draft:", error);
        if (typeof Swal !== "undefined") {
            Swal.fire({
                icon: "warning",
                title: "Draft tidak dapat disimpan",
                text: "Penyimpanan browser penuh atau tidak tersedia. Data formulir masih dapat dikirim selama halaman ini tidak ditutup."
            });
        }
        return false;
    }
};

/*************************************************************************
 * Restore Draft
 *************************************************************************/

UploadCore.ensureDynamicFamilyFields = function (data, form) {
    const familyNames = [
        "keluarga_hubungan[]",
        "keluarga_nama[]",
        "keluarga_tgl_lahir[]",
        "keluarga_usia[]",
        "keluarga_pekerjaan[]"
    ];

    const maxCount = familyNames.reduce((max, name) => {
        const value = data[name];
        return Math.max(max, Array.isArray(value) ? value.length : (value !== undefined ? 1 : 0));
    }, 0);

    const currentCount = form.querySelectorAll('[name="keluarga_nama[]"]').length;
    const addButton = form.querySelector("#btn-add-keluarga");

    if (!addButton) return;

    for (let index = currentCount; index < maxCount; index += 1) {
        addButton.click();
    }
};

UploadCore.loadForm = function () {
    if (!this.config.autoSave) return;

    const storage = this.getStorage();
    if (!storage || !storage.data) return;

    const form = this.getForm();
    if (!form) return;

    const data = storage.data;
    this.ensureDynamicFamilyFields(data, form);

    Object.keys(data).forEach((name) => {
        const elements = Array.from(form.elements).filter((element) => element.name === name);
        if (!elements.length) return;

        const first = elements[0];
        const value = data[name];

        if (first.type === "checkbox") {
            if (Array.isArray(value)) {
                elements.forEach((element) => {
                    element.checked = value.includes(element.value);
                });
            } else if (typeof value === "boolean" && !name.endsWith("[]")) {
                // Legacy support for standalone checkbox fields.
                first.checked = value;
            }
            // Older versions stored only the last checkbox's boolean for [] groups.
            // Ignore that legacy value instead of incorrectly checking every option.
            return;
        }

        if (first.type === "radio") {
            elements.forEach((element) => {
                element.checked = element.value === value;
            });
            return;
        }

        if (first.tagName === "SELECT" && first.multiple) {
            $(first).val(Array.isArray(value) ? value : [value]);
            return;
        }

        if (name.endsWith("[]") || elements.length > 1) {
            const values = Array.isArray(value) ? value : [value];
            elements.forEach((element, index) => {
                if (index < values.length) $(element).val(values[index]);
            });
            return;
        }

        $(first).val(value);
    });

    if (typeof this.config.onAfterRestore === "function") {
        this.config.onAfterRestore(form, storage);
    }

    // Persist the normalized draft format after restoration.
    this.saveForm();
};

/*************************************************************************
 * Storage Helpers
 *************************************************************************/

UploadCore.getStorage = function () {
    if (!this.config.storageKey) return null;

    const json = localStorage.getItem(this.config.storageKey);
    if (!json) return null;

    try {
        return JSON.parse(json);
    } catch (error) {
        console.error("Draft tidak dapat dibaca:", error);
        return null;
    }
};

UploadCore.hasDraft = function () {
    if (!this.config.autoSave) return false;
    const storage = this.getStorage();
    return Boolean(storage && storage.data && Object.keys(storage.data).length);
};

UploadCore.clearStorage = function () {
    if (!this.config.autoSave || !this.config.storageKey) return;
    localStorage.removeItem(this.config.storageKey);
};

/*************************************************************************
 * Init
 *************************************************************************/

UploadCore.init = function (config) {
    this.config = {
        autoSave: false,
        storageKey: "uploadForm",
        summary: true,
        files: {},
        beforeSubmit: null,
        onAfterRestore: null,
        onFormReset: null,
        successResponse: null,
        showServerError: false,
        errorMessage: "Pengiriman gagal. Data formulir tetap tersedia. Silakan coba lagi atau hubungi admin LPK Mirai.",
        ...config
    };

    $(document).ready(() => {
        const form = this.getForm();
        if (!form) {
            console.error("UploadCore: form tidak ditemukan:", this.config.form);
            return;
        }

        // Initialize default checkbox and other form-specific behavior.
        if (typeof this.config.onFormReady === "function") {
            this.config.onFormReady(form);
        }

        // Draft confirmation. File inputs cannot be restored by browsers and
        // must be selected again after the draft is restored.
        if (this.hasDraft()) {
            Swal.fire({
                icon: "question",
                title: "Lanjutkan Pengisian?",
                html:
                    "Ditemukan data formulir yang tersimpan.<br><br>" +
                    "Lanjutkan pengisian dari data sebelumnya?" +
                    "<br><small>Dokumen yang pernah dipilih perlu diunggah kembali.</small>",
                confirmButtonText: "Lanjutkan",
                cancelButtonText: "Mulai Baru",
                showCancelButton: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    this.loadForm();
                } else {
                    this.clearStorage();
                    form.reset();
                    if (typeof this.config.onFormReset === "function") {
                        this.config.onFormReset(form);
                    }
                }
            });
        }

        // File preparation; keep only the latest selected file per input.
        $(form).on("change", 'input[type="file"]', (event) => {
            this.handleFileChange(event.currentTarget);
        });

        // Autosave all regular fields and selected checkbox/radio values.
        $(form).on("input change", ":input", () => {
            this.saveForm();
        });

        this.updateSummary();

        $(form).on("submit", (event) => {
            event.preventDefault();
            this.submit(event.currentTarget);
        });
    });
};
