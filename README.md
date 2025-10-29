# League of Legends API - B201 Project 🏆

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white "PHP")](https://www.php.net/)  [![REST API](https://img.shields.io/badge/REST%20API-007bff?style=for-the-badge&logo=api&logoColor=white "REST API")]() [![CRUD](https://img.shields.io/badge/CRUD-2ecc71?style=for-the-badge&logo=data&logoColor=white "CRUD")]()

Proyek ini adalah implementasi REST API menggunakan PHP untuk menyediakan data terkait League of Legends. API ini dirancang sebagai bagian dari proyek akhir (FP) untuk kelompok B201. Tujuannya adalah untuk menyediakan akses yang mudah dan terstruktur ke data LoL melalui serangkaian endpoint.

API ini menyediakan fungsionalitas CRUD (Create, Read, Update, Delete) dasar untuk mengelola data LoL.

## Fitur Utama ✨

*   **Data Champion 🦸:** Menyediakan endpoint untuk mengambil informasi detail tentang champion League of Legends.
*   **Manajemen User 🧑‍💻:** Menyediakan fungsionalitas untuk membuat, membaca, memperbarui, dan menghapus user.
*   **Autentikasi Sederhana 🔐:** Implementasi sederhana sistem login untuk mengamankan akses ke beberapa endpoint.

## Tech Stack 🛠️

*   PHP 🐘
*   REST API 🌐
*   Kemungkinan MySQL (berdasarkan `dbConnection.php`) 🗄️

## Instalasi & Menjalankan 🚀

1.  Clone repositori:
    ```bash
    git clone https://github.com/EevnxyEgo/LoLAPI-fp-b201
    ```
2.  Masuk ke direktori:
    ```bash
    cd LoLAPI-fp-b201
    ```
3.  Install dependensi:

    Karena proyek ini menggunakan PHP, Anda mungkin perlu menginstal dependensi melalui Composer (jika ada file `composer.json`). Jika tidak ada, Anda perlu memastikan semua dependensi PHP yang diperlukan (seperti koneksi database) sudah terinstal dan dikonfigurasi di lingkungan Anda.

    ```bash
    # Contoh (jika ada composer.json)
    composer install
    ```

4.  Konfigurasi Database:

    Sunting file `controller/dbConnection.php` untuk mengatur koneksi database dengan kredensial yang sesuai. Pastikan server database (misalnya, MySQL) berjalan dan dapat diakses.

5.  Jalankan proyek:

    Karena ini adalah aplikasi PHP, Anda perlu server web (seperti Apache atau Nginx) yang dikonfigurasi untuk melayani file PHP.  Letakkan direktori proyek di direktori root server web Anda atau konfigurasi virtual host yang sesuai.

    Setelah server web dikonfigurasi, Anda dapat mengakses API melalui browser atau alat pengujian API seperti Postman.

    Contoh: `http://localhost/LoLAPI-fp-b201/` (sesuaikan dengan konfigurasi server web Anda).

## Cara Berkontribusi 🤝

1.  Fork repositori ini.
2.  Buat branch baru dengan nama fitur Anda: `git checkout -b fitur-baru`
3.  Lakukan perubahan dan commit: `git commit -m "Tambahkan fitur baru"`
4.  Push ke branch Anda: `git push origin fitur-baru`
5.  Buat Pull Request.

## Lisensi 📄

Tidak disebutkan.


---
README.md ini dihasilkan secara otomatis oleh [README.MD Generator](https://github.com/emRival) — dibuat dengan ❤️ oleh [emRival](https://github.com/emRival)
