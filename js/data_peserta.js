/*************************************************************************
 * data_peserta.js
 * Form-specific behavior for data_peserta.html
 *************************************************************************/

(function ($) {
    "use strict";

    const FORM_SELECTOR = "#dataPesertaForm";
    let validationAttempted = false;

    const GROUPS = {
        bahasa: {
            inputName: "bahasa[]",
            label: "Bahasa yang dikuasai"
        },
        materiDikuasai: {
            inputName: "materi_dikuasai[]",
            label: "Materi yang dikuasai"
        },
        materiTidakDikuasai: {
            inputName: "materi_tidak_dikuasai[]",
            label: "Materi yang tidak dikuasai"
        }
    };

    function getCheckboxes(form, inputName) {
        return Array.from(form.querySelectorAll('input[type="checkbox"]'))
            .filter((input) => input.name === inputName);
    }

    function setGroupError(form, groupName, message) {
        const group = form.querySelector(`[data-checkbox-group="${groupName}"]`);
        if (!group) return;

        const errorNode = group.querySelector("[data-checkbox-error]");
        const invalid = Boolean(message);

        group.classList.toggle("checkbox-group-invalid", invalid);

        if (errorNode) {
            errorNode.textContent = message || "";
            errorNode.hidden = !invalid;
        }

        group.querySelectorAll('input[type="checkbox"]').forEach((input) => {
            if (invalid) {
                input.setAttribute("aria-invalid", "true");
            } else {
                input.removeAttribute("aria-invalid");
            }
        });
    }

    function validateCheckboxGroups(form, showDialog = true, changedGroup = null) {
        const checks = [
            { key: "bahasa", config: GROUPS.bahasa },
            { key: "materi-dikuasai", config: GROUPS.materiDikuasai },
            { key: "materi-tidak-dikuasai", config: GROUPS.materiTidakDikuasai }
        ];

        const invalidGroups = [];

        checks.forEach(({ key, config }) => {
            const selected = getCheckboxes(form, config.inputName)
                .some((input) => input.checked);

            if (!selected) {
                if (validationAttempted || showDialog || changedGroup === key) {
                    setGroupError(form, key, `Pilih minimal satu ${config.label.toLowerCase()}.`);
                }
                invalidGroups.push({ key, config });
            } else {
                setGroupError(form, key, "");
            }
        });

        if (invalidGroups.length > 0) {
            const firstGroup = form.querySelector(
                `[data-checkbox-group="${invalidGroups[0].key}"]`
            );
            const firstCheckbox = firstGroup
                ? firstGroup.querySelector('input[type="checkbox"]')
                : null;

            if (firstCheckbox) {
                firstCheckbox.focus({ preventScroll: true });
                firstGroup.scrollIntoView({ behavior: "smooth", block: "center" });
            }

            if (showDialog && typeof Swal !== "undefined") {
                Swal.fire({
                    icon: "warning",
                    title: "Pilihan belum lengkap",
                    text: "Bahasa, materi yang dikuasai, dan materi yang tidak dikuasai wajib memiliki minimal satu pilihan."
                });
            }

            return false;
        }

        return true;
    }

    function syncExclusiveMaterials(form, changedInput = null) {
        const mastered = getCheckboxes(form, GROUPS.materiDikuasai.inputName);
        const notMastered = getCheckboxes(form, GROUPS.materiTidakDikuasai.inputName);

        // If the user selects an option, remove the same value from the other
        // group. This also normalizes old drafts containing conflicting values.
        if (changedInput && changedInput.checked) {
            const opposite = changedInput.name === GROUPS.materiDikuasai.inputName
                ? notMastered
                : mastered;

            opposite.forEach((input) => {
                if (input.value === changedInput.value) {
                    input.checked = false;
                }
            });
        } else {
            // For restored/legacy data, the "dikuasai" group takes precedence
            // if the same value was stored in both groups.
            mastered.filter((input) => input.checked).forEach((selected) => {
                const opposite = notMastered.find((input) => input.value === selected.value);
                if (opposite) opposite.checked = false;
            });
        }

        const selectedMastered = new Set(
            mastered.filter((input) => input.checked).map((input) => input.value)
        );
        const selectedNotMastered = new Set(
            notMastered.filter((input) => input.checked).map((input) => input.value)
        );

        mastered.forEach((input) => {
            const isBlocked = selectedNotMastered.has(input.value) && !input.checked;
            input.disabled = isBlocked;
            const label = form.querySelector(`label[for="${input.id}"]`);
            if (label) label.classList.toggle("text-muted", isBlocked);
        });

        notMastered.forEach((input) => {
            const isBlocked = selectedMastered.has(input.value) && !input.checked;
            input.disabled = isBlocked;
            const label = form.querySelector(`label[for="${input.id}"]`);
            if (label) label.classList.toggle("text-muted", isBlocked);
        });
    }

    function ensureDefaultLanguage(form) {
        const indonesia = form.querySelector('#indo[name="bahasa[]"]');
        if (indonesia) indonesia.checked = true;
    }

    function refreshCheckboxUI(form) {
        validationAttempted = false;
        syncExclusiveMaterials(form);
        // Do not show required-field errors immediately on initial page load.
        setGroupError(form, "bahasa", "");
        setGroupError(form, "materi-dikuasai", "");
        setGroupError(form, "materi-tidak-dikuasai", "");
    }

    function initializeForm(form) {
        // Indonesia is selected by default on a new form.
        ensureDefaultLanguage(form);
        refreshCheckboxUI(form);
    }

    function resetForm(form) {
        // Remove extra family rows added dynamically so a successful/reset
        // form returns to one initial family member block.
        const familyContainer = form.querySelector("#keluarga-container");
        if (familyContainer) familyContainer.replaceChildren();

        ensureDefaultLanguage(form);
        refreshCheckboxUI(form);
    }

    function handleCheckboxChange(event) {
        const input = event.target;
        if (!input.matches('input[type="checkbox"]')) return;

        const form = input.form;
        if (!form || form.id !== "dataPesertaForm") return;

        if (
            input.name === GROUPS.materiDikuasai.inputName ||
            input.name === GROUPS.materiTidakDikuasai.inputName
        ) {
            syncExclusiveMaterials(form, input);
        }

        const groupKey = input.name === GROUPS.bahasa.inputName
            ? "bahasa"
            : (input.name === GROUPS.materiDikuasai.inputName
                ? "materi-dikuasai"
                : "materi-tidak-dikuasai");
        validateCheckboxGroups(form, false, groupKey);
    }

    function validateBeforeSubmit(form) {
        validationAttempted = true;
        syncExclusiveMaterials(form);
        return validateCheckboxGroups(form, true);
    }

    UploadCore.init({
        form: FORM_SELECTOR,
        submitButton: "#submitDataPeserta",
        php: "data_peserta.php",
        successResponse: "OK",
        showServerError: true,
        summary: true,
        autoSave: true,
        storageKey: "dataPesertaForm",

        files: {
            foto_3x4: "Foto 3×4",
            upload_ktp: "KTP",
            upload_kk: "Kartu Keluarga",
            upload_akta: "Akta Kelahiran",
            sd_ijazah: "Ijazah SD",
            smp_ijazah: "Ijazah SMP",
            sma_ijazah: "Ijazah SMA",
            pt_ijazah: "Ijazah Perguruan Tinggi"
        },

        ui: {
            progressBar: "#progressBar",
            progressText: "#progressText",
            loading: "#loadingIndicator",
            summaryCard: "#summaryCard",
            summaryList: "#summaryList",
            summaryTotal: "#summaryTotal"
        },

        onFormReady: initializeForm,
        onAfterRestore: (form) => {
            // Preserve the agreed default and normalize mutually exclusive values.
            ensureDefaultLanguage(form);
            refreshCheckboxUI(form);
        },
        onFormReset: resetForm,
        beforeSubmit: validateBeforeSubmit
    });

    $(function () {
        const form = document.querySelector(FORM_SELECTOR);
        if (!form) return;

        // Delegated listener also works with the checkbox controls if the form
        // is later extended dynamically.
        form.addEventListener("change", handleCheckboxChange);
    });

})(jQuery);
