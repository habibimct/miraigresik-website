/*************************************************************************
 * data_peserta.js
 *************************************************************************/

UploadCore.init({

    form: "#dataPesertaForm",
    submitButton: "#submitDataPeserta",
    php: "data_peserta.php",
    summary: true,
    autoSave:true,
    storageKey:"dataPesertaForm",
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

});