					<!--begin::Header-->
					<div id="kt_header" class="header" data-kt-sticky="true" data-kt-sticky-name="header" data-kt-sticky-animation="false" data-kt-sticky-offset="{default: '200px', lg: '300px'}">
						<!--begin::Container-->
						{{-- Lebar navbar MENGIKUTI lebar isi halaman lewat @yield('lebar') yang sama.
					     Kalau navbar dipatok container-xxl sementara isi halaman selebar layar,
					     logo dan menu berhenti di ±1320px sedangkan kartu di bawahnya sampai ke
					     tepi — tepinya jadi tidak sejajar. Dengan memakai section yang sama,
					     halaman biasa tetap container-xxl dan dashboard ikut selebar layar,
					     tanpa perlu mengatur dua tempat. --}}
						<div class="@yield('lebar', 'container-fluid px-4 px-lg-6') d-flex align-items-center flex-lg-stack">
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
										{{-- Dua varian: header berubah gelap pada tema gelap, dan logo biasa
										     memakai #1e293b untuk kata "CBT" sehingga tidak terbaca di sana. --}}
										<img alt="Logo" src="{{ asset('assets/media/logos/cbt-logo.svg') }}" class="h-30px h-lg-40px theme-light-show" />
										<img alt="Logo" src="{{ asset('assets/media/logos/cbt-logo-light.svg') }}" class="h-30px h-lg-40px theme-dark-show" />
									</a>
									<!--end::Header Logo-->
								</div>
								<!--end::Wrapper-->
								{{-- Kotak pencarian menu global dibuang atas permintaan; tempatnya
								     dipakai deretan logo. Markup DAN JS pencariannya ikut terhapus
								     bersama blok ini, jadi tidak ada JS yang menggantung mencari
								     elemen yang sudah tidak ada. --}}
								<div class="deret-logo d-flex align-items-center gap-3">
								    @if(!empty($appSettings['site_logo']))
								        <img src="{{ asset('assets/media/logos/'.$appSettings['site_logo']) }}"
								             alt="Logo sekolah" class="h-40px w-auto" />
								    @endif
								    <img src="{{ asset('assets/media/logos/tut-wuri-handayani.png') }}"
								         alt="Tut Wuri Handayani" class="h-40px w-auto" />
								</div>
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
			/*
			 | Isi panel notifikasi.
			 |
			 | Dulu: baca tabel notifications (yang tidak pernah ditulis siapa pun,
			 | jadi selalu kosong), lalu jatuh ke daftar Modul & Penugasan warisan
			 | lms-sync. Akibatnya panel ini selalu berbunyi "Belum ada notifikasi
			 | baru" dan tidak berguna.
			 |
			 | Sekarang: dihitung dari keadaan CBT saat ini lewat NotifikasiCbt, dan
			 | setiap butir menunjuk satu hal yang PERLU DIKERJAKAN. Baris dari tabel
			 | notifications tetap dipakai bila memang ada (mis. pemberitahuan nilai
			 | yang dikirim sistem), ditaruh lebih dulu.
			 */
			$notifications = collect();

			if (auth()->check()) {
				foreach (\App\Models\Notification::where('user_id', auth()->id())->latest()->take(5)->get() as $dn) {
					$ikon = 'ki-notification-status';
					$warna = 'primary';
					if ($dn->type === 'exam_result') { $ikon = 'ki-verify'; $warna = 'success'; }
					elseif ($dn->type === 'assignment_deadline') { $ikon = 'ki-time'; $warna = 'danger'; }

					$notifications->push([
						'icon' => $ikon,
						'color' => $warna,
						'title' => $dn->title,
						'time' => $dn->created_at->diffForHumans(),
						'url' => $dn->url ?: '#',
						'message' => $dn->message,
						'is_read' => $dn->is_read,
					]);
				}

				// Butir CBT: dihitung, bukan disimpan — begitu pekerjaannya selesai,
				// butirnya hilang sendiri tanpa perlu ditandai "sudah dibaca".
				try {
					foreach (app(\App\Services\NotifikasiCbt::class)->untuk(auth()->user()) as $b) {
						$notifications->push([
							'icon' => $b['ikon'],
							'color' => $b['warna'],
							'title' => $b['judul'],
							'time' => $b['waktu'],
							'url' => $b['url'],
							'message' => $b['teks'],
							'is_read' => true,
						]);
					}
				} catch (\Throwable $e) {
					// Panel notifikasi ada di NAVBAR: kalau ia melempar galat, SELURUH
					// halaman ikut gagal. Jadi kegagalannya ditelan di sini saja.
					report($e);
				}

				$notifications = $notifications->take(15);
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
