<!DOCTYPE html>
<html lang="id">
	<head>
		@include('partials.head-meta')
			<script>window.addEventListener('error',function(e){try{console.warn('JS suppressed:',e&&e.message);}catch(_){}});window.addEventListener('unhandledrejection',function(e){try{console.warn('Promise rejection:',e&&e.reason);}catch(_){}});</script>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
		<link href="{{ asset('assets/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
			<link href="{{ asset('assets/css/keenicons-fix.css') }}?v=1" rel="stylesheet" type="text/css" />
		<link href="{{ asset('assets/css/elite-theme.css') }}?v=3" rel="stylesheet" type="text/css" />
		@stack('stylesheets')
		<style>
			.student-gradient {
				background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
			}
			.card-premium {
				border: none;
				border-radius: 15px;
				box-shadow: 0 10px 30px rgba(0,0,0,0.05);
			}
		</style>
	</head>
	<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed">
		<div class="d-flex flex-column flex-root">
			<div class="page d-flex flex-row flex-column-fluid">
				<div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper" style="padding-top: {{ ($hideChrome ?? false) ? '16px' : '80px' }};">
					{{-- Frontend Navbar --}}
					@unless($hideChrome ?? false) @include('frontend.layout.navbar') @endunless

					<!-- Content -->
					<div id="kt_content_container" class="d-flex flex-column-fluid align-items-start container-xxl">
						<div class="content flex-row-fluid" id="kt_content">
							@yield('content')
						</div>
					</div>

					{{-- Frontend Footer --}}
					@unless($hideChrome ?? false) @include('frontend.layout.footer') @endunless
				</div>
			</div>
		</div>

		{{-- Frontend Notification Drawer --}}
		@include('frontend.layout.notification_drawer')

		{{-- Hidden session data (parity dengan layout backend) --}}
		<div id="session-success" data-message="{{ session('success', '') }}" style="display:none;"></div>
		<div id="session-error" data-message="{{ session('error', '') }}" style="display:none;"></div>

		<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
		<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

		@auth
		<script>
			$.ajaxSetup({
				headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
			});
		</script>
		@endauth

		{{-- Flash message handler (parity dengan layout backend) --}}
		<script>
			@if(session('success'))
				Swal.fire({ text: "{!! session('success') !!}", icon: "success", buttonsStyling: false, confirmButtonText: "Ok, Mengerti!", customClass: { confirmButton: "btn btn-primary" } });
			@endif
			@if(session('error'))
				Swal.fire({ text: "{!! session('error') !!}", icon: "error", buttonsStyling: false, confirmButtonText: "Ok, Mengerti!", customClass: { confirmButton: "btn btn-danger" } });
			@endif
			@if(session('warning'))
				Swal.fire({ text: "{!! session('warning') !!}", icon: "warning", buttonsStyling: false, confirmButtonText: "Ok, Mengerti!", customClass: { confirmButton: "btn btn-warning" } });
			@endif
			@if($errors->any())
				Swal.fire({ html: "{!! implode('<br>', $errors->all()) !!}", icon: "error", buttonsStyling: false, confirmButtonText: "Ok, Perbaiki", customClass: { confirmButton: "btn btn-danger" } });
			@endif
		</script>

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
		</script>
		@stack('scripts')
		@include('partials.dev-credit')
	</body>
</html>
