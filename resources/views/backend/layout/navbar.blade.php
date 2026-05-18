					<!--begin::Header-->
					<div id="kt_header" class="header" data-kt-sticky="true" data-kt-sticky-name="header" data-kt-sticky-animation="false" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
						<!--begin::Container-->
						<div class="container-xxl d-flex align-items-center flex-lg-stack">
							<!--begin::Brand-->
							<div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0 me-2 me-lg-5">
								<!--begin::Wrapper-->
								<div class="flex-grow-1">
									<!--begin::Aside toggle-->
									<button class="btn btn-icon btn-color-gray-800 btn-active-color-primary ms-n4 me-lg-12" id="kt_aside_toggle">
										<i class="ki-duotone ki-abstract-14 fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
										</i>
									</button>
									<!--end::Aside toggle-->
									<!--begin::Header Logo-->
									@php
										$dashboardRoute = auth()->user()->hasRole('Siswa') ? 'student.dashboard' : 'dashboard';
									@endphp
									<a href="{{ route($dashboardRoute) }}">
										<img alt="Logo" src="{{ asset('assets/media/logos/lms_alt.jpeg') }}" class="h-30px h-lg-40px" />
									</a>
									<!--end::Header Logo-->
								</div>
								<!--end::Wrapper-->
								<!--begin:Search-->
								<div class="ms-5 ms-md-17 d-flex align-items-center">
									<!--begin::Search-->
									<div id="kt_header_search" class="header-search d-flex align-items-center w-lg-400px" data-kt-search-keypress="true" data-kt-search-min-length="2" data-kt-search-enter="enter" data-kt-search-layout="menu" data-kt-search-responsive="lg" data-kt-menu-trigger="auto" data-kt-menu-permanent="true" data-kt-menu-placement="{default: 'bottom-end', lg: 'bottom-start'}">
										<!--begin::Tablet and mobile search toggle-->
										<div data-kt-search-element="toggle" class="search-toggle-mobile d-flex d-lg-none align-items-center">
											<div class="d-flex btn btn-icon btn-color-gray-800 btn-active-light-primary w-30px h-30px w-md-40px h-md-40px">
												<i class="ki-duotone ki-magnifier fs-1">
													<span class="path1"></span>
													<span class="path2"></span>
												</i>
											</div>
										</div>
										<!--end::Tablet and mobile search toggle-->
										<!--begin::Form(use d-none d-lg-block classes for responsive search)-->
										<form data-kt-search-element="form" class="d-none d-lg-block w-100 position-relative mb-5 mb-lg-0" autocomplete="off">
											<!--begin::Hidden input(Added to disable form autocomplete)-->
											<input type="hidden" />
											<!--end::Hidden input-->
											<!--begin::Icon-->
											<i class="ki-duotone ki-magnifier search-icon fs-2 text-gray-500 position-absolute top-50 translate-middle-y ms-5">
												<span class="path1"></span>
												<span class="path2"></span>
											</i>
											<!--end::Icon-->
											<!--begin::Input-->
											<input type="text" class="search-input form-control form-control-solid ps-13" name="search" value="" placeholder="Search..." data-kt-search-element="input" />
											<!--end::Input-->
											<!--begin::Spinner-->
											<span class="search-spinner position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-5" data-kt-search-element="spinner">
												<span class="spinner-border h-15px w-15px align-middle text-gray-500"></span>
											</span>
											<!--end::Spinner-->
											<!--begin::Reset-->
											<span class="search-reset btn btn-flush btn-active-color-primary position-absolute top-50 end-0 translate-middle-y lh-0 d-none me-4" data-kt-search-element="clear">
												<i class="ki-duotone ki-cross fs-2 fs-lg-1 me-0">
													<span class="path1"></span>
													<span class="path2"></span>
												</i>
											</span>
											<!--end::Reset-->
										</form>
										<!--end::Form-->
										<!--begin::Menu-->
										<div data-kt-search-element="content" class="menu menu-sub menu-sub-dropdown py-7 px-7 overflow-hidden w-300px w-md-350px">
											<!--begin::Wrapper-->
											<div data-kt-search-element="wrapper">
												<!--begin::Recently viewed-->
												<div id="custom-search-results" class="scroll-y mh-200px mh-lg-350px">
													<div class="text-muted fs-7 text-center py-10" id="search-initial-msg">
														Ketik untuk mencari menu...
													</div>
												</div>
												<!--end::Recently viewed-->
												<!--begin::Empty-->
												<div data-kt-search-element="empty" class="text-center d-none" id="custom-search-empty">
													<!--begin::Icon-->
													<div class="pt-10 pb-10">
														<i class="ki-duotone ki-search-list fs-4x opacity-50">
															<span class="path1"></span><span class="path2"></span><span class="path3"></span>
														</i>
													</div>
													<!--end::Icon-->
													<!--begin::Message-->
													<div class="pb-15 fw-semibold">
														<h3 class="text-gray-600 fs-5 mb-2">Tidak ditemukan</h3>
														<div class="text-muted fs-7">Coba kata kunci lain</div>
													</div>
													<!--end::Message-->
												</div>
												<!--end::Empty-->
											</div>
											<!--end::Wrapper-->
										</div>
										<!--end::Menu-->

