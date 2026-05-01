
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 " id="sidenav-main">
  @php($currentUser = auth()->user())
  @php($sidebarTenant = $currentUser?->tenant)
  @php($sidebarBrandLogo = $sidebarTenant?->branding_path ? asset($sidebarTenant->branding_path) : asset('assets/img/logo-ct.png'))
  @php($sidebarBrandName = $sidebarTenant?->name)
  <div class="sidenav-header">
    <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
    <a class="align-items-center d-flex m-0 navbar-brand text-wrap" href="{{ route('dashboard') }}" data-testid="sidebar-tenant-brand">
        <img src="{{ $sidebarBrandLogo }}" class="navbar-brand-img h-100 rounded bg-white p-1" alt="Logo {{ $sidebarBrandName ?? 'Dashboard' }}">
        <span class="ms-3 d-flex flex-column">
          @if ($sidebarBrandName)
            <span class="font-weight-bold">{{ $sidebarBrandName }}</span>
          @else
            <span class="font-weight-bold">Humana</span>
          @endif
        </span>
    </a>
  </div>
  <hr class="horizontal dark mt-0">
  <div class="collapse navbar-collapse  w-auto" id="sidenav-collapse-main">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('dashboard') ? 'active' : '') }}" href="{{ url('dashboard') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <svg width="12px" height="12px" viewBox="0 0 45 40" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
              <title>shop </title>
              <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                <g transform="translate(-1716.000000, -439.000000)" fill="#FFFFFF" fill-rule="nonzero">
                  <g transform="translate(1716.000000, 291.000000)">
                    <g transform="translate(0.000000, 148.000000)">
                      <path class="color-background opacity-6" d="M46.7199583,10.7414583 L40.8449583,0.949791667 C40.4909749,0.360605034 39.8540131,0 39.1666667,0 L7.83333333,0 C7.1459869,0 6.50902508,0.360605034 6.15504167,0.949791667 L0.280041667,10.7414583 C0.0969176761,11.0460037 -1.23209662e-05,11.3946378 -1.23209662e-05,11.75 C-0.00758042603,16.0663731 3.48367543,19.5725301 7.80004167,19.5833333 L7.81570833,19.5833333 C9.75003686,19.5882688 11.6168794,18.8726691 13.0522917,17.5760417 C16.0171492,20.2556967 20.5292675,20.2556967 23.494125,17.5760417 C26.4604562,20.2616016 30.9794188,20.2616016 33.94575,17.5760417 C36.2421905,19.6477597 39.5441143,20.1708521 42.3684437,18.9103691 C45.1927731,17.649886 47.0084685,14.8428276 47.0000295,11.75 C47.0000295,11.3946378 46.9030823,11.0460037 46.7199583,10.7414583 Z"></path>
                      <path class="color-background" d="M39.198,22.4912623 C37.3776246,22.4928106 35.5817531,22.0149171 33.951625,21.0951667 L33.92225,21.1107282 C31.1430221,22.6838032 27.9255001,22.9318916 24.9844167,21.7998837 C24.4750389,21.605469 23.9777983,21.3722567 23.4960833,21.1018359 L23.4745417,21.1129513 C20.6961809,22.6871153 17.4786145,22.9344611 14.5386667,21.7998837 C14.029926,21.6054643 13.533337,21.3722507 13.0522917,21.1018359 C11.4250962,22.0190609 9.63246555,22.4947009 7.81570833,22.4912623 C7.16510551,22.4842162 6.51607673,22.4173045 5.875,22.2911849 L5.875,44.7220845 C5.875,45.9498589 6.7517757,46.9451667 7.83333333,46.9451667 L19.5833333,46.9451667 L19.5833333,33.6066734 L27.4166667,33.6066734 L27.4166667,46.9451667 L39.1666667,46.9451667 C40.2482243,46.9451667 41.125,45.9498589 41.125,44.7220845 L41.125,22.2822926 C40.4887822,22.4116582 39.8442868,22.4815492 39.198,22.4912623 Z"></path>
                    </g>
                  </g>
                </g>
              </g>
            </svg>
          </div>
          <span class="nav-link-text ms-1">Dashboard</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link {{ (Request::is('profile') || Request::is('user-profile') ? 'active' : '') }} " href="{{ route('profile') }}" data-testid="sidebar-menu-profile">
            <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
                <svg width="12px" height="12px" viewBox="0 0 46 42" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                    <title>customer-support</title>
                    <g id="Basic-Elements" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                        <g id="Rounded-Icons" transform="translate(-1717.000000, -291.000000)" fill="#FFFFFF" fill-rule="nonzero">
                            <g id="Icons-with-opacity" transform="translate(1716.000000, 291.000000)">
                                <g id="customer-support" transform="translate(1.000000, 0.000000)">
                                    <path class="color-background" d="M45,0 L26,0 C25.447,0 25,0.447 25,1 L25,20 C25,20.379 25.214,20.725 25.553,20.895 C25.694,20.965 25.848,21 26,21 C26.212,21 26.424,20.933 26.6,20.8 L34.333,15 L45,15 C45.553,15 46,14.553 46,14 L46,1 C46,0.447 45.553,0 45,0 Z" id="Path" opacity="0.59858631"></path>
                                    <path class="color-foreground" d="M22.883,32.86 C20.761,32.012 17.324,31 13,31 C8.676,31 5.239,32.012 3.116,32.86 C1.224,33.619 0,35.438 0,37.494 L0,41 C0,41.553 0.447,42 1,42 L25,42 C25.553,42 26,41.553 26,41 L26,37.494 C26,35.438 24.776,33.619 22.883,32.86 Z" id="Path"></path>
                                    <path class="color-foreground" d="M13,28 C17.432,28 21,22.529 21,18 C21,13.589 17.411,10 13,10 C8.589,10 5,13.589 5,18 C5,22.529 8.568,28 13,28 Z" id="Path"></path>
                                </g>
                            </g>
                        </g>
                    </g>
                </svg>
            </div>
            <span class="nav-link-text ms-1">Profil Saya</span>
        </a>
      </li>

      @if ($currentUser && count(array_intersect(['users', 'employees', 'departments', 'positions', 'work_locations', 'tenants', 'roles', 'leaves.manage', 'payroll'], $currentUser->accessibleMenuKeys())) > 0)
      <li class="nav-item mt-2">
        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Data Master</h6>
      </li>
      @if ($currentUser->hasMenuAccess('users'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('users') || Request::is('users/*') || Request::is('user-management') || Request::is('user-profile/*')) ? 'active' : '' }}"
          href="{{ route('users.index') }}"
          title="Kelola data master pengguna"
          data-testid="sidebar-menu-users"
          data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-user-shield text-sm {{ (Request::is('users') || Request::is('users/*') || Request::is('user-management') || Request::is('user-profile/*')) ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Pengguna</span>
        </a>
      </li>
      @endif
      @if ($currentUser->hasMenuAccess('employees'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('employees') || Request::is('employees/*')) ? 'active' : '' }}"
          href="{{ route('employees.index') }}"
          title="Kelola data karyawan"
          data-testid="sidebar-menu-employees"
          data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-users text-sm {{ (Request::is('employees') || Request::is('employees/*')) ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Karyawan</span>
        </a>
      </li>
      @endif

      @if ($currentUser->hasMenuAccess('departments'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('departments') || Request::is('departments/*')) ? 'active' : '' }}"
          href="{{ route('departments.index') }}"
          title="Kelola data departemen"
          data-testid="sidebar-menu-departments"
          data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-building text-sm {{ (Request::is('departments') || Request::is('departments/*')) ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Departemen</span>
        </a>
      </li>
      @endif
      @if ($currentUser->hasMenuAccess('positions'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('positions') || Request::is('positions/*')) ? 'active' : '' }}"
          href="{{ route('positions.index') }}"
          title="Kelola data posisi"
          data-testid="sidebar-menu-positions"
          data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-briefcase text-sm {{ (Request::is('positions') || Request::is('positions/*')) ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Posisi</span>
        </a>
      </li>
      @endif

      @if ($currentUser->hasMenuAccess('work_locations'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('work_locations') || Request::is('work_locations/*')) ? 'active' : '' }}"
          href="{{ route('work_locations.index') }}"
          title="Kelola data lokasi kerja"
          data-testid="sidebar-menu-work-locations"
          data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-map-marker-alt text-sm {{ (Request::is('work_locations') || Request::is('work_locations/*')) ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Lokasi Kerja</span>
        </a>
      </li>
      @endif

      @if ($currentUser->hasMenuAccess('tenants'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('tenants') || Request::is('tenants/*') || Request::is('tenant-management')) ? 'active' : '' }}"
          href="{{ route('tenants.index') }}"
          title="Kelola data tenant"
          data-testid="sidebar-menu-tenants"
          data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-layer-group text-sm {{ (Request::is('tenants') || Request::is('tenants/*') || Request::is('tenant-management')) ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Tenant</span>
        </a>
      </li>
      @endif
      @if ($currentUser->hasMenuAccess('roles'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('roles') || Request::is('roles/*')) ? 'active' : '' }}" href="{{ route('roles.index') }}" title="Kelola data role dan hak akses" data-testid="sidebar-menu-roles" data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-id-badge text-sm {{ (Request::is('roles') || Request::is('roles/*')) ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Role</span>
        </a>
      </li>
      @endif
      @if(hasMenuAccess('leaves.manage'))
      <li class="nav-item pb-2" data-testid="sidebar-menu-jenis-cuti">
        <a class="nav-link {{ Request::is('jenis-cuti') || Request::is('jenis-cuti/*') ? 'active' : '' }}" href="{{ route('jenis-cuti.index') }}" title="Kelola master jenis cuti" data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-layer-group text-sm {{ Request::is('jenis-cuti') || Request::is('jenis-cuti/*') ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Jenis Cuti</span>
        </a>
      </li>
      @endif
      @if ($currentUser->hasMenuAccess('payroll'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('deduction-rules') || Request::is('deduction-rules/*')) ? 'active' : '' }}" href="{{ route('deduction_rules.index') }}" title="Kelola master potongan payroll" data-testid="sidebar-menu-deduction-rules" data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-percent text-sm {{ (Request::is('deduction-rules') ? 'text-white' : 'text-dark') }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Potongan</span>
        </a>
      </li>
      @endif
      @endif

      @if ($currentUser && count(array_intersect(['attendances', 'leaves', 'leaves.manage', 'leaves.reports', 'payroll', 'payroll.reports'], $currentUser->accessibleMenuKeys())) > 0)
      <li class="nav-item mt-2" data-testid="sidebar-group-operasional">
        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Operasional Absensi</h6>
      </li>
      @if(hasMenuAccess('attendances'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ Request::is('attendances') ? 'active' : '' }}" href="{{ route('attendances.index') }}" data-testid="sidebar-menu-attendances">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-calendar-check text-sm {{ Request::is('attendances') ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Absensi Karyawan</span>
        </a>
      </li>
      @endif
      @if(hasMenuAccess('leaves'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ Request::is('leaves') ? 'active' : '' }}" href="{{ route('leaves.index') }}" data-testid="sidebar-menu-leaves">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-plane-departure text-sm {{ Request::is('leaves') ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Cuti / Izin</span>
        </a>
      </li>
      @endif
      @if(hasMenuAccess('leaves.reports'))
      <li class="nav-item pb-2" data-testid="sidebar-menu-leaves-reports">
        <a class="nav-link {{ Request::is('leaves/reports') ? 'active' : '' }}" href="{{ route('leaves.reports') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-chart-line text-sm {{ Request::is('leaves/reports') ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Laporan Cuti / Izin</span>
        </a>
      </li>
      @endif
      @endif

      @if ($currentUser && count(array_intersect(['lembur', 'lembur.submit', 'lembur.approval', 'lembur.reports'], $currentUser->accessibleMenuKeys())) > 0)
      <li class="nav-item mt-2" data-testid="sidebar-group-operasional-lembur">
        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Operasional Lembur</h6>
      </li>
      @if(hasMenuAccess('lembur.submit'))
      <li class="nav-item pb-2" data-testid="sidebar-menu-lembur-submit">
        <a class="nav-link {{ Request::is('lembur') || Request::is('lembur/create') ? 'active' : '' }}" href="{{ route('lembur.index') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-file-signature text-sm {{ Request::is('lembur') || Request::is('lembur/create') ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Pengajuan Lembur</span>
        </a>
      </li>
      @endif
      @if(hasMenuAccess('lembur.approval'))
      <li class="nav-item pb-2" data-testid="sidebar-menu-lembur-approval">
        <a class="nav-link {{ Request::is('lembur/approval') ? 'active' : '' }}" href="{{ route('lembur.approval') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-user-check text-sm {{ Request::is('lembur/approval') ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Persetujuan Lembur</span>
        </a>
      </li>
      @endif
      @if(hasMenuAccess('lembur.reports'))
      <li class="nav-item pb-2" data-testid="sidebar-menu-lembur-reports">
        <a class="nav-link {{ Request::is('lembur/reports') ? 'active' : '' }}" href="{{ route('lembur.reports') }}">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-chart-bar text-sm {{ Request::is('lembur/reports') ? 'text-white' : 'text-dark' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Laporan Lembur</span>
        </a>
      </li>
      @endif

      @if ($currentUser->hasMenuAccess('payroll') || $currentUser->hasMenuAccess('payroll.reports'))
      <li class="nav-item mt-2" data-testid="sidebar-group-payroll">
        <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Operasional Payroll</h6>
      </li>
      @endif

      @if ($currentUser->hasMenuAccess('payroll'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ (Request::is('payroll') ? 'active' : '') }}" href="{{ route('payroll.index') }}" title="Kelola payroll" data-testid="sidebar-menu-payroll" data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="fas fa-money-bill-wave text-sm {{ (Request::is('payroll') ? 'text-white' : 'text-dark') }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Payroll</span>
        </a>
      </li>
      @endif
      @if(hasMenuAccess('payroll.reports'))
      <li class="nav-item pb-2">
        <a class="nav-link {{ Request::is('payroll/reports') ? 'active' : '' }}" href="{{ route('payroll.reports') }}" title="Lihat laporan payroll" data-testid="sidebar-menu-payroll-reports" data-bs-toggle="tooltip">
          <div class="icon icon-shape icon-sm shadow border-radius-md bg-white text-center me-2 d-flex align-items-center justify-content-center">
            <i class="ni ni-collection text-sm {{ Request::is('payroll/reports') ? 'text-white' : 'text-info' }}" aria-hidden="true"></i>
          </div>
          <span class="nav-link-text ms-1">Laporan Payroll</span>
        </a>
      </li>
      @endif
      @endif
    </ul>
  </div>
  <div class="sidenav-footer mx-3 ">
    <div class="card card-background shadow-none card-background-mask-secondary" id="sidenavCard" data-testid="sidebar-system-status-footer">
      <div class="full-background" style="background-image: url('{{ asset('assets/img/curved-images/white-curved.jpeg') }}')"></div>
      <div class="card-body text-start p-3 w-100">
        <div class="d-flex align-items-center mb-2">
          <i class="fa {{ $systemStatus == 'normal' ? 'fa-check-circle' : 'fa-exclamation-circle' }} text-white me-2" aria-hidden="true" data-testid="sidebar-system-status-icon"></i>
          <h6 class="text-white up mb-0">Status Sistem:</h6>
        </div>
        <span class="badge {{ $systemStatus == 'normal' ? 'bg-gradient-success' : 'bg-gradient-danger' }}" data-testid="sidebar-system-status-badge">
          {{ $systemStatus == 'normal' ? 'Normal' : 'Ada Error' }}
        </span>
      </div>
    </div>
  </div>
</aside>
