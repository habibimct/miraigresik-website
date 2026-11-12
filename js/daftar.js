/*************************************************************************
 * daftar.js
 *************************************************************************/

UploadCore.init({

    form: "#daftarForm",
    submitButton: "#submitDaftar",
    php: "daftar.php",
    summary: true,
    autoSave: true,
    storageKey: "daftarForm",
    files: {

        bukti_transfer: "Bukti Transfer",
        hasil_tes_kesehatan: "Hasil Tes Kesehatan",
        surat_izin: "Surat Izin",
        surat_pernyataan: "Surat Pernyataan",
        foto_3x4: "Foto 3×4",
        ktp: "KTP",
        kk: "Kartu Keluarga",
        akta_kelahiran: "Akta Kelahiran",
        ijazah: "Ijazah",

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