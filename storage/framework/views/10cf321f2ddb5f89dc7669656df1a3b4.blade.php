    <x-form.input name="email" label="Email" required />
    <x-alert variant="danger">Gagal diproses.</x-alert>
    <x-empty-state title="Belum ada data" message="Tambahkan data pertama Anda." />
    <x-loading-state label="Memuat anggota" :lines="2" />