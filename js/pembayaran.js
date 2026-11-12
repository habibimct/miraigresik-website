/*************************************************************************
 * pembayaran.js
 *************************************************************************/

UploadCore.init({

    form: "#pembayaranForm",
    submitButton: "#submitPembayaran",
    php: "pembayaran.php",
    summary: true,
    autoSave: true,
    storageKey: "pembayaranForm",
    files: {

        bukti_pembayaran: "Bukti Pembayaran",

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