@push('scripts')
<script>
	const searchInput = document.querySelector('input[name="search"]');
	const resultsContainer = document.getElementById('custom-search-results');
	const emptyContainer = document.getElementById('custom-search-empty');
	const initialMsg = document.getElementById('search-initial-msg');

	// Definisi menu-menu aplikasi
	const appMenus = [
		// Admin
		{ title: 'Dashboard Admin', url: '{{ route("dashboard") }}', icon: 'ki-element-11', type: 'admin' },
		{ title: 'Data Sekolah', url: '{{ route("schools.index") }}', icon: 'ki-bank', type: 'admin' },
		{ title: 'Tahun Ajaran', url: '{{ route("academic-years.index") }}', icon: 'ki-calendar', type: 'admin' },
		{ title: 'Ruang Kelas', url: '{{ route("class-rooms.index") }}', icon: 'ki-shop', type: 'admin' },
		{ title: 'Data Siswa', url: '{{ route("students.index") }}', icon: 'ki-profile-user', type: 'admin' },
		{ title: 'Data Guru', url: '{{ route("teachers.index") }}', icon: 'ki-badge', type: 'admin' },
		{ title: 'Mata Pelajaran', url: '{{ route("subjects.index") }}', icon: 'ki-book-square', type: 'admin' },
		{ title: 'Jadwal Pelajaran', url: '{{ route("schedules.index") }}', icon: 'ki-calendar-8', type: 'admin' },
		{ title: 'Modul Pembelajaran', url: '{{ route("learning-modules.index") }}', icon: 'ki-book', type: 'admin' },
		{ title: 'Pengaturan Absensi', url: '{{ route("attendance-settings.index") }}', icon: 'ki-setting-2', type: 'admin' },
		{ title: 'Penugasan Guru', url: '{{ route("teaching-assignments.index") }}', icon: 'ki-teacher', type: 'admin' },
		{ title: 'Penugasan Siswa', url: '{{ route("assignments.index") }}', icon: 'ki-notepad-edit', type: 'admin' },
		{ title: 'Rombongan Belajar', url: '{{ route("enrollments.index") }}', icon: 'ki-people', type: 'admin' },
		
		// Siswa (Portal)
		{ title: 'Portal Siswa', url: '{{ route("student.dashboard") }}', icon: 'ki-home', type: 'student' },
		{ title: 'Absensi Saya', url: '{{ route("student.attendance") }}', icon: 'ki-fingerprint-scan', type: 'student' },
		{ title: 'Leaderboard & Lencana', url: '{{ route("portal.leaderboard") }}', icon: 'ki-cup', type: 'student' },
	];

	const isStudent = {{ auth()->user()->hasRole('Siswa') ? 'true' : 'false' }};
	const availableMenus = appMenus.filter(m => isStudent ? m.type === 'student' : m.type === 'admin');

	searchInput.addEventListener('input', function(e) {
		const keyword = e.target.value.toLowerCase();
		resultsContainer.innerHTML = '';
		
		if (keyword.length < 2) {
			resultsContainer.innerHTML = '<div class="text-muted fs-7 text-center py-10">Ketik untuk mencari menu...</div>';
			emptyContainer.classList.add('d-none');
			return;
		}

		const filtered = availableMenus.filter(m => m.title.toLowerCase().includes(keyword));

		if (filtered.length > 0) {
			emptyContainer.classList.add('d-none');
			filtered.forEach(menu => {
				const item = `
				<a href="${menu.url}" class="d-flex text-gray-900 text-hover-primary align-items-center mb-5">
					<div class="symbol symbol-40px me-4">
						<span class="symbol-label bg-light">
							<i class="ki-duotone ${menu.icon} fs-2 text-primary">
								<span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
							</i>
						</span>
					</div>
					<div class="d-flex flex-column justify-content-start fw-semibold">
						<span class="fs-6 fw-semibold">${menu.title}</span>
						<span class="fs-7 fw-semibold text-muted">Menu Aplikasi</span>
					</div>
				</a>`;
				resultsContainer.innerHTML += item;
			});
		} else {
			emptyContainer.classList.remove('d-none');
		}
	});
