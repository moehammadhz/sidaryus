<dialog id="dlg-lepas" aria-labelledby="lepas-title">
    <form method="POST" id="form-lepas">
        @csrf
        @method('DELETE')
        <h3 id="lepas-title">Lepas dari periode?</h3>
        <p><strong id="lepas-nama"></strong> akan dilepas dari periode yudisiumnya. Mahasiswa bisa mendaftar lagi dari dashboard ke periode yang sedang dibuka, dan nilainya bisa diralat kembali.</p>
        <div class="dlg-actions">
            <button type="button" class="btn btn--ghost" data-close>Batal</button>
            <button type="submit" class="btn btn--danger">Lepas</button>
        </div>
    </form>
</dialog>

@push('scripts')
<script>
(function () {
    var dlg = document.getElementById('dlg-lepas');
    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-lepas]');
        if (b) {
            document.getElementById('form-lepas').action = b.dataset.url;
            document.getElementById('lepas-nama').textContent = b.dataset.nama;
            dlg.showModal();
            return;
        }
        if (e.target === dlg || (dlg.open && e.target.closest('[data-close]'))) dlg.close();
    });
})();
</script>
@endpush
