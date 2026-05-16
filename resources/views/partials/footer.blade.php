<footer class="footer">
  <div class="footer__top">
    <div class="footer__container footer__grid">

      <div class="footer__brand">
        
        @php
$logoUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url('Undip-Logo.png');
@endphp

<img
  src="{{ $logoUrl }}"
  alt="UNDIP"
  class="footer__logo"
/>
        <span class="footer__brand-text">Universitas Diponegoro</span>
      </div>

      <div class="footer__col">
        <div class="footer__title">Layanan</div>
        <ul class="footer__list">
          <li>Layanan Inovasi dan Kerjasama</li>
        </ul>

        <div class="footer__section">
          <div class="footer__title">Sosial Media</div>

          <div class="footer__social">
            <div class="footer__social-item">
              @php
              $instagramUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url('instagram.png');
              @endphp

              <a 
                href="https://www.instagram.com/dirinovki.undip" 
                target="_blank"
                class="footer__social-link"
              >
                <img
                  src="{{ $instagramUrl }}"
                  alt="Instagram"
                  class="footer__social-icon"
                />

                <span class="footer__text">@dirinovki.undip</span>
              </a>
            </div>

            <div class="footer__social-item">
              @php
              $youtubeUrl = \Illuminate\Support\Facades\Storage::disk('s3')->url('youtube.png');
              @endphp

              <a 
                href="https://www.youtube.com/@dirinovkiundip" 
                target="_blank"
                class="footer__social-link"
              >
                <img
                  src="{{ $youtubeUrl }}"
                  alt="YouTube"
                  class="footer__social-icon"
                />

                <span class="footer__text">@dirinovkiundip</span>
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="footer__col">
        <div class="footer__title">Kontak</div>

        <div class="footer__contact">
          Jl. Prof Soedarto, SH, Tembalang Semarang.<br />
          Telp: 024 - 74060020

          <div class="footer__contact-block">
            WhatsApp Call Center Inovasi:<br />
            <a 
              href="https://wa.me/628113805805"
              target="_blank"
              class="footer__link"
            >
              08113805805 (Sentra KI)
            </a>
          </div>

          <div class="footer__contact-block">
            WhatsApp Helpdesk Inovasi:<br />
            <a
                          href="https://wa.me/628113848555"
              target="_blank"
              class="footer__link"
            >
               08113848555 (Inovasi)
            </a>
          </div>

          <div class="footer__contact-block">
            Email:<br />
            <a 
              href="mailto:dirinovki@gmail.com"
              class="footer__link"
            >
              dirinovki@gmail.com
            </a>
          </div>
        </div>
      </div>

      <div class="footer__col">
        <div class="footer__title">Lokasi</div>

        <div class="footer__location">
          Direktorat Inovasi dan Kerjasama, Innovation Hub, Gedung Perpustakaan Lantai 5
        </div>

        <div class="footer__map">
          <iframe
            class="footer__map-iframe"
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3959.6363392527333!2d110.43508601487366!3d-7.051947971062804!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708c1b3d6548b1%3A0xa140280d22e36dd9!2sDiponegoro%20University%20Rectorate!5e0!3m2!1sen!2sid!4v1602578929385!5m2!1sen!2sid"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            allowfullscreen=""
          ></iframe>
        </div>
      </div>

    </div>
  </div>

  <div class="footer__bottom">
    <div class="footer__container footer__bottom-inner">
      Copyright © Universitas Diponegoro 2026
    </div>
  </div>
</footer>