</script>
@endpush
									</div>
									<!--end::Search-->
								</div>
								<!--end:Search-->
							</div>
							<!--end::Brand-->
							<!--begin::Toolbar wrapper-->
							<div class="d-flex align-items-stretch flex-shrink-0">
								<!--begin::Activities-->
								<div class="d-flex align-items-center ms-1 ms-lg-3">
									<!--begin::Drawer toggle-->
									@php
										$unreadCount = auth()->check() ? \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count() : 0;
									@endphp
									<div class="position-relative btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" id="kt_notification_toggle" data-bs-toggle="offcanvas" data-bs-target="#kt_notification_drawer" aria-controls="kt_notification_drawer">
										<i class="ki-duotone ki-notification-status fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
											<span class="path3"></span>
											<span class="path4"></span>
										</i>
										@if($unreadCount > 0)
											<span id="notification-count-badge" class="badge badge-circle badge-danger position-absolute translate-middle top-0 start-100 fs-9 h-18px w-18px d-flex align-items-center justify-content-center" style="margin-left: -5px; margin-top: 5px;">{{ $unreadCount }}</span>
										@else
											<span class="bullet bullet-dot bg-danger h-6px w-6px position-absolute translate-middle top-0 start-50 animation-blink"></span>
										@endif
									</div>
									<!--end::Drawer toggle-->
								</div>
								<!--end::Activities-->
								<!--begin::Theme mode-->
								<div class="d-flex align-items-center ms-1 ms-lg-3">
									<!--begin::Menu toggle-->
									<a href="#" class="btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
										<i class="ki-duotone ki-night-day theme-light-show fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
											<span class="path3"></span>
											<span class="path4"></span>
											<span class="path5"></span>
											<span class="path6"></span>
											<span class="path7"></span>
											<span class="path8"></span>
											<span class="path9"></span>
											<span class="path10"></span>
										</i>
										<i class="ki-duotone ki-moon theme-dark-show fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
										</i>
									</a>
									<!--begin::Menu toggle-->
									<!--begin::Menu-->
									<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
										<!--begin::Menu item-->
										<div class="menu-item px-3 my-0">
											<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
												<span class="menu-icon" data-kt-element="icon">
													<i class="ki-duotone ki-night-day fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
														<span class="path5"></span>
														<span class="path6"></span>
														<span class="path7"></span>
														<span class="path8"></span>
														<span class="path9"></span>
														<span class="path10"></span>
													</i>
												</span>
												<span class="menu-title">Light</span>
											</a>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu item-->
										<div class="menu-item px-3 my-0">
											<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
												<span class="menu-icon" data-kt-element="icon">
													<i class="ki-duotone ki-moon fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
													</i>
												</span>
												<span class="menu-title">Dark</span>
											</a>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu item-->
										<div class="menu-item px-3 my-0">
											<a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
												<span class="menu-icon" data-kt-element="icon">
													<i class="ki-duotone ki-screen fs-2">
														<span class="path1"></span>
														<span class="path2"></span>
														<span class="path3"></span>
														<span class="path4"></span>
													</i>
												</span>
												<span class="menu-title">System</span>
											</a>
										</div>
										<!--end::Menu item-->
									</div>
									<!--end::Menu-->
								</div>
								<!--end::Theme mode-->
								<!--begin::User menu-->
								<div class="d-flex align-items-center ms-1 ms-lg-3">
									<!--begin::Menu wrapper-->
									<div class="btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px position-relative btn btn-color-gray-800 btn-icon btn-active-light-primary w-30px h-30px w-md-40px h-md-40px" data-kt-menu-trigger="click" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
										<i class="ki-duotone ki-user fs-1">
											<span class="path1"></span>
											<span class="path2"></span>
										</i>
									</div>
									<!--begin::User account menu-->
									<div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
										<!--begin::Menu item-->
										<div class="menu-item px-3">
											<div class="menu-content d-flex align-items-center px-3">
												<!--begin::Avatar-->
												<div class="symbol symbol-50px me-5">
													<img alt="Logo" src="{{ asset('assets/media/avatars/300-1.jpg') }}" />
												</div>
												<!--end::Avatar-->
												<!--begin::Username-->
												<div class="d-flex flex-column">
													<div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->name ?? 'Administrator' }} 
													<span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">{{ auth()->user()?->getRoleNames()->first() ?? 'Admin' }}</span></div>
													<a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email ?? '' }}</a>
												</div>
												<!--end::Username-->
											</div>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu separator-->
										<div class="separator my-2"></div>
										<!--end::Menu separator-->
										<!--begin::Menu item-->
										<div class="menu-item px-5">
											@php
												$profileRoute = auth()->user()->hasRole('Siswa') ? 'student.account.index' : 'account.index';
											@endphp
											<a href="{{ route($profileRoute) }}" class="menu-link px-5">My Profile</a>
										</div>
										<!--end::Menu item-->
										<!--begin::Menu separator-->
										<div class="separator my-2"></div>
										<!--end::Menu separator-->
										<!--begin::Menu item-->
										<div class="menu-item px-5">
											<a href="#" onclick="confirmSignOut(event)" class="menu-link px-5">Sign Out</a>
											<form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
												@csrf
											</form>
										</div>
										<!--end::Menu item-->
									</div>
									<!--end::User account menu-->
									<!--end::Menu wrapper-->
								</div>
								<!--end::User menu-->
							</div>
							<!--end::Toolbar wrapper-->
						</div>
						<!--end::Container-->
					</div>
					<!--end::Header-->

