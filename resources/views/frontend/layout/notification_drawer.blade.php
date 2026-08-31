<!-- Student Notification Drawer (Offcanvas Right) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="kt_student_notification_drawer" aria-labelledby="kt_student_notification_drawer_label" style="width: 400px;">
	<div class="offcanvas-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
		<h5 class="offcanvas-title text-white fw-bold" id="kt_student_notification_drawer_label">
			<i class="ki-duotone ki-notification-status fs-2 text-white me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
			Notifikasi
		</h5>
		<button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
	</div>
	<div class="offcanvas-body p-0">
		@php
			$studentNotifs = collect();
			
			// Modul pembelajaran terbaru
			if(class_exists('\App\Models\LearningModule')) {
				foreach(\App\Models\LearningModule::latest()->take(3)->get() as $module) {
					$studentNotifs->push([
						'icon' => 'ki-book', 'color' => 'success',
						'title' => 'Modul Baru: ' . $module->title,
						'time' => $module->created_at->diffForHumans()
					]);
				}
			}
			
			// Penugasan terbaru
			if(class_exists('\App\Models\Assignment')) {
				foreach(\App\Models\Assignment::latest()->take(3)->get() as $assignment) {
					$studentNotifs->push([
						'icon' => 'ki-notepad-edit', 'color' => 'warning',
						'title' => 'Tugas Baru: ' . $assignment->title,
						'time' => $assignment->created_at->diffForHumans()
					]);
				}
			}
			
			// Absensi terbaru milik siswa ini
			if(class_exists('\App\Models\Attendance') && auth()->user()) {
				foreach(\App\Models\Attendance::where('user_id', auth()->id())->latest()->take(3)->get() as $attendance) {
					$studentNotifs->push([
						'icon' => 'ki-badge', 'color' => 'primary',
						'title' => 'Absensi ' . ucfirst($attendance->type) . ': ' . ucfirst($attendance->status),
						'time' => $attendance->created_at->diffForHumans()
					]);
				}
			}
		@endphp

		<div class="px-7 py-5">
			<div class="d-flex align-items-center justify-content-between">
				<span class="text-gray-600 fw-bold fs-7">{{ $studentNotifs->count() }} notifikasi terbaru</span>
			</div>
		</div>
		<div class="separator"></div>
		<div class="scroll-y px-7 py-3" style="max-height: calc(100vh - 180px);">
			@forelse($studentNotifs as $notif)
			<div class="d-flex align-items-start py-4">
				<div class="symbol symbol-40px me-4">
					<span class="symbol-label bg-light-{{ $notif['color'] }}">
						<i class="ki-duotone {{ $notif['icon'] }} fs-2 text-{{ $notif['color'] }}">
							<span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span>
						</i>
					</span>
				</div>
				<div class="flex-grow-1">
					<span class="fs-6 text-gray-800 fw-bold d-block">{{ Str::limit($notif['title'], 50) }}</span>
					<span class="text-gray-400 fs-7">{{ $notif['time'] }}</span>
				</div>
			</div>
			<div class="separator separator-dashed"></div>
			@empty
			<div class="text-center py-15">
				<i class="ki-duotone ki-notification-bing fs-3x text-gray-300 mb-5"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
				<div class="text-gray-500 fw-semibold fs-6">Belum ada notifikasi.</div>
			</div>
			@endforelse
		</div>
	</div>
</div>
