<!-- Frontend Navbar (Portal Siswa) -->
<div id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-name="app-header-sticky">
	<div class="app-container container-xxl d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
		<!-- Logo -->
		<div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-lg-15">
			<button type="button" id="portalBurger" class="btn btn-icon p-0 me-3" aria-label="Buka menu"><span style="font-size:26px;line-height:1;color:#fff">&#9776;</span></button>
			<a href="{{ route('student.dashboard') }}">
				<img alt="Logo" src="{{ asset('assets/media/logos/lms.png') }}" class="h-30px h-lg-40px" />
			</a>
		</div>

		<div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">
			<!-- Menu Links -->
			<div class="app-header-menu app-header-mobile-drawer align-items-stretch">
				<div class="menu menu-rounded menu-column menu-lg-row my-5 my-lg-0 align-items-stretch fw-semibold px-2 px-lg-0">
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.dashboard') }}" class="menu-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
							<span class="menu-title">Dashboard</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.attendance') }}" class="menu-link {{ request()->routeIs('student.attendance*') ? 'active' : '' }}">
							<span class="menu-title">Absensi</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.learning-modules.index') }}" class="menu-link {{ request()->routeIs('student.learning-modules.*') ? 'active' : '' }}">
							<span class="menu-title">Materi Belajar</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.assignments.index') }}" class="menu-link {{ request()->routeIs('student.assignments.*') ? 'active' : '' }}">
							<span class="menu-title">Tugas Saya</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.exams.index') }}" class="menu-link {{ request()->routeIs('student.exams.*') ? 'active' : '' }}">
							<span class="menu-title">Ujian</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.timetable') }}" class="menu-link {{ request()->routeIs('student.timetable') ? 'active' : '' }}">
							<span class="menu-title">Jadwal</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.rapor.index') }}" class="menu-link {{ request()->routeIs('student.rapor.*') ? 'active' : '' }}">
							<span class="menu-title">e-Rapor</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.library.index') }}" class="menu-link {{ request()->routeIs('student.library.*') ? 'active' : '' }}">
							<span class="menu-title">Perpustakaan</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('portal.leaderboard') }}" class="menu-link {{ request()->routeIs('portal.leaderboard') ? 'active' : '' }}">
							<span class="menu-title">Peringkat</span>
						</a>
					</div>
					<div class="menu-item me-0 me-lg-2">
						<a href="{{ route('student.chat.index') }}" class="menu-link {{ request()->routeIs('student.chat.*') ? 'active' : '' }}">
							<span class="menu-title">💬 Pesan</span>
						</a>
					</div>
				</div>
			</div>

			<div id="portalDrawerBackdrop"></div>
			<script>
			document.addEventListener('DOMContentLoaded', function(){
				var hdr = document.getElementById('kt_app_header'); if(!hdr) return;
				var menu = hdr.querySelector('.app-header-menu');
				var burger = document.getElementById('portalBurger');
				var bd = document.getElementById('portalDrawerBackdrop');
				function openD(){ if(menu) menu.classList.add('drawer-open'); if(bd) bd.classList.add('show'); }
				function closeD(){ if(menu) menu.classList.remove('drawer-open'); if(bd) bd.classList.remove('show'); }
				if(burger) burger.addEventListener('click', openD);
				if(bd) bd.addEventListener('click', closeD);
				if(menu) menu.querySelectorAll('.menu-link').forEach(function(l){ l.addEventListener('click', closeD); });
			});
			</script>

			<!-- Right Navbar Items -->
			<div class="app-navbar flex-shrink-0">
				<!-- Notification Bell -->
				<div class="app-navbar-item ms-1 ms-md-4">
					<div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px position-relative"
						 data-bs-toggle="offcanvas" data-bs-target="#kt_student_notification_drawer">
						<i class="ki-duotone ki-notification-status fs-2">
							<span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
						</i>
						<span class="bullet bullet-dot bg-danger h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink"></span>
					</div>
				</div>

				<!-- Theme Toggle -->
				<div class="app-navbar-item ms-1 ms-md-4">
					<a href="#" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px"
						data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
						<i class="ki-duotone ki-night-day theme-light-show fs-2">
							<span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
							<span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span>
							<span class="path9"></span><span class="path10"></span>
						</i>
						<i class="ki-duotone ki-moon theme-dark-show fs-2"><span class="path1"></span><span class="path2"></span></i>
					</a>
					<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
						<div class="menu-item px-3 my-0">
							<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
								<span class="menu-icon" data-kt-element="icon"><i class="ki-duotone ki-night-day fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span><span class="path6"></span><span class="path7"></span><span class="path8"></span><span class="path9"></span><span class="path10"></span></i></span>
								<span class="menu-title">Light</span>
							</a>
						</div>
						<div class="menu-item px-3 my-0">
							<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
								<span class="menu-icon" data-kt-element="icon"><i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span class="path2"></span></i></span>
								<span class="menu-title">Dark</span>
							</a>
						</div>
						<div class="menu-item px-3 my-0">
							<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
								<span class="menu-icon" data-kt-element="icon"><i class="ki-duotone ki-screen fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i></span>
								<span class="menu-title">System</span>
							</a>
						</div>
					</div>
				</div>

				<!-- User Menu -->
				<div class="app-navbar-item ms-1 ms-md-4">
					<div class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
						<i class="ki-outline ki-user fs-2"></i>
					</div>
					<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
						<div class="menu-item px-3">
							<div class="menu-content d-flex align-items-center px-3">
								<div class="symbol symbol-50px me-5">
									<img alt="Avatar" src="{{ auth()->user()->avatar_url }}" />
								</div>
								<div class="d-flex flex-column">
									<div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->name }}
										<span class="badge badge-light-primary fw-bold fs-8 px-2 py-1 ms-2">Siswa</span>
									</div>
									<a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email }}</a>
								</div>
							</div>
						</div>
						<div class="separator my-2"></div>
						<div class="menu-item px-5">
							<a href="{{ route('student.account.index') }}" class="menu-link px-5">My Profile</a>
						</div>
						<div class="separator my-2"></div>
						<div class="menu-item px-5">
							<a href="#" onclick="confirmSignOut(event)" class="menu-link px-5">Sign Out</a>
							<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
								@csrf
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