<!--begin::Notification Drawer (Offcanvas Right)-->
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_notification_drawer" aria-labelledby="kt_notification_drawer_label" style="width: 400px;">
	<div class="offcanvas-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
		<h5 class="offcanvas-title text-white fw-bold" id="kt_notification_drawer_label">
			<i class="ki-duotone ki-notification-status fs-2 text-white me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
			Notifikasi Sistem
		</h5>
		<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body p-0">
		@php
			$notifications = collect();
			if(auth()->check()) {
				$dbNotifications = \App\Models\Notification::where('user_id', auth()->id())->latest()->take(15)->get();
				foreach($dbNotifications as $dn) {
					$icon = 'ki-notification-status';
					$color = 'primary';
					if ($dn->type === 'assignment') { $icon = 'ki-notepad-edit'; $color = 'warning'; }
					elseif ($dn->type === 'announcement') { $icon = 'ki-book-open'; $color = 'success'; }
					elseif ($dn->type === 'badge_earned') { $icon = 'ki-award'; $color = 'danger'; }
					elseif ($dn->type === 'assignment_deadline') { $icon = 'ki-time'; $color = 'danger'; }
					
					$notifications->push([
						'icon' => $icon,
						'color' => $color,
						'title' => $dn->title,
						'time' => $dn->created_at->diffForHumans(),
						'url' => $dn->url ?: '#',
						'message' => $dn->message,
						'is_read' => $dn->is_read
					]);
				}
			}
			
			// Fallback/Generic system updates if empty or not logged in
			if ($notifications->isEmpty()) {
				if(class_exists('\App\Models\LearningModule')) {
					foreach(\App\Models\LearningModule::latest()->take(3)->get() as $module) {
						$notifications->push([
							'icon' => 'ki-book', 'color' => 'success',
							'title' => 'Modul Baru: ' . $module->title,
							'time' => $module->created_at->diffForHumans(),
							'url' => '#',
							'message' => 'Materi baru telah diunggah oleh pengajar.',
							'is_read' => true
						]);
					}
				}
				if(class_exists('\App\Models\Assignment')) {
					foreach(\App\Models\Assignment::latest()->take(3)->get() as $assignment) {
						$notifications->push([
							'icon' => 'ki-notepad-edit', 'color' => 'warning',
							'title' => 'Penugasan: ' . $assignment->title,
							'time' => $assignment->created_at->diffForHumans(),
							'url' => '#',
							'message' => 'Penugasan baru telah diterbitkan.',
							'is_read' => true
						]);
					}
				}
			}
		@endphp

		<div class="px-7 py-5">
			<div class="d-flex align-items-center justify-content-between mb-4">
				<span class="text-gray-600 fw-bold fs-7">{{ $notifications->count() }} notifikasi terbaru</span>
			</div>
		</div>
		<div class="separator"></div>
		<div class="scroll-y px-7 py-3" style="max-height: calc(100vh - 180px);">
			@forelse($notifications as $notif)
			<a href="{{ $notif['url'] }}" class="d-flex align-items-start py-4 text-decoration-none text-hover-primary" style="opacity: {{ isset($notif['is_read']) && $notif['is_read'] ? '0.7' : '1' }};">
				<div class="symbol symbol-40px me-4">
					<span class="symbol-label bg-light-{{ $notif['color'] }}">
						<i class="ki-duotone {{ $notif['icon'] }} fs-2 text-{{ $notif['color'] }}">
							<span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
						</i>
					</span>
				</div>
				<div class="flex-grow-1">
					<span class="fs-6 text-gray-800 fw-bold d-block">{{ Str::limit($notif['title'], 50) }}</span>
					<span class="text-gray-500 fs-7 d-block mb-1">{{ Str::limit($notif['message'], 100) }}</span>
					<span class="text-gray-400 fs-8">{{ $notif['time'] }}</span>
				</div>
			</a>
			<div class="separator separator-dashed"></div>
			@empty
			<div class="text-center py-15">
				<i class="ki-duotone ki-notification-bing fs-3x text-gray-300 mb-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
				<div class="text-gray-500 fw-semibold fs-6">Belum ada notifikasi baru.</div>
			</div>
			@endforelse
		</div>
	</div>
