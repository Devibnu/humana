  <!-- -------- START FOOTER 3 w/ COMPANY DESCRIPTION WITH LINKS & SOCIAL ICONS & COPYRIGHT ------- -->
  @php
    $guestTenant = auth()->user()?->tenant ?? \App\Models\Tenant::query()->latest('id')->first();
    $guestBrandName = $guestTenant?->name ?? config('app.name', 'Humana');
    $tenantLoginFooterText = trim((string) ($guestTenant?->login_footer_text ?? ''));
    $resolvedLoginFooterText = str_replace('{year}', (string) now()->year, $tenantLoginFooterText);
    $defaultLoginFooterText = 'Copyright (c) '.now()->year.' '.$guestBrandName.'. All rights reserved.';
  @endphp
  <footer class="footer py-5">
    <div class="container">
      <div class="row">
      @unless (\Request::is('login'))
      <div class="col-lg-8 mb-4 mx-auto text-center">
        <a href="https://www.creative-tim.com/?_ga=2.242299972.757293697.1638911086-1528502635.1638911086" target="_blank" class="text-secondary me-xl-5 me-3 mb-sm-0 mb-2">
          Company
        </a>
        <a href="https://www.creative-tim.com/presentation" target="_blank" class="text-secondary me-xl-5 me-3 mb-sm-0 mb-2">
          About Us
        </a>
        <a href="https://www.creative-tim.com/presentation" target="_blank" class="text-secondary me-xl-5 me-3 mb-sm-0 mb-2">
          Team
        </a>
        <a href="https://www.creative-tim.com/templates" target="_blank" class="text-secondary me-xl-5 me-3 mb-sm-0 mb-2">
          Products
        </a>
        <a href="https://www.creative-tim.com/blog" target="_blank" class="text-secondary me-xl-5 me-3 mb-sm-0 mb-2">
          Blog
        </a>
        <a href="https://www.creative-tim.com/support-terms" target="_blank" class="text-secondary me-xl-5 me-3 mb-sm-0 mb-2">
          Pricing
        </a>
      </div>
      @endunless
        @if ((!auth()->user() || \Request::is('static-sign-up')) && !\Request::is('login')) 
          <div class="col-lg-8 mx-auto text-center mb-4 mt-2">
              <a href="https://dribbble.com/creativetim" target="_blank" class="text-secondary me-xl-4 me-4">
                  <span class="text-lg fab fa-dribbble" aria-hidden="true"></span>
              </a>
              <a href="https://twitter.com/CreativeTim" target="_blank" class="text-secondary me-xl-4 me-4">
                  <span class="text-lg fab fa-twitter" aria-hidden="true"></span>
              </a>
              <a href="https://www.instagram.com/creativetimofficial/" target="_blank" class="text-secondary me-xl-4 me-4">
                  <span class="text-lg fab fa-instagram" aria-hidden="true"></span>
              </a>
              <a href="https://ro.pinterest.com/thecreativetim/" target="_blank" class="text-secondary me-xl-4 me-4">
                  <span class="text-lg fab fa-pinterest" aria-hidden="true"></span>
              </a>
              <a href="https://github.com/creativetimofficial" target="_blank" class="text-secondary me-xl-4 me-4">
                  <span class="text-lg fab fa-github" aria-hidden="true"></span>
              </a>
          </div>
        @endif
      </div>
      @if (!auth()->user() || \Request::is('static-sign-up')) 
        <div class="row">
          <div class="col-8 mx-auto text-center mt-1">
            <p class="mb-0 text-secondary">
              @if (\Request::is('login') && $resolvedLoginFooterText !== '')
                {{ $resolvedLoginFooterText }}
              @elseif (\Request::is('login'))
                {{ $defaultLoginFooterText }}
              @else
                Copyright © <script>
                  document.write(new Date().getFullYear())
                </script> Soft by 
                <a style="color: #252f40;" href="https://www.creative-tim.com" class="font-weight-bold ml-1" target="_blank">Creative Tim</a>
                &
                <a style="color: #252f40;" href="https://www.updivision.com" class="font-weight-bold ml-1" target="_blank">UPDIVISION</a>.
              @endif
            </p>
          </div>
        </div>
      @endif
    </div>
  </footer>
  <!-- -------- END FOOTER 3 w/ COMPANY DESCRIPTION WITH LINKS & SOCIAL ICONS & COPYRIGHT ------- -->
