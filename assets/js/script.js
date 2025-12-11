// Konfirmasi sebelum hapus
document.addEventListener('DOMContentLoaded', function() {
    // Auto-hide alerts setelah 5 detik
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Format input uang
    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach(input => {
        input.addEventListener('keyup', function(e) {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                this.value = value;
            }
        });
    });
});
 document.addEventListener('DOMContentLoaded', function() {
        const barangSelect = document.getElementById('barang_id');
        const jumlahInput = document.getElementById('jumlah');
        const hargaInput = document.getElementById('harga_satuan');
        const totalInput = document.getElementById('total');
        
        // Hitung total otomatis
        function calculateTotal() {
            const jumlah = parseInt(jumlahInput.value) || 0;
            const harga = parseInt(hargaInput.value) || 0;
            totalInput.value = jumlah * harga;
        }
        
        // Event listeners
        jumlahInput.addEventListener('input', calculateTotal);
        hargaInput.addEventListener('input', calculateTotal);
        
        // Update harga ketika barang dipilih
        barangSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value && selectedOption.dataset.harga) {
                hargaInput.value = selectedOption.dataset.harga;
                calculateTotal();
            }
        });
        
        // Cek stok jika pemasukan
        document.getElementById('jenis_transaksi').addEventListener('change', function() {
            if (this.value === 'pemasukan' && barangSelect.value) {
                const selectedOption = barangSelect.options[barangSelect.selectedIndex];
                const stok = parseInt(selectedOption.dataset.stok) || 0;
                
                if (stok <= 0) {
                    alert('Stok barang habis! Pilih barang lain.');
                    barangSelect.value = '';
                    hargaInput.value = 0;
                    calculateTotal();
                }
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        // Format input harga
        const hargaBeli = document.getElementById('harga_beli');
        const hargaJual = document.getElementById('harga_jual');
        
        // Auto-format angka
        function formatNumber(input) {
            let value = input.value.replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                input.value = value;
            }
        }
        
        hargaBeli.addEventListener('blur', function() {
            formatNumber(this);
            // Auto-set harga jual minimal 10% lebih tinggi
            let beliValue = parseInt(this.value.replace(/[^\d]/g, '')) || 0;
            if (beliValue > 0 && hargaJual.value == '') {
                let jualValue = Math.ceil(beliValue * 1.1 / 1000) * 1000; // Naik 10%, dibulatkan ke ribuan
                hargaJual.value = jualValue.toLocaleString('id-ID');
            }
        });
        
        hargaJual.addEventListener('blur', function() {
            formatNumber(this);
        });
        
        // Validasi form
        document.getElementById('barangForm').addEventListener('submit', function(e) {
            const beli = parseInt(hargaBeli.value.replace(/[^\d]/g, '')) || 0;
            const jual = parseInt(hargaJual.value.replace(/[^\d]/g, '')) || 0;
            
            if (jual <= beli) {
                e.preventDefault();
                alert('Harga jual harus lebih besar dari harga beli!');
                hargaJual.focus();
            }
        });
    });
function showDetail(barangId) {
    fetch(`dashboard.php?page=stok&action=get_detail&barang_id=${barangId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const barang = data.data;
                const content = `
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Nama Barang:</th>
                                    <td>${barang.nama_barang}</td>
                                </tr>
                                <tr>
                                    <th>Stok Tersedia:</th>
                                    <td><span class="badge bg-primary">${barang.stok} unit</span></td>
                                </tr>
                                <tr>
                                    <th>Harga Beli:</th>
                                    <td>Rp ${parseInt(barang.harga_beli).toLocaleString('id-ID')}</td>
                                </tr>
                                <tr>
                                    <th>Harga Jual:</th>
                                    <td>Rp ${parseInt(barang.harga_jual).toLocaleString('id-ID')}</td>
                                </tr>
                                <tr>
                                    <th>Margin:</th>
                                    <td>
                                        <span class="badge bg-success">
                                            Rp ${(parseInt(barang.harga_jual) - parseInt(barang.harga_beli)).toLocaleString('id-ID')}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nilai Stok:</th>
                                    <td class="fw-bold text-primary">
                                        Rp ${(parseInt(barang.stok) * parseInt(barang.harga_jual)).toLocaleString('id-ID')}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                `;
                document.getElementById('detailContent').innerHTML = content;
                const modal = new bootstrap.Modal(document.getElementById('detailModal'));
                modal.show();
            } else {
                alert(data.message || 'Terjadi kesalahan');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan saat mengambil data');
        });
}

// Format input harga
document.addEventListener('DOMContentLoaded', function() {
    const moneyInputs = document.querySelectorAll('.money-input');
    moneyInputs.forEach(input => {
        input.addEventListener('blur', function() {
            let value = this.value.replace(/[^\d]/g, '');
            if (value) {
                value = parseInt(value).toLocaleString('id-ID');
                this.value = value;
            }
        });
        
        input.addEventListener('focus', function() {
            this.value = this.value.replace(/[^\d]/g, '');
        });
    });
    
    // Quick action: Tambah stok
    const quickAddButtons = document.querySelectorAll('.quick-add-stock');
    quickAddButtons.forEach(button => {
        button.addEventListener('click', function() {
            const barangId = this.dataset.id;
            const jumlah = prompt('Masukkan jumlah stok yang akan ditambahkan:', '1');
            
            if (jumlah && !isNaN(jumlah) && parseInt(jumlah) > 0) {
                fetch(`dashboard.php?page=stok&action=add_stock`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `barang_id=${barangId}&jumlah=${jumlah}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message || 'Terjadi kesalahan');
                    }
                });
            }
        });
    });
});