</div>
<!--end::Notification Drawer-->

<script>
function confirmSignOut(event) {
	event.preventDefault();
	Swal.fire({
		title: 'Konfirmasi Keluar',
		text: "Apakah Anda yakin ingin keluar dari aplikasi?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#3085d6',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Ya, Keluar!',
		cancelButtonText: 'Batal'
	}).then((result) => {
		if (result.isConfirmed) {
			document.getElementById('logout-form').submit();
		}
	});
}

// Mark notifications as read when offcanvas drawer is opened
document.addEventListener('DOMContentLoaded', function() {
	var drawerEl = document.getElementById('kt_notification_drawer');
	if (drawerEl) {
		drawerEl.addEventListener('shown.bs.offcanvas', function () {
			// Clear badge/counter instantly
			var badge = document.getElementById('notification-count-badge');
			if (badge) {
				badge.remove();
			}
			
			// Send AJAX request to mark all read
			fetch('{{ route("notifications.mark-as-read") }}', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-CSRF-TOKEN': '{{ csrf_token() }}'
				}
			})
			.then(response => response.json())
			.then(data => {
				console.log('Notifikasi telah dibaca:', data.message);
			})
			.catch(error => {
				console.error('Error marking notifications as read:', error);
			});
		});
	}
});
</script>
