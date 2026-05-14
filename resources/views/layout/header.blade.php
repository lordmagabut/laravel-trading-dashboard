<div class="horizontal-menu">
  <nav class="navbar top-navbar">
    <div class="container">
      <div class="navbar-content">
        <a href="#" class="navbar-brand">
          Trading<span>Dash</span>
        </a>
        <form class="search-form">
          <div class="input-group">
            <div class="input-group-text">
              <i data-feather="search"></i>
            </div>
            <input type="text" class="form-control" id="navbarForm" placeholder="Search here...">
          </div>
        </form>
        <ul class="navbar-nav">
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <img src="{{ url('assets/images/flags/us.svg') }}" class="wd-20 me-1" title="us" alt="us"> <span class="ms-1 me-1 d-none d-md-inline-block">English</span>
            </a>
            <div class="dropdown-menu" aria-labelledby="languageDropdown">
              <a href="javascript:;" class="dropdown-item py-2"> <img src="{{ url('assets/images/flags/us.svg') }}" class="wd-20 me-1" title="us" alt="us"> <span class="ms-1"> English </span></a>
              <a href="javascript:;" class="dropdown-item py-2"> <img src="{{ url('assets/images/flags/fr.svg') }}" class="wd-20 me-1" title="fr" alt="fr"> <span class="ms-1"> French </span></a>
              <a href="javascript:;" class="dropdown-item py-2"> <img src="{{ url('assets/images/flags/de.svg') }}" class="wd-20 me-1" title="de" alt="de"> <span class="ms-1"> German </span></a>
              <a href="javascript:;" class="dropdown-item py-2"> <img src="{{ url('assets/images/flags/pt.svg') }}" class="wd-20 me-1" title="pt" alt="pt"> <span class="ms-1"> Portuguese </span></a>
              <a href="javascript:;" class="dropdown-item py-2"> <img src="{{ url('assets/images/flags/es.svg') }}" class="wd-20 me-1" title="es" alt="es"> <span class="ms-1"> Spanish </span></a>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="appsDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i data-feather="grid"></i>
            </a>
            <div class="dropdown-menu p-0" aria-labelledby="appsDropdown">
              <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                <p class="mb-0 fw-bold">Web Apps</p>
                <a href="javascript:;" class="text-muted">Edit</a>
              </div>
              <div class="row g-0 p-1">
                <div class="col-3 text-center">
                  <a href="{{ url('/apps/chat') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="message-square" class="icon-lg mb-1"></i><p class="tx-12">Chat</p></a>
                </div>
                <div class="col-3 text-center">
                  <a href="{{ url('/apps/calendar') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="calendar" class="icon-lg mb-1"></i><p class="tx-12">Calendar</p></a>
                </div>
                <div class="col-3 text-center">
                  <a href="{{ url('/email/inbox') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="mail" class="icon-lg mb-1"></i><p class="tx-12">Email</p></a>
                </div>
                <div class="col-3 text-center">
                  <a href="{{ url('/general/profile') }}" class="dropdown-item d-flex flex-column align-items-center justify-content-center wd-70 ht-70"><i data-feather="instagram" class="icon-lg mb-1"></i><p class="tx-12">Profile</p></a>
                </div>
              </div>
              <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
                <a href="javascript:;">View all</a>
              </div>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="messageDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i data-feather="mail"></i>
            </a>
            <div class="dropdown-menu p-0" aria-labelledby="messageDropdown">
              <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                <p>9 New Messages</p>
                <a href="javascript:;" class="text-muted">Clear all</a>
              </div>
              <div class="p-1">
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="me-3">
                    <img class="wd-30 ht-30 rounded-circle" src="{{ url('https://via.placeholder.com/30x30') }}" alt="userr">
                  </div>
                  <div class="d-flex justify-content-between flex-grow-1">
                    <div class="me-4">
                      <p>Leonardo Payne</p>
                      <p class="tx-12 text-muted">Project status</p>
                    </div>
                    <p class="tx-12 text-muted">2 min ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="me-3">
                    <img class="wd-30 ht-30 rounded-circle" src="{{ url('https://via.placeholder.com/30x30') }}" alt="userr">
                  </div>
                  <div class="d-flex justify-content-between flex-grow-1">
                    <div class="me-4">
                      <p>Carl Henson</p>
                      <p class="tx-12 text-muted">Client meeting</p>
                    </div>
                    <p class="tx-12 text-muted">30 min ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="me-3">
                    <img class="wd-30 ht-30 rounded-circle" src="{{ url('https://via.placeholder.com/30x30') }}" alt="userr">
                  </div>
                  <div class="d-flex justify-content-between flex-grow-1">
                    <div class="me-4">
                      <p>Jensen Combs</p>
                      <p class="tx-12 text-muted">Project updates</p>
                    </div>
                    <p class="tx-12 text-muted">1 hrs ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="me-3">
                    <img class="wd-30 ht-30 rounded-circle" src="{{ url('https://via.placeholder.com/30x30') }}" alt="userr">
                  </div>
                  <div class="d-flex justify-content-between flex-grow-1">
                    <div class="me-4">
                      <p>Amiah Burton</p>
                      <p class="tx-12 text-muted">Project deatline</p>
                    </div>
                    <p class="tx-12 text-muted">2 hrs ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="me-3">
                    <img class="wd-30 ht-30 rounded-circle" src="{{ url('https://via.placeholder.com/30x30') }}" alt="userr">
                  </div>
                  <div class="d-flex justify-content-between flex-grow-1">
                    <div class="me-4">
                      <p>Yaretzi Mayo</p>
                      <p class="tx-12 text-muted">New record</p>
                    </div>
                    <p class="tx-12 text-muted">5 hrs ago</p>
                  </div>	
                </a>
              </div>
              <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
                <a href="javascript:;">View all</a>
              </div>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <i data-feather="bell"></i>
              <div class="indicator">
                <div class="circle"></div>
              </div>
            </a>
            <div class="dropdown-menu p-0" aria-labelledby="notificationDropdown">
              <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom">
                <p>6 New Notifications</p>
                <a href="javascript:;" class="text-muted">Clear all</a>
              </div>
              <div class="p-1">
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="wd-30 ht-30 d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                    <i class="icon-sm text-white" data-feather="gift"></i>
                  </div>
                  <div class="flex-grow-1 me-2">
                    <p>New Order Recieved</p>
                    <p class="tx-12 text-muted">30 min ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="wd-30 ht-30 d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                    <i class="icon-sm text-white" data-feather="alert-circle"></i>
                  </div>
                  <div class="flex-grow-1 me-2">
                    <p>Server Limit Reached!</p>
                    <p class="tx-12 text-muted">1 hrs ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="wd-30 ht-30 d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                    <img class="wd-30 ht-30 rounded-circle" src="{{ url('https://via.placeholder.com/30x30') }}" alt="userr">
                  </div>
                  <div class="flex-grow-1 me-2">
                    <p>New customer registered</p>
                    <p class="tx-12 text-muted">2 sec ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="wd-30 ht-30 d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                    <i class="icon-sm text-white" data-feather="layers"></i>
                  </div>
                  <div class="flex-grow-1 me-2">
                    <p>Apps are ready for update</p>
                    <p class="tx-12 text-muted">5 hrs ago</p>
                  </div>	
                </a>
                <a href="javascript:;" class="dropdown-item d-flex align-items-center py-2">
                  <div class="wd-30 ht-30 d-flex align-items-center justify-content-center bg-primary rounded-circle me-3">
                    <i class="icon-sm text-white" data-feather="download"></i>
                  </div>
                  <div class="flex-grow-1 me-2">
                    <p>Download completed</p>
                    <p class="tx-12 text-muted">6 hrs ago</p>
                  </div>	
                </a>
              </div>
              <div class="px-3 py-2 d-flex align-items-center justify-content-center border-top">
                <a href="javascript:;">View all</a>
              </div>
            </div>
          </li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" id="profileDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
              <img class="wd-30 ht-30 rounded-circle" src="{{ url('https://via.placeholder.com/30x30') }}" alt="profile">
            </a>
            <div class="dropdown-menu p-0" aria-labelledby="profileDropdown">
              <div class="d-flex flex-column align-items-center border-bottom px-5 py-3">
                <div class="mb-3">
                  <img class="wd-80 ht-80 rounded-circle" src="{{ url('https://via.placeholder.com/80x80') }}" alt="">
                </div>
                <div class="text-center">
                  <p class="tx-16 fw-bolder">{{ auth()->user()->name ?? 'Guest' }}</p>
                  <p class="tx-12 text-muted">{{ auth()->user()->email ?? '-' }}</p>
                </div>
              </div>
              <ul class="list-unstyled p-1">
                <li class="dropdown-item py-2">
                  <a href="{{ url('/general/profile') }}" class="text-body ms-0">
                    <i class="me-2 icon-md" data-feather="user"></i>
                    <span>Profile</span>
                  </a>
                </li>
                @can('manage users')
                <li class="dropdown-item py-2">
                  <a href="{{ route('users.index') }}" class="text-body ms-0">
                    <i class="me-2 icon-md" data-feather="users"></i>
                    <span>Users</span>
                  </a>
                </li>
                @endcan
                @can('manage roles')
                <li class="dropdown-item py-2">
                  <a href="{{ route('roles.index') }}" class="text-body ms-0">
                    <i class="me-2 icon-md" data-feather="shield"></i>
                    <span>Roles</span>
                  </a>
                </li>
                @endcan
                <li class="dropdown-item py-2">
                  <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link text-body text-decoration-none p-0 ms-0 d-flex align-items-center w-100">
                      <i class="me-2 icon-md" data-feather="log-out"></i>
                      <span>Log Out</span>
                    </button>
                  </form>
                </li>
              </ul>
            </div>
          </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="horizontal-menu-toggle">
          <i data-feather="menu"></i>					
        </button>
      </div>
    </div>
  </nav>
  <nav class="bottom-navbar">
    <div class="container">
      <ul class="nav page-navigation">
        @can('view dashboard')
        <li class="nav-item {{ function_exists('active_class') ? active_class(['/']) : '' }}">
          <a href="{{ route('dashboard') }}" class="nav-link">
            <i class="link-icon" data-feather="home"></i>
            <span class="link-title">Dashboard</span>
          </a>
        </li>
        @endcan

        @can('manage bot pairs')
        <li class="nav-item {{ function_exists('active_class') ? active_class(['bot-pair-settings', 'bot-pair-settings/*']) : '' }}">
          <a href="{{ route('bot-pairs.index') }}" class="nav-link">
            <i class="link-icon" data-feather="settings"></i>
            <span class="link-title">Bot Pair Settings</span>
          </a>
        </li>
        @endcan

        @if(
          auth()->user()?->can('view market chart') ||
          auth()->user()?->can('view technical context') ||
          auth()->user()?->can('manage technical analyses') ||
          auth()->user()?->can('review trade signals')
        )
        <li class="nav-item {{ active_class(['market-chart', 'technical-context', 'technical-analyses', 'technical-analyses/*', 'signal-dashboard']) }}">
          <a href="#" class="nav-link">
            <i class="link-icon" data-feather="database"></i>
            <span class="menu-title">Workflow</span>
            <i class="link-arrow"></i>
          </a>
          <div class="submenu">
            <ul class="submenu-item">
              @can('view market chart')
              <li class="nav-item"><a class="nav-link {{ active_class(['market-chart']) }}" href="{{ route('market.chart') }}">1. Market Chart</a></li>
              @endcan
              @can('view technical context')
              <li class="nav-item"><a class="nav-link {{ active_class(['technical-context']) }}" href="{{ route('technical.context.page') }}">2. Technical Context</a></li>
              @endcan
              @can('manage technical analyses')
              <li class="nav-item"><a class="nav-link {{ active_class(['technical-analyses', 'technical-analyses/*']) }}" href="{{ route('technical-analyses.index') }}">3. Technical Analyses</a></li>
              @endcan
              @can('manage fundamental analyses')
              <li class="nav-item"><a class="nav-link {{ active_class(['fundamental-analyses', 'fundamental-analyses/*']) }}" href="{{ route('fundamental-analyses.index') }}">4. Fundamental Analyses</a></li>
              @endcan
              @can('review trade signals')
              <li class="nav-item"><a class="nav-link {{ active_class(['signal-dashboard']) }}" href="{{ route('signals.index') }}">5. Signal Dashboard</a></li>
              @endcan
            </ul>
          </div>
        </li>
        @endif
      </ul>
    </div>
  </nav>
</div>
