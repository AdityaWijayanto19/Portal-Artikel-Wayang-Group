<?php

namespace App\Exceptions;

/**
 * Dilempar saat penghapusan user ditahan karena user tersebut masih memiliki
 * artikel dan syarat pemindahannya belum terpenuhi (penerima tidak dipilih,
 * tidak valid, atau bukan satu perusahaan).
 *
 * Sifatnya "permintaan ditolak", bukan error sistem — controller menampilkannya
 * sebagai pesan peringatan biasa ke pengguna.
 */
class UserHasArticlesException extends \RuntimeException {}
