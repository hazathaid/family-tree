<x-layouts.public title="Family Tree Platform Indonesia">
    <section class="welcome-hero" aria-labelledby="foundation-title">
        <div class="container position-relative">
            <div class="row align-items-center g-5">
                <div class="col-lg-7">
                    <p class="welcome-kicker">Arsip keluarga lintas generasi</p>
                    <h1 id="foundation-title" class="welcome-title">Setiap nama punya cerita. <em>Jangan biarkan hilang.</em></h1>
                    <p class="welcome-lead">Bangun silsilah, simpan foto lama, dan teruskan kisah keluarga dalam satu rumah digital yang mudah digunakan bersama.</p>
                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a class="btn btn-primary btn-lg px-4" href="{{ route('register') }}">Mulai keluarga Anda</a>
                        <a class="btn btn-outline-dark btn-lg px-4" href="{{ route('login') }}">Masuk ke akun</a>
                    </div>
                    <div class="welcome-proof mt-5">
                        <span>Anggota</span>
                        <span>Kenangan</span>
                        <span>Peristiwa</span>
                        <span>Warisan</span>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="family-portrait" aria-label="Ilustrasi hubungan tiga generasi keluarga">
                        <div class="portrait-caption">
                            <small>Keluarga Santoso</small>
                            <strong>Tiga generasi, satu cerita</strong>
                        </div>
                        <div class="portrait-tree">
                            <span class="portrait-line line-top"></span>
                            <span class="portrait-line line-left"></span>
                            <span class="portrait-line line-right"></span>
                            <div class="portrait-person person-root"><b>HS</b><small>Hadi</small></div>
                            <div class="portrait-person person-left"><b>BS</b><small>Budi</small></div>
                            <div class="portrait-person person-right"><b>RS</b><small>Rina</small></div>
                        </div>
                        <p class="portrait-note">“Cerita keluarga adalah rumah yang bisa dibawa ke mana saja.”</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="welcome-features">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-4">
                    <article class="feature-story">
                        <span class="feature-number">01</span>
                        <h2>Pahami hubungan</h2>
                        <p>Lihat silsilah keluarga dengan jelas, dari leluhur hingga generasi termuda.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-story">
                        <span class="feature-number">02</span>
                        <h2>Rawat kenangan</h2>
                        <p>Kumpulkan artikel, foto, dan peristiwa penting agar cerita tidak tercecer.</p>
                    </article>
                </div>
                <div class="col-md-4">
                    <article class="feature-story">
                        <span class="feature-number">03</span>
                        <h2>Tumbuh bersama</h2>
                        <p>Libatkan keluarga untuk melengkapi informasi dan menjaga sejarah bersama.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
