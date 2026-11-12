/*************************************************************************
 * tes_kesehatan.js
 *************************************************************************/

UploadCore.init({

    form: "#tesKesehatanForm",
    submitButton: "#submitTesKesehatan",
    php: "tes_kesehatan.php",
    summary: true,
    autoSave: true,
    storageKey: "tesKesehatanForm",
    files: {

    },

    ui: {

        progressBar: "#progressBar",
        progressText: "#progressText",
        loading: "#loadingIndicator",
        summaryCard: "#summaryCard",
        summaryList: "#summaryList",
        summaryTotal: "#summaryTotal"

    },

});